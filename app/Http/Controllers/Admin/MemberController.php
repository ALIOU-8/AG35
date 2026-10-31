<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MembershipFee;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('statut', 'pending');
        $search = trim((string) $request->query('q'));
        $season = MembershipFee::currentSeason();

        $members = User::query()
            ->when(array_key_exists($status, User::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('school', 'like', "%{$search}%")))
            ->withSum(['membershipFees as paid_current_season' => fn ($query) => $query->where('season', $season)], 'amount')
            ->orderByRaw("case role when 'admin' then 0 when 'bureau' then 1 else 2 end")
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.members.index', [
            'members' => $members,
            'status' => $status,
            'search' => $search,
            'season' => $season,
            'due' => MembershipFee::dueAmount(),
            'counts' => User::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.members.edit', [
            'member' => $user,
            'fees' => $user->membershipFees()->with('recorder')->latest('paid_on')->get(),
            'season' => MembershipFee::currentSeason(),
            'defaultFee' => Setting::get('membership_fee'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'school' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'study_level' => ['nullable', Rule::in(User::STUDY_LEVELS)],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'arrival_year' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'status' => ['required', Rule::in(array_keys(User::STATUSES))],
            'role' => ['nullable', Rule::in(array_keys(User::ROLES))],
            'bureau_title' => ['nullable', 'string', 'max:80'],
            'bureau_order' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if ($user->is($me) && ($data['status'] !== 'active' || (isset($data['role']) && $data['role'] !== $me->role))) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre rôle ou statut.');
        }

        // Les rôles ne sont attribués que par un administrateur, et un membre du bureau ne touche pas aux administrateurs.
        if (! Gate::allows('admin') && $user->isAdmin()) {
            abort(403);
        }

        $user->fill(collect($data)->only([
            'name', 'email', 'phone', 'school', 'field_of_study', 'study_level', 'origin_city', 'arrival_year',
        ])->all());
        $user->bureau_title = $data['bureau_title'] ?? null;
        $user->bureau_order = $data['bureau_order'] ?? null;

        if (Gate::allows('admin') && isset($data['role'])) {
            $user->role = $data['role'];
        }

        $user->status = $data['status'];
        $user->save();

        if ($user->isActive()) {
            self::activate($user);
        }

        return redirect()->route('admin.members.edit', $user)->with('status', 'Fiche du membre mise à jour.');
    }

    public function approve(User $user): RedirectResponse
    {
        $user->status = 'active';
        $user->save();
        self::activate($user);

        return back()->with('status', "{$user->name} est maintenant membre actif.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin');
        abort_if($user->is($request->user()), 403);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        return redirect()->route('admin.members.index', ['statut' => 'active'])->with('status', 'Membre supprimé.');
    }

    public function export(): StreamedResponse
    {
        $season = MembershipFee::currentSeason();

        $due = MembershipFee::dueAmount();

        return response()->streamDownload(function () use ($season, $due) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM : Excel ouvre correctement les accents.
            fputcsv($out, ['Nom', 'Email', 'Téléphone', 'Établissement', 'Filière', 'Niveau', 'Ville d\'origine', 'Arrivée à Rennes', 'Rôle', 'Fonction au bureau', 'Statut', "Cotisation {$season}", 'Inscrit le'], ';');

            User::withSum(['membershipFees as paid' => fn ($query) => $query->where('season', $season)], 'amount')
                ->orderBy('name')
                ->each(function (User $user) use ($out, $due) {
                    $status = MembershipFee::paymentStatus((float) $user->paid, $due);

                    fputcsv($out, [
                        $user->name, $user->email, $user->phone, $user->school, $user->field_of_study,
                        $user->study_level, $user->origin_city, $user->arrival_year, $user->roleLabel(),
                        $user->bureau_title, $user->statusLabel(),
                        match ($status) {
                            'paid' => 'Payée',
                            'partial' => 'Partielle ('.number_format((float) $user->paid, 2, ',', ' ').' €)',
                            default => 'Non payée',
                        },
                        $user->created_at->format('d/m/Y'),
                    ], ';');
                });

            fclose($out);
        }, 'membres-ag35-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Liste des membres en PDF (paysage) avec la situation de cotisation de la saison en cours.
     */
    public function pdf(Request $request): Response
    {
        $season = MembershipFee::currentSeason();
        $due = MembershipFee::dueAmount();

        $members = User::withSum(['membershipFees as paid' => fn ($query) => $query->where('season', $season)], 'amount')
            ->orderByRaw("case status when 'active' then 0 when 'pending' then 1 else 2 end")
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($due) {
                $user->fee_status = MembershipFee::paymentStatus((float) $user->paid, $due);
            });

        return Pdf::loadView('pdf.members', [
            'members' => $members,
            'season' => $season,
            'due' => $due,
            'counts' => $members->countBy('status'),
            'association' => Setting::get('association_name'),
            'generatedBy' => $request->user()->name,
        ])->setPaper('a4', 'landscape')->download('membres-ag35-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Un membre activé rejoint automatiquement la discussion générale.
     */
    public static function activate(User $user): void
    {
        if (! $user->approved_at) {
            $user->forceFill(['approved_at' => now()])->save();
        }

        Conversation::general()->participants()->syncWithoutDetaching([$user->id]);
    }
}

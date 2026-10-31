<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipFee;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DuesReminder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DuesController extends Controller
{
    /**
     * Un membre n'est relancé automatiquement qu'une fois par semaine.
     */
    public const REMINDER_INTERVAL_DAYS = 7;

    public const FILTERS = [
        'a-recouvrer' => 'À recouvrer',
        'paid' => 'À jour',
        'tous' => 'Tous',
    ];

    /**
     * Tableau de bord du recouvrement pour une saison.
     */
    public function index(Request $request): View
    {
        $season = self::requestedSeason($request);
        $filter = array_key_exists($request->query('statut'), self::FILTERS) ? $request->query('statut') : 'a-recouvrer';
        $search = trim((string) $request->query('q'));
        $report = self::report($season);

        $visible = $report['allMembers']
            ->filter(fn (User $member) => match ($filter) {
                'a-recouvrer' => $member->payment_status !== 'paid',
                'paid' => $member->payment_status === 'paid',
                default => true,
            })
            ->filter(fn (User $member) => $search === ''
                || str_contains(mb_strtolower($member->name.' '.$member->email), mb_strtolower($search)));

        return view('admin.dues.index', [
            ...$report,
            'season' => $season,
            'seasons' => MembershipFee::seasons(),
            'filter' => $filter,
            'search' => $search,
            'members' => $visible->values(),
            'lastReminders' => self::lastReminders($season),
            'recentPayments' => MembershipFee::with(['user', 'recorder'])->where('season', $season)
                ->latest('paid_on')->latest('id')->limit(10)->get(),
            'counts' => [
                'a-recouvrer' => $report['allMembers']->where('payment_status', '!=', 'paid')->count(),
                'paid' => $report['stats']['paid'],
                'tous' => $report['stats']['members'],
            ],
        ]);
    }

    /**
     * Rapport PDF de la saison pour le bureau : synthèse, encaissements, membres à recouvrer, journal.
     */
    public function pdf(Request $request): Response
    {
        $season = self::requestedSeason($request);
        $report = self::report($season);

        return Pdf::loadView('pdf.dues', [
            ...$report,
            'season' => $season,
            'association' => Setting::get('association_name'),
            'generatedBy' => $request->user()->name,
            'toCollect' => $report['allMembers']->where('payment_status', '!=', 'paid')->values(),
            'payments' => MembershipFee::with(['user', 'recorder'])->where('season', $season)
                ->orderBy('paid_on')->orderBy('id')->get(),
        ])->setPaper('a4')->download("cotisations-{$season}.pdf");
    }

    public function remind(Request $request, User $user): RedirectResponse
    {
        $season = $this->validatedSeason($request);
        abort_unless($user->isActive(), 404);

        $dues = $user->duesFor($season);
        if ($dues['status'] === 'paid') {
            return back()->with('error', "{$user->name} est à jour pour {$season}.");
        }

        $user->notify(new DuesReminder($request->user(), $season, $dues['remaining']));

        return back()->with('status', "Rappel envoyé à {$user->name}.");
    }

    /**
     * Relance tous les membres qui n'ont pas réglé la saison, sauf ceux relancés cette semaine.
     */
    public function remindAll(Request $request): RedirectResponse
    {
        $season = $this->validatedSeason($request);
        $due = MembershipFee::dueAmount();

        if ($due <= 0) {
            return back()->with('error', 'Indiquez d\'abord le montant de la cotisation dans « Contenu du site ».');
        }

        $recentlyReminded = self::lastReminders($season)
            ->filter(fn (string $sentAt) => now()->subDays(self::REMINDER_INTERVAL_DAYS)->lt($sentAt))
            ->keys();

        $toRemind = self::membersWithPayments($season, $due)
            ->reject(fn (User $member) => $member->payment_status === 'paid' || $member->is($request->user()));
        $skipped = $toRemind->filter(fn (User $member) => $recentlyReminded->contains($member->id))->count();

        $toRemind->reject(fn (User $member) => $recentlyReminded->contains($member->id))
            ->each(fn (User $member) => $member->notify(new DuesReminder($request->user(), $season, max(0, $due - $member->paid_amount))));

        $sent = $toRemind->count() - $skipped;
        $message = $sent === 0 ? 'Aucun rappel à envoyer.' : ($sent === 1 ? '1 rappel envoyé.' : "{$sent} rappels envoyés.");
        if ($skipped > 0) {
            $message .= " {$skipped} membre(s) déjà relancé(s) ces ".self::REMINDER_INTERVAL_DAYS.' derniers jours.';
        }

        return back()->with('status', $message);
    }

    /**
     * Journal des paiements de la saison, pour la comptabilité.
     */
    public function export(Request $request): StreamedResponse
    {
        $season = self::requestedSeason($request);

        return response()->streamDownload(function () use ($season) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM : Excel ouvre correctement les accents.
            fputcsv($out, ['Date', 'Membre', 'Email', 'Montant (€)', 'Moyen', 'Note', 'Enregistré par'], ';');

            MembershipFee::with(['user', 'recorder'])->where('season', $season)->orderBy('paid_on')->each(function (MembershipFee $fee) use ($out) {
                fputcsv($out, [
                    $fee->paid_on->format('d/m/Y'), $fee->user->name, $fee->user->email,
                    number_format((float) $fee->amount, 2, ',', ''), $fee->method, $fee->note, $fee->recorder?->name,
                ], ';');
            });

            fclose($out);
        }, "cotisations-{$season}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Chiffres communs au tableau de bord et au rapport PDF.
     *
     * @return array{due: float, allMembers: Collection<int, User>, stats: array<string, int|float>, byRecorder: Collection<int, MembershipFee>, byMethod: Collection<int, MembershipFee>}
     */
    protected static function report(string $season): array
    {
        $due = MembershipFee::dueAmount();
        $members = self::membersWithPayments($season, $due);
        $expected = $due * $members->count();
        $collected = (float) $members->sum('paid_amount');

        return [
            'due' => $due,
            'allMembers' => $members,
            'stats' => [
                'members' => $members->count(),
                'paid' => $members->where('payment_status', 'paid')->count(),
                'partial' => $members->where('payment_status', 'partial')->count(),
                'unpaid' => $members->where('payment_status', 'unpaid')->count(),
                'expected' => $expected,
                'collected' => $collected,
                'rate' => $expected > 0 ? min(100, (int) round($collected / $expected * 100)) : 100,
            ],
            // Qui a encaissé quoi : utile pour savoir qui détient l'argent liquide.
            'byRecorder' => MembershipFee::query()->where('season', $season)
                ->selectRaw('recorded_by, sum(amount) as total, count(*) as payments')
                ->groupBy('recorded_by')->orderByDesc('total')->with('recorder')->get(),
            'byMethod' => MembershipFee::query()->where('season', $season)
                ->selectRaw('method, sum(amount) as total, count(*) as payments')
                ->groupBy('method')->orderByDesc('total')->get(),
        ];
    }

    protected static function requestedSeason(Request $request): string
    {
        $season = $request->query('saison');

        return in_array($season, MembershipFee::seasons(), true) ? $season : MembershipFee::currentSeason();
    }

    /**
     * Membres actifs avec le total payé sur la saison et leur statut (à jour, partielle, impayée).
     *
     * @return Collection<int, User>
     */
    protected static function membersWithPayments(string $season, float $due): Collection
    {
        return User::active()
            ->withSum(['membershipFees as paid_amount' => fn ($query) => $query->where('season', $season)], 'amount')
            ->withMax(['membershipFees as last_paid_on' => fn ($query) => $query->where('season', $season)], 'paid_on')
            ->orderBy('name')
            ->get()
            ->each(function (User $member) use ($due) {
                $member->paid_amount = (float) $member->paid_amount;
                $member->payment_status = MembershipFee::paymentStatus($member->paid_amount, $due);
            });
    }

    /**
     * Date du dernier rappel de la saison, par membre.
     *
     * @return Collection<int, string>
     */
    protected static function lastReminders(string $season): Collection
    {
        return DatabaseNotification::query()
            ->where('type', DuesReminder::class)
            ->where('notifiable_type', (new User)->getMorphClass())
            ->where('data->season', $season)
            ->selectRaw('notifiable_id, max(created_at) as sent_at')
            ->groupBy('notifiable_id')
            ->pluck('sent_at', 'notifiable_id');
    }

    protected function validatedSeason(Request $request): string
    {
        return $request->validate(['season' => ['required', Rule::in(MembershipFee::seasons())]])['season'];
    }
}

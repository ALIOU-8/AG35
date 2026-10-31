<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        return view('chat.index', [
            'conversations' => self::sidebar($request->user()),
            'active' => null,
            'contacts' => self::contacts($request->user()),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();
        abort_unless($conversation->hasParticipant($user), 404);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        $conversation->load('participants');

        $messages = $conversation->messages()->with(['user', 'conversation'])
            ->latest('id')->limit(50)->get()->reverse()->values();

        $other = $conversation->isDirect() ? $conversation->otherParticipant($user) : null;

        return view('chat.index', [
            'conversations' => self::sidebar($user),
            'contacts' => self::contacts($user),
            'active' => [
                'uuid' => $conversation->uuid,
                'name' => $conversation->displayName($user),
                'type' => $conversation->type,
                'subtitle' => match (true) {
                    $other !== null => $other->isOnline() ? 'En ligne' : ($other->last_seen_at ? 'Vu '.$other->last_seen_at->diffForHumans() : ''),
                    default => $conversation->participants->count().' participants',
                },
                'profileUrl' => $other ? route('members.show', $other) : null,
                'avatar' => $other?->avatarUrl(),
                'initials' => $other?->initials() ?? mb_strtoupper(mb_substr($conversation->name ?? 'G', 0, 2)),
                'participants' => $conversation->participants->sortBy('name')->map(fn (User $p) => $p->name)->values(),
                'messages' => $messages->map->toChatArray(),
                'hasMore' => $conversation->messages()->count() > $messages->count(),
                'urls' => [
                    'send' => route('messages.store', $conversation),
                    'poll' => route('messages.poll', $conversation),
                    'older' => route('messages.older', $conversation),
                    'read' => route('conversations.read', $conversation),
                    'leave' => $conversation->type === 'group' ? route('conversations.leave', $conversation) : null,
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'members' => ['required', 'array', 'min:1', 'max:200'],
            'members.*' => ['string'],
        ], ['members.required' => 'Choisissez au moins un membre.']);

        $members = collect($data['members'])
            ->map(fn ($key) => User::findByEncryptedKey($key))
            ->filter(fn (?User $member) => $member?->isActive());

        if ($members->isEmpty()) {
            throw ValidationException::withMessages(['members' => 'Choisissez au moins un membre.']);
        }

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $data['name'],
            'created_by' => $request->user()->id,
        ]);
        $conversation->participants()->attach($members->pluck('id')->push($request->user()->id)->unique()->all());

        return redirect()->route('conversations.show', $conversation);
    }

    public function direct(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();
        abort_if($user->is($me) || ! $user->isActive(), 404);

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->whereKey($me->id))
            ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create(['type' => 'direct', 'created_by' => $me->id]);
            $conversation->participants()->attach([$me->id, $user->id]);
        }

        return redirect()->route('conversations.show', $conversation);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->hasParticipant($request->user()), 404);

        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->json(['read' => true]);
    }

    public function leave(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->type === 'group' && $conversation->hasParticipant($request->user()), 404);

        $conversation->participants()->detach($request->user()->id);

        return redirect()->route('conversations.index')->with('status', 'Vous avez quitté le groupe.');
    }

    /**
     * Liste latérale des conversations, la plus récente en premier, avec le nombre de messages non lus.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function sidebar(User $user): Collection
    {
        return $user->conversations()
            ->with(['participants', 'latestMessage.user'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('messages.user_id', '!=', $user->id)
                    ->whereRaw('messages.created_at > coalesce(conversation_user.last_read_at, ?)', ['1970-01-01 00:00:00']);
            }])
            ->orderByRaw('coalesce(conversations.last_message_at, conversations.created_at) desc')
            ->get()
            ->map(function (Conversation $conversation) use ($user) {
                $other = $conversation->isDirect() ? $conversation->otherParticipant($user) : null;
                $last = $conversation->latestMessage;

                return [
                    'uuid' => $conversation->uuid,
                    'url' => route('conversations.show', $conversation),
                    'name' => $conversation->displayName($user),
                    'type' => $conversation->type,
                    'avatar' => $other?->avatarUrl(),
                    'initials' => $other?->initials() ?? mb_strtoupper(mb_substr($conversation->name ?? 'G', 0, 2)),
                    'online' => (bool) $other?->isOnline(),
                    'preview' => $last ? (($last->user_id === $user->id ? 'Vous : ' : '').($last->body ?: '📎 Pièce jointe')) : 'Aucun message',
                    'time' => $last?->created_at->isToday() ? $last->created_at->format('H:i') : $last?->created_at->translatedFormat('j M'),
                    'unread' => $conversation->unread_count,
                ];
            });
    }

    /**
     * Membres actifs proposés pour un nouveau groupe ; la valeur est chiffrée, jamais l'id.
     *
     * @return Collection<int, array<string, string>>
     */
    protected static function contacts(User $user): Collection
    {
        return User::active()->whereKeyNot($user->id)->orderBy('name')->get()
            ->map(fn (User $member) => ['key' => encrypt($member->id), 'name' => $member->name]);
    }
}

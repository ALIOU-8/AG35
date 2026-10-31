<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\MemberNotification;
use App\Notifications\NewMessage;
use App\Support\Realtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MessageController extends Controller
{
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->hasParticipant($user), 404);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,odt,txt,zip,mp3,m4a'],
        ], ['body.required_without' => 'Le message est vide.']);

        $message = $conversation->messages()->make(['body' => $data['body'] ?? null]);
        $message->user()->associate($user);

        if ($file = $request->file('attachment')) {
            $message->fill([
                'attachment_path' => $file->store('chat/'.now()->format('Y/m'), 'public'),
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_mime' => $file->getMimeType(),
            ]);
        }

        $message->save();

        $conversation->forceFill(['last_message_at' => $message->created_at])->save();
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => $message->created_at]);

        $message->setRelation('conversation', $conversation->load('participants'));
        Realtime::toOthers(new MessageSent($message));

        // Push pour les membres dont l'application est fermée ; seuls les appareils abonnés sont concernés.
        if (MemberNotification::pushConfigured()) {
            Notification::send(
                $conversation->participants()->whereKeyNot($user->id)->where('status', 'active')->whereHas('pushSubscriptions')->get(),
                new NewMessage($message),
            );
        }

        return response()->json(['message' => $message->toChatArray()], 201);
    }

    /**
     * Secours quand le WebSocket est indisponible : messages postérieurs au dernier uuid connu.
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->hasParticipant($request->user()), 404);

        $afterId = $conversation->messages()->where('uuid', (string) $request->query('after'))->value('id');

        $messages = $conversation->messages()->with(['user', 'conversation'])
            ->when($afterId, fn ($query) => $query->where('id', '>', $afterId))
            ->latest('id')->limit(50)->get()->reverse()->values();

        return response()->json(['messages' => $messages->map->toChatArray()]);
    }

    public function older(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->hasParticipant($request->user()), 404);

        $beforeId = $conversation->messages()->where('uuid', (string) $request->query('before'))->value('id');
        abort_unless($beforeId, 404);

        $messages = $conversation->messages()->with(['user', 'conversation'])
            ->where('id', '<', $beforeId)->latest('id')->limit(31)->get();

        return response()->json([
            'messages' => $messages->take(30)->reverse()->values()->map(fn (Message $m) => $m->toChatArray()),
            'hasMore' => $messages->count() > 30,
        ]);
    }
}

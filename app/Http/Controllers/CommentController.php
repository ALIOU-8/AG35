<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MemberNotification;
use App\Notifications\PostCommented;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $comment = $post->comments()->make($data);
        $comment->user()->associate($request->user());
        $comment->save();

        // L'auteur de la publication et les membres qui l'ont déjà commentée.
        $recipients = User::active()
            ->whereKeyNot($request->user()->id)
            ->where(fn ($query) => $query->whereKey($post->user_id)
                ->orWhereIn('id', Comment::where('post_id', $post->id)->select('user_id')))
            ->get();
        Notification::send($recipients, new PostCommented($comment));

        return response()->json(['comment' => $comment->load('user')->toFeedArray($request->user())], 201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->canBeDeletedBy($request->user()), 403);

        MemberNotification::forget('comment_id', $comment->id);
        $comment->delete();

        return response()->json(['deleted' => true]);
    }
}

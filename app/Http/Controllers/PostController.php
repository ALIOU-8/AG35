<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Notifications\MemberNotification;
use App\Notifications\PostLiked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public const VIDEO_MIMES = ['video/mp4', 'video/webm', 'video/quicktime'];

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimetypes:'.implode(',', [...self::IMAGE_MIMES, ...self::VIDEO_MIMES]), 'max:102400'],
            'visibility' => ['nullable', 'in:members,public'],
        ], [
            'body.required_without' => 'Écrivez un texte ou ajoutez au moins une photo ou vidéo.',
            'media.*.max' => 'Chaque fichier doit faire moins de 100 Mo.',
            'media.*.mimetypes' => 'Formats acceptés : JPG, PNG, GIF, WEBP, MP4, WEBM, MOV.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $data, $user) {
            $post = $user->posts()->create([
                'body' => $data['body'] ?? null,
                // Seul le bureau publie sur le site public.
                'visibility' => $user->canManageMembers() ? ($data['visibility'] ?? 'members') : 'members',
            ]);

            foreach ($request->file('media', []) as $position => $file) {
                $mime = $file->getMimeType();

                $post->media()->create([
                    'type' => Str::startsWith($mime, 'video/') ? 'video' : 'image',
                    'path' => $file->store('posts/'.now()->format('Y/m'), 'public'),
                    'mime' => $mime,
                    'size' => $file->getSize(),
                    'position' => $position,
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('feed')]);
        }

        return redirect()->route('feed')->with('status', 'Publication partagée.');
    }

    /**
     * Publication seule, ouverte depuis une notification.
     */
    public function show(Request $request, Post $post): View
    {
        return view('posts.show', [
            'post' => FeedController::feedQuery($request->user())->whereKey($post->id)->firstOrFail(),
        ]);
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->canBeDeletedBy($request->user()), 403);

        MemberNotification::forget('post_id', $post->id);
        Storage::disk('public')->delete($post->media->pluck('path')->all());
        $post->delete();

        return back()->with('status', 'Publication supprimée.');
    }

    public function like(Request $request, Post $post): JsonResponse
    {
        $liker = $request->user();
        $result = $post->likers()->toggle($liker->id);
        $liked = count($result['attached']) > 0;

        // Comme sur Facebook : une seule notification par personne, retirée si le « j'aime » est annulé.
        MemberNotification::forget('key', PostLiked::key($post, $liker));
        if ($liked && $post->user_id !== $liker->id) {
            $post->user->notify(new PostLiked($liker, $post));
        }

        return response()->json([
            'liked' => $liked,
            'likes' => $post->likers()->count(),
        ]);
    }
}

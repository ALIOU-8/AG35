<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Str;

class PostCommented extends MemberNotification
{
    public function __construct(public Comment $comment)
    {
        parent::__construct($comment->user);
    }

    public function kind(): string
    {
        return 'comment';
    }

    /**
     * Comme sur Facebook : l'auteur de la publication et les autres participants à la discussion sont prévenus.
     */
    public function text(User $notifiable): string
    {
        $post = $this->comment->post;

        return match (true) {
            $post->user_id === $notifiable->id => 'a commenté votre publication',
            $post->user_id === $this->actor->id => 'a aussi commenté sa publication',
            default => 'a aussi commenté la publication de '.$post->user->name,
        };
    }

    public function excerpt(): ?string
    {
        return Str::limit(Str::squish($this->comment->body), 80);
    }

    public function url(): string
    {
        return route('posts.show', $this->comment->post).'#commentaires';
    }

    public function references(): array
    {
        return ['comment_id' => $this->comment->id, 'post_id' => $this->comment->post_id];
    }
}

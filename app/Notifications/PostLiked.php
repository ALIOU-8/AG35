<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;

class PostLiked extends MemberNotification
{
    public function __construct(User $actor, public Post $post)
    {
        parent::__construct($actor);
    }

    /**
     * Une seule notification par personne et par publication : la clé permet de la remplacer ou de la retirer.
     */
    public static function key(Post $post, User $liker): string
    {
        return "like:{$post->id}:{$liker->id}";
    }

    public function kind(): string
    {
        return 'like';
    }

    public function text(User $notifiable): string
    {
        return 'aime votre publication';
    }

    public function excerpt(): ?string
    {
        return $this->post->excerpt();
    }

    public function url(): string
    {
        return route('posts.show', $this->post);
    }

    public function references(): array
    {
        return ['key' => self::key($this->post, $this->actor), 'post_id' => $this->post->id];
    }
}

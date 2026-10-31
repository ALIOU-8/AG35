<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MembershipFee;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function index(Request $request): View
    {
        return view('feed.index', [
            'posts' => self::feedQuery($request->user())->simplePaginate(10),
            'meetings' => Meeting::whereNull('ended_at')
                ->where(fn ($query) => $query->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now()->subHours(3)))
                ->orderBy('scheduled_at')->limit(4)->get(),
            'newMembers' => User::active()->latest('approved_at')->limit(6)->get(),
            'dues' => $request->user()->duesFor(MembershipFee::currentSeason()),
        ]);
    }

    /**
     * Requête commune au fil et aux profils : auteur, médias, commentaires, compteur et état des « j'aime ».
     */
    public static function feedQuery(User $viewer): Builder
    {
        return Post::query()
            ->with(['user', 'media', 'comments.user'])
            ->withCount('likers')
            ->withExists(['likers as liked_by_viewer' => fn ($query) => $query->where('users.id', $viewer->id)])
            ->latest();
    }
}

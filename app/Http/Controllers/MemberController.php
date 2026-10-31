<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $level = $request->query('niveau');

        $members = User::active()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('school', 'like', "%{$search}%")
                    ->orWhere('field_of_study', 'like', "%{$search}%")
                    ->orWhere('origin_city', 'like', "%{$search}%"));
            })
            ->when(in_array($level, User::STUDY_LEVELS, true), fn ($query) => $query->where('study_level', $level))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('members.index', compact('members', 'search', 'level'));
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($user->isActive() || $request->user()->canManageMembers(), 404);

        return view('members.show', [
            'member' => $user,
            'posts' => FeedController::feedQuery($request->user())->where('user_id', $user->id)->simplePaginate(10),
        ]);
    }
}

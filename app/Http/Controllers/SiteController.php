<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Meeting;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', [
            'bureau' => User::active()->whereNotNull('bureau_title')
                ->orderByRaw('bureau_order is null')->orderBy('bureau_order')->orderBy('name')->get(),
            'news' => Post::public()->with(['user', 'media'])->latest()->limit(3)->get(),
            'stats' => [
                'members' => User::active()->count(),
                'posts' => Post::count(),
                'meetings' => Meeting::count(),
            ],
        ]);
    }

    public function news(): View
    {
        return view('site.news', [
            'posts' => Post::public()->with(['user', 'media'])->latest()->paginate(9),
        ]);
    }

    public function article(Post $post): View
    {
        abort_unless($post->isPublic(), 404);

        return view('site.article', ['post' => $post->load(['user', 'media'])]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            // Champ piège invisible : rempli uniquement par les robots.
            'website' => ['prohibited'],
        ]);

        ContactMessage::create($data);

        return redirect()->to(route('home').'#contact')
            ->with('contact_status', 'Merci ! Votre message a bien été envoyé au bureau de l\'association.');
    }
}

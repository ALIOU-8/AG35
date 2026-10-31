<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::latest()->paginate(20);

        // Affichés une fois : marqués comme lus.
        ContactMessage::whereKey($messages->getCollection()->modelKeys())->whereNull('read_at')->update(['read_at' => now()]);

        return view('admin.contacts.index', compact('messages'));
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return back()->with('status', 'Message supprimé.');
    }
}

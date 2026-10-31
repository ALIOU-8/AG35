<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingAnnounced;
use App\Notifications\MemberNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function index(): View
    {
        return view('meetings.index', [
            'upcoming' => Meeting::with('creator')->whereNull('ended_at')
                ->orderByRaw('scheduled_at is not null')->orderBy('scheduled_at')->latest()->get(),
            'past' => Meeting::with('creator')->whereNotNull('ended_at')->latest('ended_at')->limit(15)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date', 'required_unless:start_now,1'],
            'start_now' => ['nullable', 'boolean'],
        ], ['scheduled_at.required_unless' => 'Indiquez la date et l\'heure de la réunion.']);

        $meeting = new Meeting([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'scheduled_at' => $request->boolean('start_now') ? null : $data['scheduled_at'],
        ]);
        $meeting->creator()->associate($request->user());
        $meeting->save();

        Notification::send(User::active()->whereKeyNot($request->user()->id)->get(), new MeetingAnnounced($meeting));

        if ($request->boolean('start_now')) {
            return redirect()->route('meetings.show', $meeting);
        }

        return redirect()->route('meetings.index')->with('status', 'Réunion planifiée.');
    }

    public function show(Request $request, Meeting $meeting): View|RedirectResponse
    {
        if ($meeting->isEnded()) {
            return redirect()->route('meetings.index')->with('error', 'Cette réunion est terminée.');
        }

        return view('meetings.room', [
            'meeting' => $meeting,
            'config' => [
                'driver' => config('visio.driver'),
                'channel' => $meeting->uuid,
                'me' => $request->user()->toPublicArray(),
                'iceServers' => config('visio.ice_servers'),
                'jitsiDomain' => config('visio.jitsi_domain'),
                'canEnd' => $meeting->canBeManagedBy($request->user()),
                'endUrl' => route('meetings.end', $meeting),
                'leaveUrl' => route('meetings.index'),
            ],
        ]);
    }

    public function end(Request $request, Meeting $meeting): JsonResponse|RedirectResponse
    {
        abort_unless($meeting->canBeManagedBy($request->user()), 403);

        $meeting->forceFill(['ended_at' => now()])->save();

        if ($request->expectsJson()) {
            return response()->json(['ended' => true]);
        }

        return redirect()->route('meetings.index')->with('status', 'Réunion terminée.');
    }

    public function destroy(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless($meeting->canBeManagedBy($request->user()), 403);

        MemberNotification::forget('meeting_id', $meeting->id);
        $meeting->delete();

        return redirect()->route('meetings.index')->with('status', 'Réunion supprimée.');
    }
}

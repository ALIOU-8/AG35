<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipFee;
use App\Models\User;
use App\Notifications\DuesPaymentRecorded;
use App\Notifications\MemberNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembershipFeeController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'season' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'amount' => ['required', 'numeric', 'min:0', 'max:10000'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in(MembershipFee::METHODS)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $fee = $user->membershipFees()->make($data);
        $fee->recorder()->associate($request->user());
        $fee->save();

        // Reçu dans la cloche du membre (inutile quand on enregistre son propre paiement).
        if (! $user->is($request->user())) {
            $user->notify(new DuesPaymentRecorded($fee));
        }

        return back()->with('status', "Paiement de {$user->name} enregistré.");
    }

    public function destroy(MembershipFee $fee): RedirectResponse
    {
        MemberNotification::forget('fee_id', $fee->id);
        $fee->delete();

        return back()->with('status', 'Cotisation supprimée.');
    }
}

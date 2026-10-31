<?php

namespace App\Http\Controllers;

use App\Models\MembershipFee;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuesController extends Controller
{
    /**
     * « Ma cotisation » : situation de la saison, historique et comment payer.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('dues.show', [
            'dues' => $user->duesFor(MembershipFee::currentSeason()),
            'history' => $user->membershipFees()->latest('paid_on')->get(),
            'instructions' => Setting::get('dues_instructions'),
            'treasurers' => User::active()->whereKeyNot($user->id)
                ->where(fn ($query) => $query->where('bureau_title', 'like', 'Trésor%')->orWhere('bureau_title', 'like', 'Tresor%'))
                ->get(),
        ]);
    }
}

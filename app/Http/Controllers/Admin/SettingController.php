<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(Setting::DEFAULTS)->map(fn () => ['nullable', 'string', 'max:3000'])->all();
        $rules['contact_email'] = ['nullable', 'email', 'max:180'];
        $rules['membership_fee'] = ['nullable', 'numeric', 'min:0', 'max:10000'];
        foreach (['facebook_url', 'instagram_url', 'whatsapp_url'] as $key) {
            $rules[$key] = ['nullable', 'url', 'max:500'];
        }

        Setting::put(collect($request->validate($rules))->map(fn ($value) => $value ?? '')->all());

        return back()->with('status', 'Contenu du site mis à jour.');
    }
}

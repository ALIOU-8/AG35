<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30'],
            'school' => ['required', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'study_level' => ['nullable', Rule::in(User::STUDY_LEVELS)],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Le tout premier compte administre l'association ; les suivants attendent la validation du bureau.
        $isFirstAccount = ! User::query()->exists();

        $user = User::create([
            ...$request->only(['name', 'email', 'phone', 'school', 'field_of_study', 'study_level', 'origin_city']),
            'password' => Hash::make($request->password),
        ]);

        if ($isFirstAccount) {
            $user->forceFill(['role' => 'admin', 'status' => 'active'])->save();
            MemberController::activate($user);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route($user->isActive() ? 'feed' : 'account.pending', absolute: false));
    }
}

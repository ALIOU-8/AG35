<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            if ($request->expectsJson()) {
                abort(403, 'Votre compte n\'est pas encore actif.');
            }

            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}

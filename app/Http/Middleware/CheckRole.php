<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            abort(403);
        }
        $userRole = auth()->user()->role instanceof UserRole
            ? auth()->user()->role->value
            : auth()->user()->role;
        if (!in_array($userRole, $roles, true)) {
            abort(403);
        }
        return $next($request);
    }
}
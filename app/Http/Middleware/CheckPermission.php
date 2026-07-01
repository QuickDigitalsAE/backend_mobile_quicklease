<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Super Administrator (role id 1) => no permission check
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->hasPermissionTo($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to this resource.'
            ], 403);
        }

        return $next($request);
    }
}

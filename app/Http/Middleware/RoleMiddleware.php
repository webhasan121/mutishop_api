<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // ১. ইউজার লগইন করা আছে কিনা চেক
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized. Please login first.'], 401);
        }

        $user = Auth::user();


        // ২. ইউজারের রোল চেক করা (যেই রোলগুলো রিকোয়েস্টে পাঠানো হবে)
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // ৩. রোল না মিললে Forbidden
        return response()->json([
            'message' => 'Access Denied! You are not authorized.'
        ], 403);
    }
}

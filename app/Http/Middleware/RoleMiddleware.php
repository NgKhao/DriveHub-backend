<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Kiểm tra role của người dùng có authenticated chưa
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized. No authenticated user.',
                'status' => 'error'
            ], 401);
        }

        // Nếu user có 1 trong các role được phép
        if (!in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden. Allowed roles: ' . implode(', ', $roles),
                'status' => 'error'
            ], 403);
        }

        // Nếu role hợp lệ, tiếp tục xử lý request
        return $next($request);
    }
}

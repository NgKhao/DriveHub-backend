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
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Kiểm tra role của người dùng có authenticated chưa
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized. No authenticated user.',
                'status' => 'error'
            ], 401);
        }

        // Kiểm tra role của người dùng
        if ($request->user()->role !== $role) {
            return response()->json([
                'message' => 'Unauthorized. Required role: ' . $role,
                'status' => 'error'
            ], 403);
        }

        // Nếu role hợp lệ, tiếp tục xử lý request
        return $next($request);
    }
}

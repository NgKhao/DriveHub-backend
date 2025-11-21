<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\instance;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:buyer,seller',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng ký thành công!',
            'data' => [
                'userInfo' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token' => $token,
                'type' => 'Bearer',
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Thông tin đăng nhập sai!']]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages(['email' => ['Tài khoản của bạn không hoạt động. Vui lòng liên hệ quản trị viên.']]);
        }
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 'success',
            'message' => 'Đăng nhập thành công!',
            'data' => [
                'userInfo' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token' => $token,
                'type' => 'Bearer',
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Đăng xuất thành công!',
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Thông tin người dùng.',
            'detail' => [
                'id' => $request->user()->id,
                'fullName' => $request->user()->name,
                'email' => $request->user()->email,
                'numberPhone' => $request->user()->phone,
                'role' => $request->user()->role,
                'status' => $request->user()->status,
            ]
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'fullName' => 'sometimes|string|max:255',
            'numberPhone' => 'sometimes|nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $validated['fullName'] ?? $user->name,
            'phone' => $validated['numberPhone'] ?? $user->phone,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật thông tin thành công!',
            'detail' => [
                'id' => $user->id,
                'fullName' => $user->name,
                'email' => $user->email,
                'numberPhone' => $user->phone,
                'role' => $user->role,
                'isActive' => $user->status === 'active',
            ],
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6',
            'newPassword' => 'required|string|min:6|different:password',
        ]);
        $user = $request->user();
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mật khẩu hiện tại không đúng!',
            ], 400);
        }

        $user->update([
            'password' => $validated['newPassword'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đổi mật khẩu thành công!',
        ], 200);
    }
}

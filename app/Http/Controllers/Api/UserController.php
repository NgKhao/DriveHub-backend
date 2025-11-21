<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('pageSize', 10);
        $page = $request->get('pageNumber', 0) + 1;

        $user = User::paginate($perPage, ['*'], 'page', $page);

        $content = collect($user->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtolower($user->role),
                'isActive' => $user->status === 'active',
            ];
        });

        return response()->json([
            'messenger' => 'Lấy danh sách người dùng thành công',
            'status' => 'success',
            'detail' => [
                'content' => $content,
                'pageNumber' => $user->currentPage() - 1,
                'pageSize' => $user->perPage(),
                'totalElements' => $user->total(),
                'totalPages' => $user->lastPage(),
                'first' => $user->onFirstPage(),
                'last' => $user->onLastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Check if user is admin
        if (!Gate::allows('admin-only')) {
            return response()->json([
                'messenger' => 'Truy cập bị từ chối. Cần quyền quản trị.',
                'status' => 'error',
                'detail' => null,
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'fullName' => 'required|string|max:255',
            'numberPhone' => 'required|string|max:20',
            'role' => 'required|in:buyer,seller,admin',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'name' => $validated['fullName'],
            'phone' => $validated['numberPhone'],
            'role' => strtolower($validated['role']),
            'status' => 'active',
            'password' => $validated['password'],
        ]);

        return response()->json([
            'messenger' => 'Tạo người dùng thành công',
            'status' => 'success',
            'detail' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtolower($user->role),
                'isActive' => $user->status === 'active',
            ],
        ], 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'messenger' => 'Lấy thông tin người dùng thành công',
            'status' => 'success',
            'detail' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtolower($user->role),
                'isActive' => $user->status === 'active',
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'fullName' => 'sometimes|string|max:255',
            'numberPhone' => 'sometimes|string|max:20',
            'roleName' => 'sometimes|in:buyer,seller,admin',
            'isActive' => 'sometimes|boolean',
        ]);

        $updateData = [];
        if (isset($validated['fullName'])) {
            $updateData['name'] = $validated['fullName'];
        }
        if (isset($validated['numberPhone'])) {
            $updateData['phone'] = $validated['numberPhone'];
        }
        if (isset($validated['roleName'])) {
            $updateData['role'] = strtolower($validated['roleName']);
        }
        if (isset($validated['isActive'])) {
            $updateData['status'] = $validated['isActive'] ? 'active' : 'inactive';
        }

        $user->update($updateData);

        return response()->json([
            'messenger' => 'Cập nhật người dùng thành công',
            'status' => 'success',
            'detail' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtolower($user->role),
                'isActive' => $user->status === 'active',
            ],
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Check if user is admin or deleting their own account
        if (!Gate::allows('admin-only') && auth()->id() != $id) {
            return response()->json([
                'messenger' => 'Truy cập bị từ chối. Bạn chỉ có thể xóa tài khoản hoặc cần quyền quản trị.',
                'status' => 'Error',
                'detail' => null,
            ], 403);
        }

        $user->delete();

        return response()->json([
            'messenger' => 'Xóa người dùng thành công',
            'status' => 'success',
            'detail' => null,
        ]);
    }
}

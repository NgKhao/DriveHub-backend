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
        $page = $request->get('pageNumber', 0) + 1; // Convert 0-based to 1-based for Laravel

        $user = User::paginate($perPage, ['*'], 'page', $page);

        $content = collect($user->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtoupper($user->role), // Map to uppercase for frontend
                'isActive' => $user->status === 'active',
            ];
        });

        return response()->json([
            'messenger' => 'Users retrieved successfully',
            'status' => 200,
            'detail' => [
                'content' => $content,
                'pageNumber' => $user->currentPage() - 1, // Convert back to 0-based for frontend
                'pageSize' => $user->perPage(),
                'totalElements' => $user->total(),
                'totalPages' => $user->lastPage(),
                'first' => $user->onFirstPage(),
                'last' => $user->onLastPage(),
            ],
            'instance' => 'UserController',
        ]);
    }

    public function store(Request $request)
    {
        // Check if user is admin
        if (!Gate::allows('admin-only')) {
            return response()->json([
                'messenger' => 'Access denied. Admin privileges required.',
                'status' => 403,
                'detail' => null,
                'instance' => 'UserController',
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'fullName' => 'required|string|max:255',
            'numberPhone' => 'required|string|max:20',
            'role' => 'required|in:BUYER,SELLER,ADMIN',
        ]);

        $roleMap = [
            'BUYER' => 'buyer',
            'SELLER' => 'seller',
            'ADMIN' => 'admin',
        ];

        $user = User::create([
            'email' => $validated['email'],
            'name' => $validated['fullName'],
            'phone' => $validated['numberPhone'],
            'role' => $roleMap[$validated['role']],
            'status' => 'active',
            'password' => bcrypt('defaultpassword'), // Default password for admin-created users
        ]);

        return response()->json([
            'messenger' => 'User created successfully',
            'status' => 201,
            'detail' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtoupper($user->role),
                'isActive' => $user->status === 'active',
            ],
            'instance' => 'UserController',
        ], 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'messenger' => 'User retrieved successfully',
            'status' => 200,
            'detail' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtoupper($user->role),
                'isActive' => $user->status === 'active',
            ],
            'instance' => 'UserController',
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'fullName' => 'sometimes|string|max:255',
            'numberPhone' => 'sometimes|string|max:20',
            'roleName' => 'sometimes|in:BUYER,SELLER,ADMIN',
            'isActive' => 'sometimes|boolean',
        ]);

        $roleMap = [
            'BUYER' => 'buyer',
            'SELLER' => 'seller',
            'ADMIN' => 'admin',
        ];

        $updateData = [];
        if (isset($validated['fullName'])) {
            $updateData['name'] = $validated['fullName'];
        }
        if (isset($validated['numberPhone'])) {
            $updateData['phone'] = $validated['numberPhone'];
        }
        if (isset($validated['roleName'])) {
            $updateData['role'] = $roleMap[$validated['roleName']];
        }
        if (isset($validated['isActive'])) {
            $updateData['status'] = $validated['isActive'] ? 'active' : 'inactive';
        }

        $user->update($updateData);

        return response()->json([
            'messenger' => 'User updated successfully',
            'status' => 200,
            'detail' => [
                'id' => $user->id,
                'email' => $user->email,
                'fullName' => $user->name,
                'numberPhone' => $user->phone,
                'role' => strtoupper($user->role),
                'isActive' => $user->status === 'active',
            ],
            'instance' => 'UserController',
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Check if user is admin or deleting their own account
        if (!Gate::allows('admin-only') && auth()->id() != $id) {
            return response()->json([
                'messenger' => 'Access denied. You can only delete your own account or admin privileges required.',
                'status' => 403,
                'detail' => null,
                'instance' => 'UserController',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'messenger' => 'User deleted successfully',
            'status' => 200,
            'detail' => null,
            'instance' => 'UserController',
        ]);
    }
}

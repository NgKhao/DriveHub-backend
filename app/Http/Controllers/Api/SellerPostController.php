<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SellerPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $posts = $request->user()->posts()->latest()->paginate(10);
        return response()->json(
            [
                'message' => 'Danh sách bài đăng của người bán.',
                'status' => 'success',
                'detail' => [
                    'posts' => PostResource::collection($posts->items()),
                    'pagination' => [
                        'currentPage' => $posts->currentPage(),
                        'lastPage' => $posts->lastPage(),
                        'perPage' => $posts->perPage(),
                        'total' => $posts->total(),
                    ],
                ]

            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string',
            'mileage' => 'required|integer|min:0',
            'location' => 'required|string',
            'phoneContact' => 'required|string',
            'transmission' => 'required|in:manual,automatic',
            'fuelType' => 'required|in:gasoline,diesel,electric,hybrid',
            'condition' => 'required|in:new,used',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg',
        ]);

        // Upload images (nếu có)
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('posts', $filename, 'public');
                $imagePaths[] = Storage::url($path);
            }
        }

        // Tạo bài đăng mới
        $post = Post::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'year' => $validated['year'],
            'color' => $validated['color'],
            'mileage' => $validated['mileage'],
            'location' => $validated['location'],
            'phone_contact' => $validated['phoneContact'],
            'transmission' => $validated['transmission'],
            'fuel_type' => $validated['fuelType'],
            'condition' => $validated['condition'],
            'images' => $imagePaths,
            'status' => 'draft', // Chờ thanh toán
        ]);

        return response()->json(
            [
                'message' => 'Bài đăng đã được tạo, vui lòng thanh toán.',
                'status' => 'success',
                'detail' => [
                    'post' => new PostResource($post),
                    'paymentUrl' => route('payments.create', $post->id),
                ],
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        //
        if ($post->user_id !== request()->user()->id) {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập bài đăng này.',
                'status' => 'error',
            ], 403);
        }

        return response()->json(
            [
                'message' => 'Chi tiết bài đăng.',
                'status' => 'success',
                'detail' => [
                    'post' => new PostResource($post),
                ],
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //

        // Kiểm tra quyền sở hữu
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bạn không có quyền chỉnh sửa bài đăng này.',
                'status' => 'error',
            ], 403);
        }

        // Chỉ cho sửa khi bài đăng ở trạng thái 'draft' hoặc 'rejected', 'pending'
        if (!in_array($post->status, ['pending', 'rejected', 'draft'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể sửa bài đã được duyệt',
            ], 422);
        }

        // Validate
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'brand' => 'sometimes|string',
            'model' => 'sometimes|string',
            'year' => 'sometimes|integer|min:1900',
            'color' => 'sometimes|string',
            'mileage' => 'sometimes|integer|min:0',
            'location' => 'sometimes|string',
            'phoneContact' => 'sometimes|string',
            'transmission' => 'sometimes|in:manual,automatic',
            'fuelType' => 'sometimes|in:gasoline,diesel,electric,hybrid',
            'condition' => 'sometimes|in:new,used',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg',
        ]);

        // Xử lý upload ảnh mới (nếu có)
        $imagePaths = $post->images ?? [];
        if ($request->hasFile('images')) {

            // Xóa ảnh cũ
            foreach ($post->images as $oldImage) {
                $path = str_replace('/storage/', '', $oldImage);
                Storage::disk('public')->delete($path);
            }

            $imagePaths = [];
            // Upload ảnh mới
            foreach ($request->file('images') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('posts', $filename, 'public');
                $imagePaths[] = Storage::url($path);
            }
            $validated['images'] = $imagePaths;
        }

        if (isset($validated['fuelType'])) {
            $validated['fuel_type'] = $validated['fuelType'];
            unset($validated['fuelType']);
        }

        if (isset($validated['phoneContact'])) {
            $validated['phone_contact'] = $validated['phoneContact'];
            unset($validated['phoneContact']);
        }

        $post->update($validated);

        return response()->json([
            'message' => 'Bài đăng đã được cập nhật.',
            'status' => 'success',
            'detail' => [
                'post' => new PostResource($post),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //
        if ($post->user_id !== request()->user()->id) {
            return response()->json([
                'message' => 'Bạn không có quyền xóa bài đăng này.',
                'status' => 'error',
            ], 403);
        }
        // Xóa ảnh vật lý khi xóa bài đăng
        foreach ($post->images as $image) {
            $path = str_replace('/storage/', '', $image);
            Storage::disk('public')->delete($path);
        }

        $post->delete();

        return response()->json([
            'message' => 'Bài đăng đã được xóa.',
            'status' => 'success',
        ]);
    }
}

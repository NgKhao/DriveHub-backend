<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class AdminPostController extends Controller
{
    //
    public function index()
    {
        $posts = Post::latest()->paginate(10);
        return response()->json(
            [
                'message' => 'Danh sách tất cả bài đăng.',
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

    public function show(Post $post)
    {
        return response()->json([
            'message' => 'Chi tiết bài đăng.',
            'status' => 'success',
            'detail' => new PostResource($post->load('user')),
        ]);
    }

    public function updateStatus(Request $request, Post $post)
    {
        //
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $post->update([
            'status' => $validated['status'],
        ]);


        return response()->json([
            'message' => 'Cập nhật trạng thái bài đăng thành công.',
            'status' => 'success',
            'detail' => new PostResource($post->load('user')),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PublicPostController extends Controller
{
    //
    public function index()
    {
        //
        $posts = Post::where('status', 'approved')
            ->paginate(10);
        return response()->json(
            [
                'message' => 'Danh sách bài đăng.',
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
        //

        $post = Post::where('id', $post->id)
            ->where('status', 'approved')
            ->first();

        if (!$post) {
            return response()->json([
                'message' => 'Bài đăng không tồn tại hoặc chưa được phê duyệt.',
                'status' => 'error',
            ], 404);
        }

        return response()->json([
            'message' => 'Chi tiết bài đăng.',
            'status' => 'success',
            'detail' => new PostResource($post->load('user')),
        ]);
    }
}

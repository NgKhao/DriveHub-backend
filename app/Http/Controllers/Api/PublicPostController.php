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

    // Tìm kiếm và lọc bài đăng
    public function search(Request $request)
    {
        $query = Post::where('status', 'approved');

        if($request->filled('keyword')){ //tồn tại và không rỗng
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword){ //group các điều kiện query lại (), sử dụng or phải có function()
                // để group lại
                $q->where('title', 'like', "%$keyword%")
                    ->orWhere('brand', 'like', "%$keyword%")
                    ->orWhere('model', 'like', "%$keyword%")
                    ->orWhere('description', 'like', "%$keyword%");
            });
        }

         // Lọc theo brand (hãng xe)
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

         // Lọc theo giá
        if ($request->filled('minPrice')) {
            $query->where('price', '>=', $request->minPrice);
        }
        if ($request->filled('maxPrice')) {
            $query->where('price', '<=', $request->maxPrice);
        }

         // Lọc theo tình trạng (new/used)
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        $posts = $query->paginate(10);

         return response()->json([
            'message' => 'Kết quả tìm kiếm.',
            'status' => 'success',
            'detail' => [
                'posts' => PostResource::collection($posts->items()),
                'pagination' => [
                    'currentPage' => $posts->currentPage(),
                    'lastPage' => $posts->lastPage(),
                    'perPage' => $posts->perPage(),
                    'total' => $posts->total(),
                ]
            ]
        ]);
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

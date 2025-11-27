<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Services\PostService;
use App\Http\Resources\PostResource;

class PublicPostsController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function index(Request $request)
    {
        $posts = Post::where('status', 'approved')
            ->paginate(10);

        return response()->json([
            'message' => 'Danh sách bài đăng',
            'status' => 'success',
            'detail' => [
                'content' => PostResource::collection($posts),
                'pageNumber' => $posts->currentPage() - 1,
                'pageSize' => $posts->perPage(),
                'totalElements' => $posts->total(),
                'totalPages' => $posts->lastPage(),
                'first' => $posts->onFirstPage(),
                'last' => $posts->hasMorePages() === false,
            ],
        ], 200);
    }

    public function show($id)
    {
        $post = Post::with('user')->where('status','approved')
        ->findOrFail($id);
            return response()->json([
                'message' => 'Chi tiết bài đăng',
                'status' => 'success',
                'detail' => new PostResource($post),
            ], 200);
        }

     public function search(Request $request)
    {
        //Gui toan bo Request sang Service de xu ly
        $posts = $this->postService->getPublicPosts($request->all(),
            $request->get('limit',10));

        return response()->json([
            'message' => 'Kết quả tìm kiếm bài đăng',
            'status' => 'success',
            'detail' => [
                'content' => PostResource::collection($posts),
                'pageNumber' => $posts->currentPage() - 1,
                'pageSize' => $posts->perPage(),
                'totalElements' => $posts->total(),
                'totalPages' => $posts->lastPage(),
                'first' => $posts->onFirstPage(),
                'last' => $posts->hasMorePages() === false,
            ],
        ], 200);
    }

    // Lay danh sach bai dang theo SellerId
    public function getBySeller(Request $request, int $sellerId)
    {
        $posts = $this->postService->getPostsBySeller($sellerId, $request
            ->get('limit', 10));
        return response()->json([
            'message' => 'Danh sách bài đăng của người bán.',
            'status' => 'success',
            'detail' => PostResource::collection($posts),
        ], status: 200);
    }

    public function getFeatured()
    {
        $posts = $this->postService->getFeaturedPosts(5);
        return response()->json([
            'message' => 'Bài đăng nổi bật',
            'status' => 'success',
            'detail' => PostResource::collection($posts),
        ], 200);
    }

    public function getBrands()
    {
        $brands = $this->postService->getDistinctBrands();

        return response()->json([
            'message' => 'Danh sách nhãn hiệu',
            'status' => 'success',
            'detail' => $brands,
        ], 200);
    }

    public function getModels(Request $request)
    {
        $brand = $request->get('brand');
        if (!$brand) {
            return response()->json([
                'message' => 'Thiếu tên mẫu xe',
                'status' => 'error',
                'detail' => null,
            ], 400);
        }

        $models = $this->postService->getDistinctModels($brand);

        return response()->json([
            'message' => 'Danh sách các mẫu xe',
            'status' => 'success',
            'detail' => $models,
        ], 200);
    }
}

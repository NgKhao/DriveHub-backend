<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AdminService;
use App\Models\Post;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Log;

class AdminPostsController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
        $this->adminService = $adminService;
    }

    // Phe duyet bai dang
    public function approve(Request $request, $id)
    {
        try {
            $adminId = $request->user()->id;
            $post = $this->adminService->approvePost($id, $adminId);

            return response()->json([
                'message' => 'Bài đăng đã được phê duyệt',
                'status' => 'success',
                'detail' => new PostResource($post),
                ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'   => 'Bài đăng không tồn tại.',
                'status'    => 'error'
            ], 400);
        }
    }

    // Tu choi bai dang
    public function reject(Request $request, int $id)
    {
        $validated = $request->validate(['reason' => 'required|string|max:500']);

        try {
            $adminId = $request->user()->id();
            $posts = $this->adminService->rejectPost($id, $adminId);

            return response()->json([
                'message' => 'Bài đăng đã bị từ chối',
                'status' => 'error',
                'detail' => new PostResource($posts),
                ],200);

        } catch (\Exception $e) {
            return response()->json([
                'message'   => 'Bài đăng không tồn tại.',
                'status'    => 'error'
            ], 400);
        }
    }

    // Lay danh sach tat ca bai dang (Co the loc theo status)
    public function getAllPosts(Request $request)
    {
        $posts = $this->adminService->getAllPosts($request->query('status'), 10);

        return response()->json([
            'message' => 'Danh sách bài đăng theo trạng thái',
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

    public function getPostById($id)
    {
        try {
            $post = Post::findOrFail($id);

            return response()->json([
                'message' => 'Chi tiết bài đăng',
                'status' => 'success',
                'detail' => new PostResource($post),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'   => 'Bài đăng không tồn tại.',
                'status'    => 'error'
            ], 400);
        }
    }

    // Cap nhat trang thai
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,pending,approved,rejected',
        ]);

        try {
            $post = $this->adminService->updatePostStatus($id, $request->input('status'));

            return response()->json([
                'message' => 'Trạng thái bài đăng đã được cập nhật',
                'status' => 'success',
                'detail' => new PostResource($post),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'   => 'Không thể cập nhật trạng thái.',
                'status'    => 'error'
            ], 400);
        }
    }

    // Xoa bai dang
    public function deletePost($id)
    {
        try {
            $this->adminService->deletePost($id);

            return response()->json([
                'message'   => 'Bài đăng đã được xóa bởi quản trị viên.',
                'status'    => 'success',
                'detail'    => null,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Cụ thể hóa lỗi cho trường hợp không tìm thấy (404)
            return response()->json([
                'message'   => 'Bài đăng không tồn tại.',
                'status'    => 'error'
            ], 404);
        } catch (\Exception $e) {
             // QUAN TRỌNG: Ghi log chi tiết để theo dõi lỗi 500
            Log::error("Admin Delete Error for Post ID {$id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Trả về lỗi 500 chung nếu không phải là ModelNotFound
            return response()->json([
                'message'   => 'Đã xảy ra lỗi máy chủ khi xóa bài đăng.',
                'status'    => 'error'
            ], 500);
        }
    }
}

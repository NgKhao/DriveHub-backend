<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PostService;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Log;
use App\Models\Post;

class SellerPostsController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->middleware(['auth:sanctum', 'role:seller']);
        $this->postService = $postService;
    }

    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'price'        => 'required|numeric|min:0',
            'brand'        => 'required|string', // Frontend gửi make
            'model'        => 'required|string',
            'year'         => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color'        => 'required|string',
            'mileage'      => 'required|integer|min:0',
            'location'     => 'required|string',
            'phone_contact' => 'required|string',
            'transmission' => 'required|in:manual,automatic',
            'fuelType'     => 'required|in:gasoline,diesel,electric,hybrid',
            'condition'    => 'required|in:new,used',
            'images'       => 'nullable|array',
            'images.*'     => 'image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
            'sellerType'   => 'required|in:individual,agency'
        ]);


        try {
            // Goi service
            $post = $this->postService->createPost($validated, $request->user());
            //$paymentUrl = $this->postService->generatePaymentURL($post);

            // Tra ve respone
            return response()->json([
            'message' => 'Bài đăng đã được tạo, vui lòng thanh toán.',
            'status' => 'success',
            'detail' => [
                'post'      => new PostResource($post),
                //'vnpayUrl'  => $paymentUrl,
            ],
            ],201);

        } catch (\Exception $e) {
            // Log::error("Post creation failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // TRẢ VỀ LỖI CHI TIẾT ĐỂ DEBUG
            return response()->json([
                'message' => 'Lỗi server (Debug Mode).',
                'status' => 'error',
                'detail' => $e->getMessage(), // <--- TRẢ VỀ THÔNG ĐIỆP LỖI THỰC TẾ
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Validation
        $validated = $request->validate([
            'title'          => 'sometimes|required|string|max:255',
            'description'    => 'sometimes|required|string',
            'price'          => 'sometimes|required|numeric',
            'brand'          => 'sometimes|required|string',
            'model'          => 'sometimes|required|string',
            'year'           => 'sometimes|required|integer',
            'color'          => 'sometimes|required|string',
            'mileage'        => 'sometimes|required|integer',
            'location'       => 'sometimes|required|string',
            'phone_contact'  => 'sometimes|required|string',
            'transmission'   => 'sometimes|required|in:manual,automatic',
            'fuel_type'      => 'sometimes|required|in:petrol,diesel,electric,hybrid',
            'condition'      => 'sometimes|required|in:new,used',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try{
            // Goi Service xu ly cap nhat(check quyen)
            $post = $this->postService->updatePosts($id, $validated, $request->user()->id);

            // Return Response
            return response()->json([
                'message' => 'Cập nhật bài đăng thành công.',
                'status' => 'success',
                'detail' => new PostResource($post),
            ], 200);

        } catch (\Exception $e) {
            // Xu ly Not Found hoac Khong co quyen
            return response()->json([
                'message'   => 'Bài đăng không tồn tại hoặc bạn không có quyền.',
                'status'    => 'error'
            ], 404);
        }
    }

    public function destroy(int $id)
    {
        try{
            $this->postService->deletePost($id, auth()->id());

            return response()->json([
                'message' => 'Bài đăng đã được xóa thành công.',
                'status' => 'error',
                'detail' => null,
            ], 200);

        }catch (\Exception $e) {
            // Xu ly Not Found hoac Khong co quyen
            return response()->json([
                'message'   => 'Bài đăng không tồn tại hoặc bạn không có quyền.',
                'status'    => 'error'
            ], 404);
        }
    }

    public function showMyCar(int $id)
    {
        try{
            //Lay bai dang
            $post = Post::where('id', $id)
                        ->where('user_id', auth()->id())
                        ->firstOrFail();
            return response()->json([
                'message' => 'Chi tiết bài đăng của bạn.',
                'status' => 'success',
                'detail' => new PostResource($post),
        ], 200);

        } catch (\Exception $e) {
            // Xu ly Not Found hoac Khong co quyen
            return response()->json([
                'message'   => 'Bài đăng không tồn tại.',
                'status'    => 'error'
            ], 404);
        }
    }
    public function getMyCars(Request $request)
    {
        // Lay bai dang cua user dang dang nhap(phan trang)
        $posts = $request->user()->posts()->paginate(10);

        return response()->json([
            'message' => 'Danh sách bài đăng của bạn.',
            'status' => 'success',
            'detail' => PostResource::collection($posts),
        ], 200);
    }

    public function contactSeller(Request $request)
    {
        $validated = $request->validate([
            'buyerName'  => 'required|string',
            'buyerPhone' => 'required|string',
            'buyerEmail' => 'required|email',
            'message'    => 'required|string',
            'carId'      => 'required|integer',
        ]);

        // Goi mot Contact/Notif Service gui email/notif

        return response()->json([
            'message' => 'Tin nhắn đã được gửi đến người bán',
            'status' => 'success',
            'detail' => null,
        ], 200);
    }

}

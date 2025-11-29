<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Post;
use App\Services\VNPayService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
    public function __construct(
        private VNPayService $vnpayService
    ) {}

    // Tạo payment và trả về VNPay URL
    public function create(Request $request, Post $post)
    {

        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bạn không có quyền thanh toán cho bài đăng này.',
                'status' => 'error',
            ], 403);
        }

        // Kiểm tra status
        if ($post->status !== 'draft') {
            return response()->json([
                'message' => 'Chỉ có thể thanh toán cho bài đăng ở trạng thái nháp.',
                'status' => 'error',
            ], 422);
        }

        $amount = 50000;

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'vnpay',
            'transaction_id' => 'POST_' . $post->id . '_' . time(),

        ]);

        $vnpayUrl = $this->vnpayService->createPaymentUrl($post->id, $amount);

        return response()->json([
            'message' => 'Vui lòng thanh toán để hoàn tất.',
            'status' => 'success',
            'detail' => [
                'payment_id' => $payment->id,
                'vnpay_url' => $vnpayUrl,
                'amount' => $amount,
            ],
        ]);
    }

    // Xử lý callback từ VNPay
    public function vnpayReturn(Request $request)
    {
        $inputData = $request->all();

        // Verify
        if (!$this->vnpayService->verifyReturn($inputData)) {
            return $this->redirectFrontend('failed', 'Chữ ký không hợp lệ');
        }

        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';

        $postId = $this->vnpayService->getPostIdFromTxnRef($vnp_TxnRef);

        if (!$postId) {
            return $this->redirectFrontend('failed', 'Không tìm thấy bài đăng');
        }

        $post = Post::find($postId);
        if (!$post) {
            return $this->redirectFrontend('failed', 'Không tìm thấy bài đăng');
        }

        $isSuccess = $vnp_ResponseCode === '00';

        // Cập nhật payment
        $payment = Payment::where('post_id', $post->id)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $payment->update([
                'status' => $isSuccess ? 'success' : 'failed',
                'transaction_id' => $vnp_TxnRef,
            ]);
        }

        // Cập nhật post
        if ($isSuccess) {
            $post->update(['status' => 'pending']);
        }

        // Redirect về frontend
        return $this->redirectFrontend(
            $isSuccess ? 'success' : 'failed',
            $isSuccess ? 'Thanh toán thành công' : 'Thanh toán thất bại'
        );
    }

    /**
     * Redirect về frontend
     */
    private function redirectFrontend(string $status, string $message)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $url = "{$frontendUrl}/seller/posts?payment={$status}&message=" . urlencode($message);

        return redirect($url);
    }
}

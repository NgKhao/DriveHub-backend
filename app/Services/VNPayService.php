<?php

namespace App\Services;

class VNPayService
{
    /**
     * Tạo URL thanh toán VNPay
     */
    public function createPaymentUrl(int $postId, float $amount): string
    {
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_ReturnUrl = env('VNPAY_RETURN_URL');

        $vnp_TxnRef = 'POST_' . $postId . '_' . time(); // Mã giao dịch
        $vnp_OrderInfo = 'Thanh toan dang bai ID: ' . $postId;
        $vnp_Amount = $amount * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = "";
        $hashdata = "";
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        return $vnp_Url;
    }

    /**
     * Xác thực callback từ VNPay
     */
    public function verifyReturn(array $inputData): bool
    {
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        $inputDataFiltered = [];
        foreach ($inputData as $key => $value) {
            if (strpos($key, 'vnp_') === 0 && $key !== 'vnp_SecureHash') {
                $inputDataFiltered[$key] = $value;
            }
        }

        ksort($inputDataFiltered);

        $hashData = "";
        $i = 0;
        foreach ($inputDataFiltered as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET'));

        return $secureHash === $vnp_SecureHash;
    }

    /**
     * Lấy Post ID từ vnp_TxnRef
     */
    public function getPostIdFromTxnRef(string $txnRef): ?int
    {
        // Format: POST_123_1234567890
        if (preg_match('/POST_(\d+)_/', $txnRef, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}

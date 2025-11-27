<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
class PostService
{
    //Tao bai viet moi, upload anh
    public function createPost(array $data, $user): Post
    {
        return DB::transaction(function () use ($data, $user) {
            // Map du lieu frontend
            $fuelMap = ['gasoline' => 'petrol', 'diesel' => 'diesel', 'hybrid' => 'hybrid', 'electric' => 'electric'];
            $transmissionMap = ['automatic' => 'automatic', 'manual' => 'manual'];
            $conditionMap = ['new' => 'new', 'used' => 'used'];

            // Xu ly load anh
            $imagePaths = [];
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $file) {
                    //Kiem tra file hop le
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        //Luu vao storage/app/public/posts
                        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('post', $filename, 'public');
                        $imagePaths[] = '/storage' . $path;
                    }
                }
            }

            //Chuan bi data de insert
            $postData = [
                'user_id'       => $user->id,
                'title'         => $data['title'],
                'description'   => $data['description'],
                'price'         => $data['price'],
                'brand'         => $data['brand'], // Frontend gửi 'make', DB là 'brand'
                'model'         => $data['model'],
                'year'          => $data['year'],
                'color'         => $data['color'],
                'mileage'       => $data['mileage'],
                'location'      => $data['location'],
                'phone_contact' => $data['phone_contact'], // Frontend gửi camelCase
                'transmission'  => $transmissionMap[$data['transmission']] ?? 'automatic',
                'fuel_type'     => $fuelMap[$data['fuelType']] ?? 'petrol',
                'condition'     => $conditionMap[$data['condition']] ?? 'used',
                'images'        => json_encode($imagePaths),
                'status'        => 'draft', // Mặc định là draft chờ thanh toá
            ];
            return Post::create($postData);
        });
    }
    public function updatePosts(int $postId, array $data, int $userId): Post
    {
        $post = Post::where('id', $postId)
                    ->where('user_id', $userId) //Kiem tra quyen so huu
                    ->firstOrFail();
         //TODO: xu ly upload/xoa anh ...

         $post->fill($data);
         $post->save();

         return $post;
     }

    public function deletePost(int $postId, int $userId): bool
    {
        $post = Post::where('id', $postId)
                    ->where('user_id', $userId) // Kiem tra quyen so huu
                    ->firstOrFail();
        //TODO: Xu ly xoa anh vat ly khoi storage

        return $post->delete();
    }

    public function getPublicPosts(array $filters = [], int $perPage = 10)
    {
        $query = Post::where('status','approved')
            //Dam bao nguoi ban dang hoat dong
            ->whereHas('user', fn($q) => $q->where('status', 'active'));

        // Bo sung cac filter
        if (!empty($filters['brabd'])) {
            $query->where('brand', $filters['brand']);
        }
        if (!empty($filters['model'])) {
            $query->where('model', $filters['model']);
        }
        if (!empty($filters['minPrice'])) {
            $query->where('price', '>=', $filters['minPrice']);
        }
        if (!empty($filters['maxPrice'])) {
            $query->where('price', '<=', $filters['maxPrice']);
        }
        if (!empty($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }

        // Ap dung phan trang
        return $query->paginate($perPage);
    }

    // Lay danh sach bai dang theo ID
    public function getPostsBySeller(int $sellerId, int $perPage = 10)
    {
        // Tra ve bai da duoc APPROVED và nguoi ban ACTIVE
        return Post::where('user_id', $sellerId)
            ->where('status', 'approved')
            ->whereHas('user', fn($q) => $q->where('status', 'active'))
            ->paginate($perPage);
    }

    // Lay danh sach bai dang noi bat (sap xep theo luot xem/moi nhat)
    public function getFeaturedPosts(int $limit = 5)
    {
        //5 bai viet co luot xem cao nhat hoac moi nhat
        return Post::where('status', 'approved')
            ->whereHas('user', fn($q) => $q->where('status', 'active'))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    // Lay Distinct tu Brands
    public function getDistinctBrands()
    {
        return Post::where('status', 'approved')
            ->whereHas('user', fn($q) => $q-> where('status', 'active'))
            ->select('brand')
            ->distinct()
            ->pluck('brand');
    }

    // Lay Distinct Models theo Brand
    public function getDistinctModels(string $brand)
    {
        return Post::where('brand', $brand)
            ->where('status', 'approved')
            ->whereHas('user', fn($q) => $q->where('status', 'active'))
            ->select('model')
            ->distinct()
            ->pluck('model');
    }

    //Link thanh toan payment
    public function generatePaymentURL(Post $post): string
    {
        //vnpay service...
        return 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?mock=payment_url&post_id=' . $post->id;
    }
}

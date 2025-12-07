<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Favorite;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // Hien thi danh sach yeu thich cua nguoi dung
    public function index()
    {
        try {
            $user = Auth::user();

            $favorites = Favorite::where('user_id', $user->id)
                ->with('post.user')
                ->get();

            $favoriteItems = $favorites->map(function ($favorite) {
                return [
                    'favoriteId' => $favorite->id,
                    'post'       => new PostResource($favorite->post),
                ];
            });

            return response()->json([
                'messenger' => 'Lấy danh sách yêu thích thành công',
                'status'    => 'success',
                'detail'    => $favoriteItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message'  => 'Có lỗi xảy ra khi lấy danh sách yêu thích',
                'status'   => 'error',
                'detail'   => null,
            ], 500);
        }
    }

    // Them bai dang vao danh sach yeu thich
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $postId = $request->input('postId');

            // Validate postId is provided
            if (!$postId) {
                return response()->json([
                    'message'  => 'postId là bắt buộc',
                    'status'   => 'error',
                    'detail'   => null,
                ], 400);
            }

            $post = Post::where('id', $postId)
                ->where('status', 'approved')
                ->with('user')
                ->first();

            if (!$post) {
                return response()->json([
                    'message'  => 'Bài đăng không tồn tại hoặc không khả dụng',
                    'status'   => 'error',
                    'detail'   => null,
                ], 404);
            }

            $existingFavorite = Favorite::where('user_id', $user->id)
                ->where('post_id', $postId)
                ->first();

            if ($existingFavorite) {
                return response()->json([
                    'message'  => 'Bài đăng đã nằm trong danh sách yêu thích.',
                    'status'   => 'error', // dùng 409 Conflict thay vì 400
                    'detail'   => null,
                ], 409);
            }

            $favorite = Favorite::firstOrCreate([
                'user_id' => $user->id,
                'post_id' => $postId,
            ]);

            $favoriteItem = [
                'favoriteId' => $favorite->id,
                'post'       => new PostResource($post),
            ];

            return response()->json([
                'message'  => 'Thêm vào yêu thích thành công',
                'status'   => 'success',
                'detail'   => $favoriteItem,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Favorite store error: '.$e->getMessage(), [
        'trace' => $e->getTraceAsString()
         ]);

            return response()->json([
                'message'  => 'Có lỗi xảy ra khi thêm vào yêu thích',
                'status'   => 'error',
                'detail'   => null,
            ], 500);
        }
    }

    // Xoa bai dang khoi danh sach yeu thich.
    public function destroy($postId)
    {
        try {
            $user = Auth::user();

            $favorite = Favorite::where('user_id', $user->id)
                ->where('post_id', $postId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'message'  => 'Bài đăng không có trong danh sách yêu thích',
                    'status'   => 'error',
                    'detail'   => null,
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'message'  => 'Xóa khỏi yêu thích thành công',
                'status'   => 'success',
                'detail'   => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message'  => 'Có lỗi xảy ra khi xóa khỏi yêu thích',
                'status'   => 'error',
                'detail'   => null,
            ], 500);
        }
    }
}

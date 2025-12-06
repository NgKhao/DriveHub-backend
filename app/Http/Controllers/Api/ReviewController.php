<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    //
    public function index(Request $request, $sellerId)
    {
        //
        $seller = User::findOrFail($sellerId);

        $reviews = $seller->reviewsReceived()->with('user')->paginate(10);

        $averageRating = $seller->reviewsReceived()->avg('rating');
        $totalReviews = $seller->reviewsReceived()->count();

        return response()->json(
            [
                'message' => 'Danh sách đánh giá cho người bán.',
                'status' => 'success',
                'detail' => [
                    'seller' => [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'averageRating' => round($averageRating, 2),
                        'totalReviews' => $totalReviews,
                    ],
                    'reviews' => ReviewResource::collection($reviews->items()),
                    'pagination' => [
                        'currentPage' => $reviews->currentPage(),
                        'lastPage' => $reviews->lastPage(),
                        'perPage' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ],
                ]

            ]
        );
    }

    public function store(Request $request, $sellerId)
    {
        //
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $seller = User::findOrFail($sellerId);

        if($request->user()->id === $seller->id){
            return response()->json([
                'message' => 'Bạn không thể tự đánh giá chính mình.',
                'status' => 'error',
            ], 403);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'seller_id' => $sellerId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? '',
        ]);

        return response()->json([
            'message' => 'Đánh giá đã được gửi thành công.',
            'status' => 'success',
            'detail' => new ReviewResource($review->load('user', 'seller')),
        ]);
    }

}

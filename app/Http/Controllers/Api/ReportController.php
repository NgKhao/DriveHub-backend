<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Buyer report Seller về một bài đăng cụ thể
     * POST /posts/{postId}/report
     */
    public function reportPost(Request $request, $postId)
    {
        //
        $post = Post::findOrFail($postId);

        if($request->user()->id === $post->user_id){
            return response()->json([
                'message' => 'Bạn không thể báo cáo bài đăng của chính mình.',
                'status' => 'error',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $post->user_id,
            'post_id' => $post->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Báo cáo đã được gửi thành công.',
            'status' => 'success',
            'reportId' =>  new ReportResource($report->load('reporter', 'reportedUser', 'post')),
        ], 201);
    }

      /**
     * Seller report Buyer bằng email hoặc phone (không cần post_id)
     * POST /reports/report-buyer
     */
    public function reportBuyer(Request $request){
        $validated = $request->validate([
            'buyer_identifier' => 'required|string', // email hoặc phone
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $indentifier = $validated['buyer_identifier'];

        $buyer = User::where('email', $indentifier)
                ->orWhere('phone', $indentifier)
                ->first();

        if(!$buyer){
            return response()->json([
                'message' => 'Không tìm thấy người dùng với email hoặc số điện thoại này.',
                'status' => 'error',
            ], 404);
        }

        if($request->user()->id === $buyer->id){
            return response()->json([
                'message' => 'Bạn không thể báo cáo chính mình.',
                'status' => 'error',
            ], 400);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $buyer->id,
            'post_id' => null,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Báo cáo đã được gửi thành công.',
            'status' => 'success',
            'reportId' =>  new ReportResource($report->load('reporter', 'reportedUser')),
        ], 201);
    }

    /**
     * Lấy danh sách reports của user hiện tại (đã tạo)
     * GET /my-reports
     */
    public function myReports(Request $request)
    {
        //
        $reports = Report::where('reporter_id', $request->user()->id)
                    ->with('reporter', 'reportedUser', 'post')
                    ->latest()
                    ->paginate(10);

        return response()->json([
            'message' => 'Danh sách báo cáo của bạn.',
            'status' => 'success',
            'detail' => [
                'reports' => ReportResource::collection($reports->items()),
                'pagination' => [
                    'currentPage' => $reports->currentPage(),
                    'lastPage' => $reports->lastPage(),
                    'perPage' => $reports->perPage(),
                    'total' => $reports->total(),
                ],
            ],
        ]);
    }

     /**
     * Admin: Lấy tất cả reports
     * GET /admin/reports
     */
    public function index(Request $request)
    {
        $reports = Report::with('reporter', 'reportedUser', 'post')
                    ->latest()
                    ->paginate(10);

        return response()->json([
            'message' => 'Danh sách tất cả báo cáo.',
            'status' => 'success',
            'detail' => [
                'reports' => ReportResource::collection($reports->items()),
                'pagination' => [
                    'currentPage' => $reports->currentPage(),
                    'lastPage' => $reports->lastPage(),
                    'perPage' => $reports->perPage(),
                    'total' => $reports->total(),
                ],
            ],
        ]);
    }

     /**
     * Admin: Xem chi tiết report
     * GET /admin/reports/{reportId}
     */
    public function show($reportId)
    {
        $report = Report::with('reporter', 'reportedUser', 'post')->findOrFail($reportId);
        return response()->json([
            'message' => 'Chi tiết báo cáo.',
            'status' => 'success',
            'detail' => new ReportResource($report),
        ]);
    }

    // Admin: Cập nhật trạng thái report
    // PATCH /admin/reports/{reportId}/status
    public function updateStatus(Request $request, $reportId)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,resolved,dismissed',
        ]);

        $report = Report::findOrFail($reportId);
        $report->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Cập nhật trạng thái báo cáo thành công.',
            'status' => 'success',
            'detail' => new ReportResource($report->load('reporter', 'reportedUser', 'post')),
        ]);
    }

    //  Xóa report (Admin hoặc người tạo)
    // DELETE /reports/{reportId}
    public function destroy(Request $request, $reportId)
    {
        $report = Report::findOrFail($reportId);

        // Kiểm tra quyền: Admin hoặc người tạo report
        if($request->user()->role !== 'admin' && $request->user()->id !== $report->reporter_id){
            return response()->json([
                'message' => 'Bạn không có quyền xóa báo cáo này.',
                'status' => 'error',
            ], 403);
        }

        $report->delete();

        return response()->json([
            'message' => 'Báo cáo đã được xóa thành công.',
            'status' => 'success',
        ]);
    }
}

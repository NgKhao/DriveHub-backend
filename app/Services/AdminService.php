<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class AdminService
{
    // Duyet bai dang
    public function approvePost(int $postId, int $adminId): Post
    {
        $post = Post::findOrFail($postId);
        $post->status = 'approved';
        $post->save();

        //TODO: gui email/notif cho nguoi ban

        return $post;
    }
    //
    public function rejectPost(int $postId, int $adminId): Post
    {
        $post = Post::findOrFail($postId);
        $post->status = 'rejected';
        $post->rejected_by = $adminId;
        $post->save();

        //TODO: gui email/notif cho nguoi ban

        return $post;
    }

    // Lay tat ca bai dang (loc theo status)
    public function getAllPosts(?string $status = null, int $perPage = 10)
    {
        $query = Post::query();
        if ($status) {
            $query->where('status', strtolower($status));
        }
        return $query->with('user')->paginate($perPage);
    }

    // Cap nhat trang thai
    public function updatePostStatus(int $postId, string $status): Post
    {
        $post = Post::findOrFail($postId);
        $post->status = $status;
        $post->save();

        return $post;
    }

    // Xoa bai dang
    public function deletePost(int $postId): bool
    {
        return DB::transaction(function () use ($postId){
             $post = Post::findOrFail($postId);


        //TODO: xu ly xoa anh khoi storage

        return $post->delete();
        });
    }
}

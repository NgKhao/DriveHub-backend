<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('clerk_id')->unique();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('role', ['admin', 'buyer', 'seller'])->default('buyer');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('clerk_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
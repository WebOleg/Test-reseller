<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('api_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_users');
    }
};

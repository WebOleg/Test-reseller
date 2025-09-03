<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'charge', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->string('reference_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->timestamps();
            
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['sub_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->decimal('order_cost', 10, 2);
            $table->string('order_status')->default('on_hold');
            $table->unsignedBigInteger('user_id');
            $table->string('user_phone')->nullable();
            $table->string('user_city')->nullable();
            $table->text('user_address')->nullable();
            $table->dateTime('order_date')->useCurrent();

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

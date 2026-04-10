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
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->index();
            $table->string('payment_id')->index();
            $table->string('event');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('timestamp');
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};

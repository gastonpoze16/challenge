<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index('updated_at');
            $table->index('currency');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->index('timestamp');
            $table->index(['payment_id', 'event_id'], 'event_logs_payment_event_composite');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['currency']);
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex(['timestamp']);
            $table->dropIndex('event_logs_payment_event_composite');
        });
    }
};

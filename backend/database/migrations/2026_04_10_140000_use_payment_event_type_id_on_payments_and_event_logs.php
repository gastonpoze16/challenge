<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normaliza estado del pago: FK a payment_event_types en lugar de duplicar el string del código.
     * La API sigue exponiendo el atributo virtual `event` (código) vía modelo.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_event_type_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('payment_event_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        foreach (DB::table('payments')->cursor() as $row) {
            $typeId = DB::table('payment_event_types')
                ->where('code', $row->event)
                ->value('id');
            if ($typeId !== null) {
                DB::table('payments')->where('id', $row->id)->update([
                    'payment_event_type_id' => $typeId,
                ]);
            }
        }

        if (DB::table('payments')->whereNull('payment_event_type_id')->exists()) {
            throw new \RuntimeException(
                'Migration stopped: some payments have an event code not present in payment_event_types. Run PaymentEventTypeSeeder and fix data.'
            );
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('event');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->foreignId('payment_event_type_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('payment_event_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        foreach (DB::table('event_logs')->cursor() as $row) {
            $typeId = DB::table('payment_event_types')
                ->where('code', $row->event)
                ->value('id');
            if ($typeId !== null) {
                DB::table('event_logs')->where('id', $row->id)->update([
                    'payment_event_type_id' => $typeId,
                ]);
            }
        }

        if (DB::table('event_logs')->whereNull('payment_event_type_id')->exists()) {
            throw new \RuntimeException(
                'Migration stopped: some event_logs have an event code not present in payment_event_types.'
            );
        }

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('event')->nullable()->after('payment_id');
        });

        foreach (DB::table('payments')->cursor() as $row) {
            $code = DB::table('payment_event_types')
                ->where('id', $row->payment_event_type_id)
                ->value('code');
            DB::table('payments')->where('id', $row->id)->update(['event' => $code ?? 'payment.created']);
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_event_type_id']);
            $table->dropColumn('payment_event_type_id');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->string('event')->nullable()->after('payment_id');
        });

        foreach (DB::table('event_logs')->cursor() as $row) {
            $code = DB::table('payment_event_types')
                ->where('id', $row->payment_event_type_id)
                ->value('code');
            DB::table('event_logs')->where('id', $row->id)->update(['event' => $code ?? 'payment.created']);
        }

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropForeign(['payment_event_type_id']);
            $table->dropColumn('payment_event_type_id');
        });
    }
};

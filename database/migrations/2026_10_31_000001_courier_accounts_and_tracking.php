<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Courier accounts (several per courier per shop), courier status tracking,
 * scan-based packing / hand-over / return receiving and courier payment matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Accounts ────────────────────────────────────────────────
        Schema::table('courier_settings', function (Blueprint $table) {
            $table->dropUnique(['courier', 'tenant_id']);
        });
        Schema::table('courier_settings', function (Blueprint $table) {
            $table->string('courier', 20)->change();          // was an enum
            $table->text('api_key')->nullable()->change();     // encrypted now, so longer
            $table->text('api_secret')->nullable()->change();
            $table->text('extra_config')->nullable()->change();
            $table->string('name', 100)->nullable()->after('courier');
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->boolean('sandbox')->default(false)->after('is_default');
            $table->string('webhook_token', 64)->nullable()->unique()->after('webhook_url');
            $table->timestamp('last_webhook_at')->nullable();
            $table->index(['tenant_id', 'courier']);
        });

        // Existing keys were saved in plain text: encrypt them in place.
        foreach (DB::table('courier_settings')->get() as $row) {
            $enc = fn ($v) => $v === null || $v === '' ? $v : (self::isEncrypted($v) ? $v : encrypt($v, false));
            DB::table('courier_settings')->where('id', $row->id)->update([
                'api_key'       => $enc($row->api_key),
                'api_secret'    => $enc($row->api_secret),
                'extra_config'  => $enc($row->extra_config),
                'name'          => $row->name ?? ucfirst($row->courier === 'carrybe' ? 'carrybee' : $row->courier),
                'courier'       => $row->courier === 'carrybe' ? 'carrybee' : $row->courier,
                'is_default'    => true,
                'webhook_token' => Str::random(40),
            ]);
        }

        // ── Shipments ───────────────────────────────────────────────
        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->string('courier_name', 20)->change();     // was an enum: RedX / Paperfly / CarryBee could not be saved
            $table->foreignId('courier_account_id')->nullable()->after('order_id');
            $table->string('courier_status', 60)->nullable()->after('status');   // as the courier says it
            $table->string('courier_message', 500)->nullable()->after('courier_status');
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('next_sync_at')->nullable();
            $table->decimal('cod_amount', 10, 2)->nullable();
            $table->decimal('collected_amount', 10, 2)->nullable();
            $table->decimal('courier_charge', 10, 2)->nullable();   // what the courier actually charged
            $table->timestamp('handed_over_at')->nullable();
            $table->foreignId('handed_over_by')->nullable();
            $table->timestamp('return_received_at')->nullable();
            $table->foreignId('return_received_by')->nullable();
            $table->timestamp('paid_at')->nullable();               // courier paid us the COD
            $table->string('payment_ref', 100)->nullable();
            $table->index(['tenant_id', 'courier_name', 'consignment_id']);
            $table->index(['tenant_id', 'tracking_code']);
            $table->index('next_sync_at');
        });
        DB::table('courier_shipments')->where('courier_name', 'carrybe')->update(['courier_name' => 'carrybee']);

        Schema::create('courier_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('courier_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40);          // normalised: picked, in_transit, delivered …
            $table->string('courier_status', 60)->nullable();
            $table->string('message', 500)->nullable();
            $table->string('source', 10)->default('webhook');   // webhook | sync | manual
            $table->json('payload')->nullable();
            $table->timestamp('happened_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('packed_at')->nullable();
            $table->foreignId('packed_by')->nullable();
        });

        Schema::create('courier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('courier_account_id')->nullable();
            $table->string('courier', 20);
            $table->string('file_name')->nullable();
            $table->string('reference', 100)->nullable();
            $table->unsignedInteger('matched')->default(0);
            $table->unsignedInteger('mismatched')->default(0);
            $table->unsignedInteger('unknown')->default(0);
            $table->decimal('total_collected', 12, 2)->default(0);
            $table->decimal('total_charge', 12, 2)->default(0);
            $table->json('rows')->nullable();         // what we found for every line, for the report
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_payments');
        Schema::dropIfExists('courier_events');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['packed_at', 'packed_by']));
    }

    private static function isEncrypted(string $v): bool
    {
        try {
            decrypt($v, false);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};

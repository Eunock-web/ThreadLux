<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop manual PostgreSQL check constraints created by the enum() method
        // These are often named: table_column_check
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_status_check');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_payment_method_check');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_provider_check');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_escrow_status_check');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to restore the exact constraints without re-creating enums
    }
};

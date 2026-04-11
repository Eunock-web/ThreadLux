<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * SQLite doesn't support ALTER TABLE for ENUM — we use raw SQL to update the check constraint.
     * For SQLite (dev), enum is stored as TEXT, so this just adds 'en_litige' as valid.
     */
    public function up(): void
    {
        // For SQLite: escrow_status is already a string after previous migration,
        // so en_litige is already accepted. We just document the valid values here.
        // For MySQL/Postgres production, add this column modification:
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN escrow_status ENUM('none', 'held', 'released', 'refunded', 'disputed', 'en_litige') NOT NULL DEFAULT 'none'");
        }
        // SQLite already accepts any string value, no change needed for dev
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN escrow_status ENUM('none', 'held', 'released', 'refunded', 'disputed') NOT NULL DEFAULT 'none'");
        }
    }
};

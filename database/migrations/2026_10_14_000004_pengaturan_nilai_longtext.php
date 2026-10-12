<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // logo base64 ~533KB untuk 400KB file, VARCHAR 255 tidak muat.
        try {
            DB::statement('ALTER TABLE tb_pengaturan MODIFY nilai LONGTEXT NULL');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
    }
};

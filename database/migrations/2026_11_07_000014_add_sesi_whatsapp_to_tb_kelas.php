<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_kelas', function (Blueprint $table) {
            // Sesi WhatsApp milik kelas. Kolom kosong = ikut konfigurasi pusat
            // di tab Konfigurasi menu WhatsApp.
            $table->string('wa_number', 30)->nullable()->after('nama');
            $table->string('wa_api_key', 500)->nullable()->after('wa_number');
            $table->string('wa_url', 500)->nullable()->after('wa_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('tb_kelas', function (Blueprint $table) {
            $table->dropColumn(['wa_number', 'wa_api_key', 'wa_url']);
        });
    }
};

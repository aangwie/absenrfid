<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_perangkat', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('lokasi', 150)->nullable();
            $table->string('api_key', 80)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamp('terakhir_tap_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tb_tap_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perangkat_id')->nullable()->constrained('tb_perangkat')->nullOnDelete();
            $table->string('tag', 100);
            $table->string('kode', 40);
            $table->string('pesan', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['kode', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_tap_log');
        Schema::dropIfExists('tb_perangkat');
    }
};

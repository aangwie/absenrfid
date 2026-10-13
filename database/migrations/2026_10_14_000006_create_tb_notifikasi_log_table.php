<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_notifikasi_log', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 20)->index();
            $table->date('tanggal')->index();
            $table->enum('tipe', ['masuk', 'pulang']);
            $table->string('nomor_tujuan', 30)->nullable();
            $table->text('pesan')->nullable();
            $table->enum('status', ['antre', 'terkirim', 'gagal'])->default('antre')->index();
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->text('respons')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_notifikasi_log');
    }
};

<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CronPing implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $pingId) {}

    public function handle(): void
    {
        $now = now()->toDateTimeString();
        foreach ([
            'cron_ping_at' => $now,
            'cron_ping_id' => $this->pingId,
            'cron_ping_hasil' => 'OK dieksekusi ' . $now,
            'cron_last_run' => $now,
        ] as $kunci => $nilai) {
            DB::table('tb_pengaturan')->updateOrInsert(['kunci' => $kunci], ['nilai' => $nilai, 'updated_at' => now(), 'created_at' => now()]);
        }
    }
}

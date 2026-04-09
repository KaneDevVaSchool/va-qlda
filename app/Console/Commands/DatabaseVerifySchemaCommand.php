<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DatabaseVerifySchemaCommand extends Command
{
    protected $signature = 'db:verify-schema';

    protected $description = 'Kiểm tra các bảng bắt buộc trên DB ứng dụng và CMS (sau deploy / trước khi báo lỗi schema)';

    public function handle(): int
    {
        try {
            return $this->runChecks();
        } catch (\Throwable $e) {
            $this->error('Không kiểm tra được: '.$e->getMessage());
            $this->line('Kiểm tra .env (DB_*, CMS_DB_*) và máy chủ MySQL đang chạy.');

            return self::FAILURE;
        }
    }

    private function runChecks(): int
    {
        $ok = true;

        $appTables = [
            'migrations',
            'departments',
            'vendors',
            'vendor_department',
            'vendor_products',
            'vendor_reviews',
            'vendor_timelines',
            'contracts',
            'audit_logs',
        ];

        $this->info('Connection mặc định ('.config('database.default').', database='.config('database.connections.'.config('database.default').'.database').')');

        foreach ($appTables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("  <fg=green>OK</>  {$table}");
            } else {
                $this->line("  <fg=red>MISS</> {$table}");
                $ok = false;
            }
        }

        $cmsDb = config('database.connections.cms.database');
        $this->newLine();
        $this->info('Connection cms (database='.$cmsDb.')');

        foreach (['users'] as $table) {
            if (Schema::connection('cms')->hasTable($table)) {
                $this->line("  <fg=green>OK</>  {$table}");
            } else {
                $this->line("  <fg=red>MISS</> {$table}");
                $ok = false;
            }
        }

        $this->newLine();
        if ($ok) {
            $this->info('Tất cả bảng kiểm tra đều tồn tại.');

            return self::SUCCESS;
        }

        $this->error('Thiếu bảng — chạy: php artisan migrate --force');
        $this->line('(Môi trường production: đảm bảo đã deploy đủ file trong database/migrations và .env trỏ đúng DB.)');

        return self::FAILURE;
    }
}


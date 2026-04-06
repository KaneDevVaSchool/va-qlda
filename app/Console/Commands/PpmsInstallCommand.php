<?php

namespace App\Console\Commands;

use Database\Seeders\PpmsSeeder;
use Illuminate\Console\Command;

class PpmsInstallCommand extends Command
{
    protected $signature = 'ppms:install {--force : Bỏ qua xác nhận (CI / script)}';

    protected $description = 'migrate:fresh + seed tài khoản quản trị (ADMIN_EMAIL / ADMIN_INITIAL_PASSWORD trong .env)';

    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'development', 'staging', 'testing'], true)) {
            $this->error('Chỉ chạy khi APP_ENV là local, development, staging hoặc testing.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Xóa toàn bộ bảng và chạy migration + seed lại?', true)) {
            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => PpmsSeeder::class, '--force' => true]);

        $this->newLine();
        $this->info('Hoàn tất. Đăng nhập bằng ADMIN_EMAIL và ADMIN_INITIAL_PASSWORD trong .env.');

        return self::SUCCESS;
    }
}

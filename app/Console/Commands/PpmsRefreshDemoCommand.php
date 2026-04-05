<?php

namespace App\Console\Commands;

use Database\Seeders\PpmsSeeder;
use Illuminate\Console\Command;

class PpmsRefreshDemoCommand extends Command
{
    protected $signature = 'ppms:refresh-demo {--force : Bỏ qua xác nhận (CI / script)}';

    protected $description = 'migrate:fresh + seed PPMS (dữ liệu demo VA Schools, mật khẩu: password)';

    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'development', 'staging', 'testing'], true)) {
            $this->error('Chỉ chạy khi APP_ENV là local, development, staging hoặc testing.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Xóa toàn bộ bảng và nạp lại dữ liệu demo?', true)) {
            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => PpmsSeeder::class, '--force' => true]);

        $this->newLine();
        $this->info('Hoàn tất. Mật khẩu mọi user: password');
        $this->line('Ví dụ: kieu.nguyen@va-schools.vn / password (PM), tai.nguyen@va-schools.vn (Dev)');

        return self::SUCCESS;
    }
}

<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class ApiErrorPresenter
{
    /**
     * Map database-related exceptions to safe, user-facing Vietnamese copy.
     * Raw SQL / connection strings must never be returned to clients.
     *
     * @return array{message: string, code: string, hint?: string}
     */
    public static function fromDatabaseThrowable(Throwable $e): array
    {
        $raw = $e->getMessage();

        if ($e instanceof QueryException || $e instanceof PDOException) {
            if (str_contains($raw, '1049') || stripos($raw, 'Unknown database') !== false) {
                return [
                    'message' => 'Hệ thống không kết nối được cơ sở dữ liệu đã cấu hình.',
                    'code' => 'database_unknown',
                    'hint' => 'Kiểm tra tên database trong file .env và đảm bảo database đã được tạo trên máy chủ MySQL.',
                ];
            }

            if (str_contains($raw, '2002') || stripos($raw, 'Connection refused') !== false) {
                return [
                    'message' => 'Không kết nối được máy chủ cơ sở dữ liệu.',
                    'code' => 'database_connection',
                    'hint' => 'Xác nhận MySQL đang chạy và thông tin host/port trong .env là đúng.',
                ];
            }

            if (str_contains($raw, '2006') || stripos($raw, 'MySQL server has gone away') !== false) {
                return [
                    'message' => 'Kết nối tới cơ sở dữ liệu bị gián đoạn.',
                    'code' => 'database_connection',
                    'hint' => 'Thử lại sau vài giây. Nếu lỗi lặp lại, kiểm tra cấu hình MySQL và tải máy chủ.',
                ];
            }

            if (str_contains($raw, '1045') || stripos($raw, 'Access denied') !== false) {
                return [
                    'message' => 'Thông tin đăng nhập cơ sở dữ liệu không đúng.',
                    'code' => 'database_auth',
                    'hint' => 'Kiểm tra DB_USERNAME và DB_PASSWORD trong file .env.',
                ];
            }

            if (stripos($raw, 'Base table or view not found') !== false
                || str_contains($raw, '42S02')
                || (stripos($raw, "doesn't exist") !== false && stripos($raw, 'Table') !== false)) {
                return [
                    'message' => 'Cấu trúc dữ liệu hệ thống chưa được khởi tạo đầy đủ.',
                    'code' => 'database_schema',
                    'hint' => 'Trên máy chủ ứng dụng, chạy: php artisan migrate',
                ];
            }

            if (stripos($raw, 'Duplicate entry') !== false) {
                return [
                    'message' => 'Dữ liệu trùng với bản ghi đã có.',
                    'code' => 'database_duplicate',
                    'hint' => 'Thử giá trị khác hoặc cập nhật bản ghi hiện có.',
                ];
            }

            if (stripos($raw, 'Lock wait timeout') !== false || stripos($raw, 'Deadlock') !== false) {
                return [
                    'message' => 'Thao tác chưa hoàn tất do hệ thống đang bận.',
                    'code' => 'database_busy',
                    'hint' => 'Vui lòng thử lại sau vài giây.',
                ];
            }
        }

        return [
            'message' => 'Không truy cập được dữ liệu lúc này.',
            'code' => 'database_error',
            'hint' => 'Vui lòng thử lại. Nếu lỗi lặp lại, báo cho bộ phận IT.',
        ];
    }
}

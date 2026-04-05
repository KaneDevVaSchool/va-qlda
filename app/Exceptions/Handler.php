<?php

namespace App\Exceptions;

use App\Support\ApiErrorPresenter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($this->wantsFriendlyApiResponse($request)) {
            $json = $this->renderFriendlyApiJson($request, $e);
            if ($json !== null) {
                return $json;
            }
        }

        return parent::render($request, $e);
    }

    protected function wantsFriendlyApiResponse(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    protected function renderFriendlyApiJson(Request $request, Throwable $e): ?JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Dữ liệu gửi lên chưa hợp lệ.',
                'code' => 'validation_failed',
                'hint' => 'Kiểm tra và chỉnh lại các trường được liệt kê bên dưới.',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn.',
                'code' => 'unauthenticated',
                'hint' => 'Vui lòng đăng nhập lại.',
            ], 401);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện thao tác này.',
                'code' => 'forbidden',
                'hint' => 'Liên hệ quản trị nếu bạn cần quyền truy cập.',
            ], 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Không tìm thấy dữ liệu được yêu cầu.',
                'code' => 'not_found',
                'hint' => 'Có thể bản ghi đã bị xóa hoặc địa chỉ không còn đúng.',
            ], 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Không tìm thấy API hoặc phương thức không được hỗ trợ.',
                'code' => 'not_found',
            ], 404);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return response()->json([
                'message' => 'Bạn đang gửi quá nhiều yêu cầu trong thời gian ngắn.',
                'code' => 'too_many_requests',
                'hint' => 'Vui lòng đợi vài giây rồi thử lại.',
            ], $e->getStatusCode());
        }

        if ($e instanceof QueryException || $e instanceof PDOException) {
            return response()->json(ApiErrorPresenter::fromDatabaseThrowable($e), 500);
        }

        if ($e instanceof HttpExceptionInterface) {
            return $this->renderFriendlyHttpException($e);
        }

        return response()->json([
            'message' => 'Đã xảy ra lỗi không mong muốn.',
            'code' => 'server_error',
            'hint' => 'Vui lòng thử lại. Nếu lỗi tiếp diễn, báo bộ phận IT kèm thời gian xảy ra.',
        ], 500);
    }

    protected function renderFriendlyHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();

        if ($status === 419) {
            return response()->json([
                'message' => 'Phiên làm việc đã hết hạn.',
                'code' => 'page_expired',
                'hint' => 'Tải lại trang và thử lại.',
            ], 419);
        }

        $message = match ($status) {
            400 => 'Yêu cầu không hợp lệ.',
            401 => 'Bạn cần đăng nhập để thực hiện thao tác này.',
            403 => 'Bạn không có quyền thực hiện thao tác này.',
            404 => 'Không tìm thấy nội dung được yêu cầu.',
            405 => 'Phương thức HTTP không được hỗ trợ cho đường dẫn này.',
            408 => 'Yêu cầu hết thời gian chờ.',
            409 => 'Dữ liệu xung đột với trạng thái hiện tại.',
            413 => 'Tệp hoặc dữ liệu gửi lên quá lớn.',
            415 => 'Định dạng dữ liệu không được hỗ trợ.',
            422 => 'Không thể xử lý dữ liệu đã gửi.',
            429 => 'Quá nhiều yêu cầu. Vui lòng chờ rồi thử lại.',
            default => 'Không thể xử lý yêu cầu.',
        };

        $payload = [
            'message' => $message,
            'code' => 'http_'.$status,
        ];

        if ($status === 429) {
            $payload['hint'] = 'Thử lại sau vài giây.';
        }

        return response()->json($payload, $status);
    }
}

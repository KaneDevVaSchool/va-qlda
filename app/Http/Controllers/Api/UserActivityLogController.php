<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserActivityLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserActivityLogController extends Controller
{
    public function index(Request $request, UserActivityLogService $activity): \Illuminate\Http\JsonResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:200',
            'event' => 'nullable|string|max:96',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $perPage = (int) ($filters['per_page'] ?? 15);
        unset($filters['per_page']);

        $paginator = $activity->paginate($request->user(), $perPage, $filters);

        return response()->json($paginator);
    }

    public function exportCsv(Request $request, UserActivityLogService $activity): StreamedResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:200',
            'event' => 'nullable|string|max:96',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $filename = 'activity-'.now()->format('Y-m-d-His').'.csv';
        $user = $request->user();

        return response()->streamDownload(function () use ($user, $activity, $filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['action', 'module', 'ip', 'device', 'time']);
            foreach ($activity->streamCsvLines($user, $filters) as $row) {
                fputcsv($out, [
                    $row['action'],
                    $row['module'],
                    $row['ip'],
                    $row['device'],
                    $row['time'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

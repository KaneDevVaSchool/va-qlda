<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PPMS Weekly Status</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #444; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
<h1>Báo cáo trạng thái tuần — PPMS</h1>
<div class="meta">Tạo lúc: {{ $generatedAt }}</div>
<p>Open tasks: {{ $taskOpen }} — Tasks completed (7 ngày): {{ $taskDoneWeek }}</p>
<table>
    <thead>
    <tr>
        <th>Dự án</th>
        <th>Loại</th>
        <th>Phase</th>
        <th>Trạng thái</th>
        <th>Tiến độ %</th>
        <th>Owner</th>
    </tr>
    </thead>
    <tbody>
    @foreach($projects as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td>{{ $p->type }}</td>
            <td>{{ $p->phase }}</td>
            <td>{{ $p->status }}</td>
            <td>{{ number_format((float) $p->progress, 2) }}</td>
            <td>{{ $p->owner?->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

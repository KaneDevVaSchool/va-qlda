<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PPMS — Projects</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1d26; }
        h1 { font-size: 14px; margin: 0 0 6px; color: #9a0036; }
        .meta { color: #5c6370; margin-bottom: 12px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e8eaef; padding: 4px 6px; text-align: left; }
        th { background: #f6f7f9; font-size: 8px; text-transform: uppercase; }
        .num { text-align: right; }
        .filters { margin-bottom: 10px; padding: 6px; background: #f6f7f9; font-size: 8px; word-break: break-all; }
    </style>
</head>
<body>
    <h1>PPMS — Danh sách dự án (đã lọc)</h1>
    @php
        $rowLimit = $rowLimit ?? 500;
        $totalMatching = $totalMatching ?? $projects->count();
        $truncated = $truncated ?? false;
    @endphp
    <div class="meta">
        Sinh lúc: {{ $generatedAt }}
        · Hiển thị {{ $projects->count() }} / {{ $totalMatching }} dòng khớp bộ lọc
        · Giới hạn PDF: {{ $rowLimit }} dòng
        @if ($truncated)
            <strong> (đã cắt bớt — dùng CSV để xuất đầy đủ)</strong>
        @endif
    </div>
    @if (!empty($filterSummary))
        <div class="filters"><strong>Query:</strong> {{ json_encode($filterSummary, JSON_UNESCAPED_UNICODE) }}</div>
    @endif
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Khách hàng</th>
                <th>Loại</th>
                <th>Phase</th>
                <th>TT</th>
                <th>Nhãn</th>
                <th class="num">%</th>
                <th>Deadline</th>
                <th>Bắt đầu</th>
                <th>Bắt đầu thực tế</th>
                <th>Owner</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->customer_name }}</td>
                    <td>{{ $p->type }}</td>
                    <td>{{ $p->phase }}</td>
                    <td>{{ $p->status }}</td>
                    <td>{{ is_array($p->labels) && count($p->labels) ? implode(', ', $p->labels) : '—' }}</td>
                    <td class="num">{{ number_format((float) $p->progress, 1) }}</td>
                    <td>{{ $p->deadline?->toDateString() }}</td>
                    <td>{{ $p->start_date?->toDateString() ?? '—' }}</td>
                    <td>{{ $p->actual_start_date?->toDateString() ?? '—' }}</td>
                    <td>{{ $p->owner?->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

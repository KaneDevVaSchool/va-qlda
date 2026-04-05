<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>3P Evaluation #{{ $e->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; }
        .conf { color: #b91c1c; font-weight: bold; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
<div class="conf">CONFIDENTIAL — 3P Evaluation</div>
<h1>Đánh giá 3P — {{ $e->person->name ?? '' }}</h1>
<p>Kỳ: {{ $e->period_type }} {{ $e->period_label }} | Trạng thái: {{ $e->status }}</p>
<table>
    <tr><th>P1 (40%)</th><td>{{ $e->p1 }}</td></tr>
    <tr><th>P2 (30%)</th><td>{{ $e->p2 }}</td></tr>
    <tr><th>P3 (30%)</th><td>{{ $e->p3 }}</td></tr>
    <tr><th>Tổng</th><td>{{ $e->total }}</td></tr>
    <tr><th>Grade</th><td>{{ $e->grade }}</td></tr>
    <tr><th>Reviewer</th><td>{{ $e->reviewer->name ?? '—' }}</td></tr>
    @if($e->adjustment_delta)
        <tr><th>Điều chỉnh</th><td>{{ $e->adjustment_delta }} — {{ $e->adjustment_reason }}</td></tr>
    @endif
</table>
<h2>Peer reviews</h2>
<table>
    <thead><tr><th>Reviewer</th><th>Attitude</th><th>Ghi chú</th></tr></thead>
    <tbody>
    @foreach($e->peers as $p)
        <tr>
            <td>{{ $p->reviewer->name ?? '' }}</td>
            <td>{{ $p->attitude_score }}</td>
            <td>{{ $p->notes }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>

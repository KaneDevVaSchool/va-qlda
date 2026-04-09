<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $contract->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0 0 4px; letter-spacing: -0.02em; }
        .muted { color: #555; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 7px 9px; text-align: left; vertical-align: top; }
        th { background: #f4f4f5; font-weight: 700; width: 28%; }
        .scope { white-space: pre-wrap; }
        .footer { margin-top: 18px; font-size: 9px; color: #777; }
    </style>
</head>
<body>
<h1>Tóm tắt hợp đồng — {{ $contract->code }}</h1>
<div class="muted">Tạo PDF lúc: {{ $generatedAt }}</div>

<table>
    <tr><th>Trạng thái</th><td>{{ $contract->status?->value ?? '—' }}</td></tr>
    <tr><th>Nhà cung cấp</th><td>{{ $contract->vendor?->name ?? '—' }}</td></tr>
    <tr><th>Sản phẩm / dịch vụ</th><td>{{ $contract->product?->name ?? '—' }}</td></tr>
    <tr><th>Phòng ban</th><td>{{ $contract->department?->name ?? '—' }}</td></tr>
    <tr><th>Ngày bắt đầu</th><td>{{ $contract->start_date?->toDateString() ?? '—' }}</td></tr>
    <tr><th>Ngày kết thúc</th><td>{{ $contract->end_date?->toDateString() ?? '—' }}</td></tr>
    <tr><th>Tổng giá trị (VNĐ)</th><td>{{ number_format((float) $contract->total_value, 0, ',', '.') }}</td></tr>
    <tr><th>Chu kỳ thanh toán</th><td>{{ $contract->payment_cycle?->value ?? '—' }}</td></tr>
    <tr><th>Phạm vi</th><td class="scope">{{ $contract->scope ?: '—' }}</td></tr>
</table>

<div class="footer">{{ config('app.name', 'PPMS') }}</div>
</body>
</html>

<x-mail::message>
# Khảo sát hài lòng (CSAT)

Dự án **{{ $project->name }}** đã hoàn tất giai đoạn. Vui lòng đánh giá mức độ hài lòng (1–5) trong PPMS.

<x-mail::button :url="$surveyUrl">
Mở PPMS — CSAT
</x-mail::button>

Hoặc đăng nhập PPMS → Dự án → mục CSAT.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

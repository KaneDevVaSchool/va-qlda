<?php

namespace Database\Seeders;

use App\Models\CsatResponse;
use App\Models\Evaluation;
use App\Models\EvaluationPeer;
use App\Models\InnovationIdea;
use App\Models\Kaizen;
use App\Models\KpiSnapshot;
use App\Models\PpmsNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use App\Models\User;
use App\Services\EvaluationScoring;
use App\Services\ProjectProgressService;
use App\Services\TaskWeightCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu demo VA Schools — luồng kiểm thử E2E (dự án, task, Kaizen, 3P, KPI snapshot, CSAT, thông báo).
 * Mật khẩu mọi tài khoản: password
 */
class PpmsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'Quản trị hệ thống',
            'email' => 'admin@va-schools.vn',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $khoa = User::query()->create([
            'name' => 'Nguyễn Anh Khoa',
            'email' => 'khoa.nguyen@va-schools.vn',
            'password' => 'password',
            'role' => 'tl',
            'email_verified_at' => now(),
        ]);

        $tai = User::query()->create([
            'name' => 'Nguyễn Phát Tài',
            'email' => 'tai.nguyen@va-schools.vn',
            'password' => 'password',
            'role' => 'developer',
            'email_verified_at' => now(),
        ]);

        $long = User::query()->create([
            'name' => 'Trần Hoàng Long',
            'email' => 'long.tran@va-schools.vn',
            'password' => 'password',
            'role' => 'developer',
            'email_verified_at' => now(),
        ]);

        $thinh = User::query()->create([
            'name' => 'Phạm Quốc Thịnh',
            'email' => 'thinh.pham@va-schools.vn',
            'password' => 'password',
            'role' => 'developer',
            'email_verified_at' => now(),
        ]);

        $kieu = User::query()->create([
            'name' => 'Nguyễn Lê Thanh Kiều',
            'email' => 'kieu.nguyen@va-schools.vn',
            'password' => 'password',
            'role' => 'pm',
            'email_verified_at' => now(),
        ]);

        $hoa = User::query()->create([
            'name' => 'Đinh Thị Thu Hoa',
            'email' => 'hoa.dinh@va-schools.vn',
            'password' => 'password',
            'role' => 'pm',
            'email_verified_at' => now(),
        ]);

        $mai = User::query()->create([
            'name' => 'Lê Thị Mai (HR BP)',
            'email' => 'mai.le@va-schools.vn',
            'password' => 'password',
            'role' => 'hr',
            'email_verified_at' => now(),
        ]);

        DB::table('users')->update(['password_changed_at' => now()]);

        $progress = app(ProjectProgressService::class);

        // —— Dự án 1: PPMS / Delivery (UAT, có rủi ro tiến độ) ——
        $pPpms = Project::query()->create([
            'name' => 'Nền tảng PPMS VA Schools',
            'type' => 'delivery',
            'phase' => 'uat',
            'status' => 'at_risk',
            'owner_id' => $kieu->id,
            'deadline' => now()->addDays(40),
            'description' => 'Hệ quản lý dự án & hiệu suất nội bộ: Laravel API, Vue SPA, KPI, Kaizen, 3P. Đội: Kiều/Hoa BA·PM, Khoa TL, Tài/Long fullstack, Thịnh kiến trúc & tự động hoá.',
            'progress' => 0,
            'customer_name' => 'VA Schools — Ban Giám hiệu',
            'customer_email' => 'ban.giamhieu@va-schools.vn',
            'suppliers' => [
                ['name' => 'Cloud VA (IaaS)'],
                ['name' => 'Đối tác tích hợp SSO'],
            ],
            'process_timeline' => [
                ['phase' => 'planning', 'completed_at' => now()->subMonths(4)->toDateString()],
                ['phase' => 'development', 'completed_at' => now()->subMonths(2)->toDateString()],
                ['phase' => 'uat', 'completed_at' => null],
            ],
            'stakeholder_emails' => ['ban.giamhieu@va-schools.vn', 'cto@va-schools.vn'],
            'csat_invites_sent' => 0,
        ]);

        $tEpic = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => null,
            'name' => 'Khung ứng dụng & điều hướng (Epic)',
            'assignee_id' => $long->id,
            'estimate_hours' => 40,
            'actual_hours' => 38,
            'complexity' => 4,
            'impact' => 5,
            'weight' => TaskWeightCalculator::compute(4, 5),
            'due_date' => now()->subDays(5),
            'status' => 'done',
            'sort_order' => 1,
        ]);

        $tApi = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => null,
            'name' => 'API dự án / task / phụ thuộc (Laravel + Sanctum)',
            'assignee_id' => $tai->id,
            'estimate_hours' => 56,
            'actual_hours' => 52,
            'complexity' => 5,
            'impact' => 5,
            'weight' => TaskWeightCalculator::compute(5, 5),
            'due_date' => now()->subDays(2),
            'status' => 'done',
            'sort_order' => 2,
        ]);

        $tUi = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => null,
            'name' => 'Giao diện responsive (Vue) — dashboard & chi tiết dự án',
            'assignee_id' => $long->id,
            'estimate_hours' => 48,
            'actual_hours' => 22,
            'complexity' => 4,
            'impact' => 5,
            'weight' => TaskWeightCalculator::compute(4, 5),
            'due_date' => now()->addDays(7),
            'status' => 'in_progress',
            'sort_order' => 3,
        ]);

        $tCicd = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => null,
            'name' => 'CI/CD & môi trường staging (pipeline, env, backup DB)',
            'assignee_id' => $thinh->id,
            'estimate_hours' => 24,
            'actual_hours' => 8,
            'complexity' => 4,
            'impact' => 4,
            'weight' => TaskWeightCalculator::compute(4, 4),
            'due_date' => now()->addDays(10),
            'status' => 'in_progress',
            'sort_order' => 4,
        ]);

        $tBa = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => null,
            'name' => 'Kịch bản UAT & checklist CSAT Type 2',
            'assignee_id' => $hoa->id,
            'estimate_hours' => 16,
            'actual_hours' => 6,
            'complexity' => 3,
            'impact' => 4,
            'weight' => TaskWeightCalculator::compute(3, 4),
            'due_date' => now()->addDays(5),
            'status' => 'todo',
            'sort_order' => 5,
        ]);

        $tChild1 = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => $tEpic->id,
            'name' => 'Sidebar / drawer mobile + skip link',
            'assignee_id' => $long->id,
            'estimate_hours' => 12,
            'actual_hours' => 12,
            'complexity' => 3,
            'impact' => 4,
            'weight' => TaskWeightCalculator::compute(3, 4),
            'due_date' => now()->subDays(6),
            'status' => 'done',
            'sort_order' => 1,
        ]);

        $tChild2 = Task::query()->create([
            'project_id' => $pPpms->id,
            'parent_id' => $tEpic->id,
            'name' => 'Bảng dữ liệu cuộn ngang (backoffice)',
            'assignee_id' => $long->id,
            'estimate_hours' => 8,
            'actual_hours' => 2,
            'complexity' => 2,
            'impact' => 3,
            'weight' => TaskWeightCalculator::compute(2, 3),
            'due_date' => now()->addDays(3),
            'status' => 'in_progress',
            'sort_order' => 2,
        ]);

        TaskDependency::query()->create([
            'successor_task_id' => $tUi->id,
            'predecessor_task_id' => $tApi->id,
        ]);

        TaskComment::query()->create([
            'task_id' => $tUi->id,
            'user_id' => $khoa->id,
            'body' => 'Ưu tiên xong Gantt + filter trước demo với BGĐ. Long sync với Tài về shape API `/gantt`.',
        ]);

        TaskComment::query()->create([
            'task_id' => $tApi->id,
            'user_id' => $tai->id,
            'body' => 'Đã bổ sung predecessors trên `GET /projects/{id}` — FE có thể hiển thị Pred column.',
        ]);

        CsatResponse::query()->create([
            'project_id' => $pPpms->id,
            'milestone_label' => 'Sprint UAT-1',
            'user_id' => $kieu->id,
            'rater_email' => $kieu->email,
            'rating' => 4,
            'comment' => 'Giao diện ổn; mong bổ sung export PDF 3P trên mobile.',
        ]);

        CsatResponse::query()->create([
            'project_id' => $pPpms->id,
            'milestone_label' => 'Demo nội bộ TL',
            'user_id' => $khoa->id,
            'rater_email' => $khoa->email,
            'rating' => 5,
            'comment' => null,
        ]);

        $progress->syncProjectProgress($pPpms->fresh());

        // —— Dự án 2: Maintenance Type 1 ——
        $pLms = Project::query()->create([
            'name' => 'Vận hành LMS & SSO nội bộ',
            'type' => 'maintenance',
            'phase' => 'maintenance',
            'status' => 'on_track',
            'owner_id' => $khoa->id,
            'deadline' => now()->addMonth(),
            'description' => 'Theo dõi incident, patch bảo mật, SLA phản hồi ticket. Phối hợp Thịnh (automation) và Tài (hotfix API).',
            'progress' => 0,
            'customer_name' => 'Khối vận hành & giáo vụ',
            'customer_email' => 'hotro.giaovu@va-schools.vn',
            'suppliers' => [
                ['name' => 'NCC SSO (hợp đồng bảo trì)'],
            ],
            'process_timeline' => [
                ['phase' => 'planning', 'completed_at' => now()->subYear()->toDateString()],
                ['phase' => 'development', 'completed_at' => now()->subMonths(10)->toDateString()],
                ['phase' => 'uat', 'completed_at' => now()->subMonths(9)->toDateString()],
                ['phase' => 'done', 'completed_at' => now()->subMonths(8)->toDateString()],
                ['phase' => 'maintenance', 'completed_at' => now()->subMonths(7)->toDateString()],
            ],
        ]);

        Task::query()->create([
            'project_id' => $pLms->id,
            'name' => 'Runbook backup DB hàng tuần (đã xác minh)',
            'assignee_id' => $thinh->id,
            'estimate_hours' => 4,
            'actual_hours' => 3.5,
            'complexity' => 2,
            'impact' => 5,
            'weight' => TaskWeightCalculator::compute(2, 5),
            'due_date' => now()->subDay(),
            'status' => 'done',
            'sort_order' => 1,
        ]);

        Task::query()->create([
            'project_id' => $pLms->id,
            'name' => 'Gia hạn chứng thư SSL portal giáo viên',
            'assignee_id' => $tai->id,
            'estimate_hours' => 3,
            'actual_hours' => 0,
            'complexity' => 2,
            'impact' => 4,
            'weight' => TaskWeightCalculator::compute(2, 4),
            'due_date' => now()->addDays(4),
            'status' => 'todo',
            'sort_order' => 2,
        ]);

        Task::query()->create([
            'project_id' => $pLms->id,
            'name' => 'Ticket #4421 — lỗi đăng nhập app mobile (điều tra)',
            'assignee_id' => $tai->id,
            'estimate_hours' => 6,
            'actual_hours' => 7.5,
            'complexity' => 3,
            'impact' => 4,
            'weight' => TaskWeightCalculator::compute(3, 4),
            'due_date' => now()->subDays(1),
            'status' => 'blocked',
            'blocked_reason' => 'Chờ log từ nhà cung cấp SSO',
            'blocked_at' => now()->subHours(6),
            'sort_order' => 3,
        ]);

        $progress->syncProjectProgress($pLms->fresh());

        // —— Dự án 3: R&D Type 3 ——
        $pAi = Project::query()->create([
            'name' => 'Lab AI — gợi ý chấm bài tự luận (POC)',
            'type' => 'rnd',
            'phase' => 'development',
            'status' => 'on_track',
            'owner_id' => $thinh->id,
            'deadline' => now()->addMonths(4),
            'description' => 'Thử nghiệm pipeline RAG nội bộ, không production. Báo cáo funnel Innovation trong PPMS.',
            'progress' => 0,
            'customer_name' => 'Khối học thuật (POC nội bộ)',
            'customer_email' => null,
            'suppliers' => [],
            'process_timeline' => [
                ['phase' => 'planning', 'completed_at' => now()->subMonths(2)->toDateString()],
                ['phase' => 'development', 'completed_at' => null],
            ],
        ]);

        Task::query()->create([
            'project_id' => $pAi->id,
            'name' => 'Khảo sát dữ liệu mẫu (đã ẩn danh hoá)',
            'assignee_id' => $kieu->id,
            'estimate_hours' => 20,
            'actual_hours' => 14,
            'complexity' => 3,
            'impact' => 5,
            'weight' => TaskWeightCalculator::compute(3, 5),
            'due_date' => now()->subWeek(),
            'status' => 'done',
            'sort_order' => 1,
        ]);

        Task::query()->create([
            'project_id' => $pAi->id,
            'name' => 'Prototype scoring API (Python service)',
            'assignee_id' => $thinh->id,
            'estimate_hours' => 40,
            'actual_hours' => 18,
            'complexity' => 5,
            'impact' => 5,
            'weight' => TaskWeightCalculator::compute(5, 5),
            'due_date' => now()->addWeeks(3),
            'status' => 'in_progress',
            'sort_order' => 2,
        ]);

        $progress->syncProjectProgress($pAi->fresh());

        // —— Kaizen ——
        $weekLast = now()->subWeek()->startOfWeek()->toDateString();
        $weekPrev = now()->subWeeks(2)->startOfWeek()->toDateString();

        Kaizen::query()->create([
            'submitter_id' => $tai->id,
            'week_start' => $weekPrev,
            'problem' => 'Merge conflict hay làm hỏng build nightly khi nhiều nhánh feature.',
            'solution' => 'Áp rule rebase trước PR + checklist 3 mục trên template.',
            'outcome_measurable' => 'Giảm 60% build đỏ do conflict (4 → 1.5 lần/tuần trung bình).',
            'estimated_value' => 1200000,
            'status' => 'verified',
            'tl_rating' => 5,
            'score' => 55,
            'reviewed_by' => $khoa->id,
        ]);

        Kaizen::query()->create([
            'submitter_id' => $long->id,
            'week_start' => $weekLast,
            'problem' => 'Bảng PPMS tràn ngang trên iPhone, khó thao tác bulk.',
            'solution' => 'Bọc `.ppms-table-scroll` + tăng tap target nút.',
            'outcome_measurable' => 'QA pass checklist responsive 12 màn.',
            'estimated_value' => 800000,
            'status' => 'verified',
            'tl_rating' => 4,
            'score' => 45,
            'reviewed_by' => $khoa->id,
        ]);

        Kaizen::query()->create([
            'submitter_id' => $thinh->id,
            'week_start' => $weekLast,
            'problem' => 'Job nhắc Kaizen chưa gắn reminder record theo tuần.',
            'solution' => 'Bảng `kaizen_weekly_reminders` + command Thứ 5.',
            'outcome_measurable' => '100% user có bản ghi reminder khi gửi notify.',
            'estimated_value' => 400000,
            'status' => 'implemented',
            'tl_rating' => null,
            'reviewed_by' => $khoa->id,
        ]);

        Kaizen::query()->create([
            'submitter_id' => $hoa->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'problem' => 'Form tạo dự án thiếu gợi ý stakeholder CSAT.',
            'solution' => 'Thêm placeholder email + tooltip BR Type 2.',
            'outcome_measurable' => 'Giảm thời gian nhập liệu 20% (đo 5 phiên usability).',
            'estimated_value' => 350000,
            'status' => 'submitted',
        ]);

        Kaizen::query()->create([
            'submitter_id' => $kieu->id,
            'week_start' => $weekPrev,
            'problem' => 'PM không thấy rủi ro phase UAT trên dashboard.',
            'solution' => 'Highlight card at-risk + link nhanh tới dự án.',
            'outcome_measurable' => '3/3 PM xác nhận dùng được trong họp tuần.',
            'estimated_value' => 600000,
            'status' => 'approved',
            'tl_rating' => null,
            'reviewed_by' => $khoa->id,
        ]);

        // —— Innovation ——
        InnovationIdea::query()->create([
            'submitter_id' => $thinh->id,
            'project_id' => $pAi->id,
            'title' => 'Vector store nội bộ cho tài liệu chấm bài',
            'description' => 'Dùng embedding cục bộ, không gửi PII ra ngoài. Gắn với project R&D Lab AI.',
            'status' => 'poc',
        ]);

        InnovationIdea::query()->create([
            'submitter_id' => $tai->id,
            'project_id' => $pPpms->id,
            'title' => 'Webhook Slack/Teams khi dự án chuyển At Risk',
            'description' => 'Payload gọn: tên dự án, phase, % tiến độ.',
            'status' => 'submitted',
        ]);

        InnovationIdea::query()->create([
            'submitter_id' => $hoa->id,
            'project_id' => $pPpms->id,
            'title' => 'Wizard tạo dự án theo template BR (Type 1/2/3)',
            'description' => 'Giảm lỗi chọn sai phase ban đầu cho PM mới.',
            'status' => 'applied',
        ]);

        InnovationIdea::query()->create([
            'submitter_id' => $long->id,
            'project_id' => null,
            'title' => 'Storybook cho component PPMS (Vue)',
            'description' => 'Chuẩn hoá button, card, table cho đội BA review UI.',
            'status' => 'submitted',
        ]);

        // —— Đánh giá 3P ——
        $p1 = 84.0;
        $p2 = 80.0;
        $p3 = 78.0;
        $totalLong = EvaluationScoring::total($p1, $p2, $p3);

        $evalApproved = Evaluation::query()->create([
            'period_type' => 'quarterly',
            'period_label' => '2025-Q4',
            'person_id' => $long->id,
            'p1' => $p1,
            'p2' => $p2,
            'p3' => $p3,
            'total' => $totalLong,
            'grade' => EvaluationScoring::grade($totalLong),
            'reviewer_id' => $kieu->id,
            'status' => 'approved',
        ]);

        EvaluationPeer::query()->create([
            'evaluation_id' => $evalApproved->id,
            'reviewer_id' => $hoa->id,
            'attitude_score' => 88,
            'notes' => 'Hợp tác tốt với BA khi làm UX table; chủ động hỏi edge case mobile.',
        ]);

        EvaluationPeer::query()->create([
            'evaluation_id' => $evalApproved->id,
            'reviewer_id' => $khoa->id,
            'attitude_score' => 90,
            'notes' => 'Code review kỹ, chia sẻ pattern Vue hợp lý.',
        ]);

        $pTai1 = 81.0;
        $pTai2 = 76.0;
        $pTai3 = 79.0;
        $totalTai = EvaluationScoring::total($pTai1, $pTai2, $pTai3);

        $evalDraft = Evaluation::query()->create([
            'period_type' => 'quarterly',
            'period_label' => '2026-Q1',
            'person_id' => $tai->id,
            'p1' => $pTai1,
            'p2' => $pTai2,
            'p3' => $pTai3,
            'total' => $totalTai,
            'grade' => EvaluationScoring::grade($totalTai),
            'reviewer_id' => $khoa->id,
            'status' => 'draft',
        ]);

        EvaluationPeer::query()->create([
            'evaluation_id' => $evalDraft->id,
            'reviewer_id' => $thinh->id,
            'attitude_score' => 82,
            'notes' => 'Kỹ thuật API rõ ràng; nên cải thiện tài liệu OpenAPI.',
        ]);

        EvaluationPeer::query()->create([
            'evaluation_id' => $evalDraft->id,
            'reviewer_id' => $long->id,
            'attitude_score' => 85,
            'notes' => 'Pair debug hiệu quả trên tích hợp FE.',
        ]);

        $thAnnual = EvaluationScoring::total(90, 88, 86);

        Evaluation::query()->create([
            'period_type' => 'annual',
            'period_label' => '2025',
            'person_id' => $thinh->id,
            'p1' => 90.0,
            'p2' => 88.0,
            'p3' => 86.0,
            'total' => $thAnnual,
            'grade' => EvaluationScoring::grade($thAnnual),
            'reviewer_id' => $mai->id,
            'status' => 'draft',
            'adjustment_delta' => null,
        ]);

        // —— KPI snapshot (minh hoạ trend dashboard; immutable theo tuần) ——
        $morphUser = (new User)->getMorphClass();
        $morphProject = (new Project)->getMorphClass();

        for ($w = 4; $w >= 1; $w--) {
            $weekEnding = Carbon::now()->subWeeks($w)->endOfWeek(Carbon::SUNDAY)->toDateString();
            $perfBase = 72 + (4 - $w) * 2.5;

            foreach ([$tai, $long, $thinh] as $dev) {
                KpiSnapshot::query()->create([
                    'week_ending' => $weekEnding,
                    'entity_type' => $morphUser,
                    'entity_id' => $dev->id,
                    'metric_name' => 'performance_pct',
                    'value' => round($perfBase + ($dev->id % 5), 2),
                    'meta' => ['benchmark_team_avg' => 78.5],
                ]);
            }

            KpiSnapshot::query()->create([
                'week_ending' => $weekEnding,
                'entity_type' => $morphProject,
                'entity_id' => $pPpms->id,
                'metric_name' => 'progress',
                'value' => min(99, 55 + $w * 4),
                'meta' => null,
            ]);
        }

        // —— Thông báo in-app ——
        $mk = fn(string $title, string $body) => ['title' => $title, 'body' => $body];

        PpmsNotification::query()->create([
            'type' => 'project_at_risk',
            'recipient_id' => $kieu->id,
            'channel' => 'in_app',
            'payload' => $mk('Dự án PPMS ở trạng thái At Risk', 'Kiểm tra task UAT và SLA trước họp thứ Hai.'),
            'sent_at' => now()->subHours(3),
        ]);

        PpmsNotification::query()->create([
            'type' => 'task_blocked',
            'recipient_id' => $tai->id,
            'channel' => 'in_app',
            'payload' => $mk('Ticket SSO #4421 đang blocked', 'Cần log từ vendor để unblock — xem dự án LMS.'),
            'sent_at' => now()->subDay(),
        ]);

        PpmsNotification::query()->create([
            'type' => 'kaizen_reminder',
            'recipient_id' => $long->id,
            'channel' => 'in_app',
            'payload' => $mk('Nhắc Kaizen tuần này', 'Gửi ít nhất 1 Kaizen có kết quả đo được.'),
            'sent_at' => now()->subDays(2),
            'read_at' => now()->subDay(),
        ]);

        PpmsNotification::query()->create([
            'type' => 'review_pending',
            'recipient_id' => $khoa->id,
            'channel' => 'in_app',
            'payload' => $mk('Có Kaizen chờ duyệt', 'Thịnh: implemented — cần TL verify.'),
            'sent_at' => now()->subHours(20),
        ]);

        $this->command?->info('PPMS VA Schools: đã seed. Đăng nhập — mật khẩu: password');
        $this->command?->table(
            ['Email', 'Vai trò'],
            [
                [$admin->email, 'admin'],
                [$khoa->email, 'Team Lead'],
                [$tai->email, 'Fullstack Dev 1 (Nguyễn Phát Tài)'],
                [$long->email, 'Fullstack Dev 2 (Trần Hoàng Long)'],
                [$thinh->email, 'Dev 3 — AI/Automation & Kiến trúc (Phạm Quốc Thịnh)'],
                [$kieu->email, 'BA/PM'],
                [$hoa->email, 'BA/PM & UI/UX'],
                [$mai->email, 'HR'],
            ]
        );
    }
}

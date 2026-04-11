<?php

/**
 * Nội dung tham chiếu hiển thị trong UI (nguồn dữ liệu: JSON/DB + i18n, không phụ thuộc file ngoài).
 */
return [
    'framework_version' => '2.0',
    'sheet_map' => [
        'regulations' => '1. Quy Dinh',
        'dev_rubric' => '2. Dev Competency',
        'ba_rubric' => '3. BA-PM-UIUX Competency',
        'level_matrix' => '4. Level Matrix',
        'scorecard' => '5. Bang Cham Diem',
        'kaizen_log' => '6. Kaizen Log',
        'dashboard' => '7. Bao Cao Tong Hop',
    ],

    /** Thang điểm & hệ quả (tóm tắt) */
    'grading_scale' => [
        [
            'grade' => 'S',
            'points' => 5,
            'completion' => '>120%',
            'label' => 'Breakthrough / Đột phá',
            'kaizen_rule' => 'BẮT BUỘC: ≥2 Kaizen Actions triển khai, có số liệu đo lường; ít nhất 1 Kaizen cấp team/công ty.',
            'outcome' => 'Tăng lương ≥15% + Bonus đặc biệt + Xem xét thăng chức + Certificate of Excellence',
        ],
        [
            'grade' => 'A',
            'points' => 4,
            'completion' => '100–110%',
            'label' => 'Above Expectation / Vượt kỳ vọng',
            'kaizen_rule' => 'BẮT BUỘC: ≥1 Kaizen Action trong kỳ, có kết quả đo lường (cá nhân hoặc nhóm nhỏ).',
            'outcome' => 'Tăng lương 8–14% + Bonus hiệu suất + Ưu tiên dự án chiến lược',
        ],
        [
            'grade' => 'B',
            'points' => 3,
            'completion' => '85–99%',
            'label' => 'Standard / Chuẩn mực',
            'kaizen_rule' => 'KHUYẾN KHÍCH: ≥1 ý tưởng cải tiến (chưa triển khai vẫn được).',
            'outcome' => 'Duy trì mức lương + Kế hoạch đào tạo + Review thăng chức sau 2 kỳ B liên tiếp',
        ],
        [
            'grade' => 'C',
            'points' => 2,
            'completion' => '70–84%',
            'label' => 'Below Standard / Chưa đạt chuẩn',
            'kaizen_rule' => 'KHÔNG đủ điều kiện A/S. PIP 30 ngày.',
            'outcome' => 'Cảnh báo chính thức + PIP 30 ngày + Freeze lương',
        ],
        [
            'grade' => 'D',
            'points' => 1,
            'completion' => '<70%',
            'label' => 'Unsatisfactory / Không đạt',
            'kaizen_rule' => 'Kế hoạch khẩn cấp 14 ngày.',
            'outcome' => 'Xem xét chấm dứt HĐ / Điều chuyển / Đào tạo bắt buộc 60 ngày',
        ],
    ],

    /** Career Level Matrix (Junior → Lead) */
    'career_levels' => [
        ['id' => 'junior', 'label_key' => 'evaluationsPage.careerJunior', 'years' => '0–2'],
        ['id' => 'middle', 'label_key' => 'evaluationsPage.careerMiddle', 'years' => '2–4'],
        ['id' => 'senior', 'label_key' => 'evaluationsPage.careerSenior', 'years' => '4–7'],
        ['id' => 'lead', 'label_key' => 'evaluationsPage.careerLead', 'years' => '7+'],
    ],

    'level_matrix' => [
        ['dim' => '1', 'name_key' => 'evaluationsPage.lmCodeQuality', 'junior' => 'Code chạy được, cần review nhiều. Convention đôi khi sai.', 'middle' => 'Code sạch, convention nhất quán. Tự review. Coverage ≥70%.', 'senior' => 'Code elegant, refactor chủ động. Coverage ≥80%. Sonar A.', 'lead' => 'Đặt chuẩn coding cho team. Review là cơ hội dạy. Zero critical security.'],
        ['dim' => '2', 'name_key' => 'evaluationsPage.lmSystemDesign', 'junior' => 'Làm theo design có sẵn. Chưa tự thiết kế phức tạp.', 'middle' => 'Thiết kế module vừa. Biết MVC, Repository.', 'senior' => 'Microservice/module lớn, trade-off, API chuẩn.', 'lead' => 'Kiến trúc toàn sản phẩm, tầm nhìn 6–12 tháng.'],
        ['dim' => '3', 'name_key' => 'evaluationsPage.lmProblemSolving', 'junior' => 'Debug lỗi đơn giản với help.', 'middle' => 'Debug độc lập module quen. RCA cơ bản.', 'senior' => 'Debug phức tạp, 5 Whys, benchmark.', 'lead' => 'Incident production, postmortem, kiến trúc phòng ngừa.'],
        ['dim' => '4', 'name_key' => 'evaluationsPage.lmLangFramework', 'junior' => 'Syntax cơ bản, làm theo tutorial.', 'middle' => 'Best practice, tự tra docs.', 'senior' => 'Idioms, đánh giá thư viện, contribute ví dụ.', 'lead' => 'Expert, patch framework, dạy người khác.'],
        ['dim' => '5', 'name_key' => 'evaluationsPage.lmTesting', 'junior' => 'Unit test cơ bản. Coverage <50%.', 'middle' => 'Unit + integration. Coverage ≥70%.', 'senior' => 'TDD mindset, strategy, ≥80%, helper cho team.', 'lead' => 'Contract/mutation testing, quality gate CI/CD.'],
        ['dim' => '6', 'name_key' => 'evaluationsPage.lmDevOps', 'junior' => 'Deploy theo hướng dẫn, Docker cơ bản.', 'middle' => 'Local env, CI đơn giản, Dockerfile.', 'senior' => 'CI/CD design, K8s, monitoring.', 'lead' => 'IaC, multi-region, DR.'],
        ['dim' => '7', 'name_key' => 'evaluationsPage.lmKaizenMindset', 'junior' => 'Nhận ra lãng phí khi được chỉ.', 'middle' => 'Tự nhận ra Muda, Kaizen cá nhân.', 'senior' => 'Kaizen cho team, đo lường, present.', 'lead' => 'Văn hóa Kaizen, Gemba, Kaizen Board.'],
        ['dim' => '8', 'name_key' => 'evaluationsPage.lmHorenso', 'junior' => 'Báo cáo khi được hỏi. Hỏi khi không hiểu.', 'middle' => 'Báo blocker chủ động, standup rõ, email business.', 'senior' => 'Proactive, manage up, present kỹ thuật.', 'lead' => 'Chiến lược giao tiếp team, C-level, conference.'],
        ['dim' => '9', 'name_key' => 'evaluationsPage.lmMentoring', 'junior' => 'Tự học, nhận mentoring.', 'middle' => 'Hỗ trợ junior, chia sẻ trong team.', 'senior' => 'Mentor 1–2 người, lead discussion.', 'lead' => 'Mentor team, vision, hire, unblock.'],
        ['dim' => '10', 'name_key' => 'evaluationsPage.lmOwnership', 'junior' => 'Cần hướng dẫn chi tiết; task <2 ngày độc lập.', 'middle' => 'Độc lập 1–2 tuần, own một feature.', 'senior' => 'Own module lớn, giải quyết việc không được giao.', 'lead' => 'Strategic ownership, tech direction, long-term.'],
    ],

    /** Quy tắc Kaizen Log (copy i18n) */
    'kaizen_log_policy' => 'Để đạt hạng A (≥4đ) hoặc S (5đ) trong nhóm Person — nhân viên phải có ít nhất 1 mục Kaizen với kết quả đo lường được (Kaizen Log / xác nhận TL/PM). Không có Kaizen đã xác nhận = điểm Person tối đa B (3đ) trong hệ thống tính điểm.',

    /** Gợi ý heatmap (copy i18n) */
    'heatmap_guide' => 'Ô cam/đỏ ≥2 tháng liên tiếp: cảnh báo burnout hoặc skill gap — cần 1:1. Xanh đậm liên tiếp: tiềm năng thăng chức.',

    /** Gợi ý phân bố xếp loại (keys i18n) */
    'distribution_hints' => [
        'S' => 'Xuất sắc — Xem xét thăng chức / tăng lương ưu tiên',
        'A' => 'Vượt kỳ vọng — Giao dự án chiến lược, phát triển tiếp',
        'B' => 'Đạt chuẩn — Ổn định, coaching để lên A',
        'C' => 'Cần coaching, PIP nếu kéo dài',
        'D' => 'Can thiệp khẩn',
    ],
];

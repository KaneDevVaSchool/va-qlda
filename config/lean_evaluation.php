<?php

/**
 * Khung đánh giá Lean Japanese 3P (dùng script build JSON / merge i18n; runtime đọc từ JSON/DB).
 * P1 Position 20% · P2 Person 40% · P3 Performance 40% — thang điểm tiêu chí 1–5 (D→S).
 */
return [
    'pillar_weights' => [
        'p1' => 0.20,
        'p2' => 0.40,
        'p3' => 0.40,
    ],

    /** Xếp loại theo điểm tổng hợp (1–5), bậc trung bình có trọng số. */
    'grade_bands' => [
        ['min' => 4.50, 'grade' => 'S', 'label' => 'Xuất sắc (Breakthrough)'],
        ['min' => 4.00, 'grade' => 'A', 'label' => 'Vượt kỳ vọng'],
        ['min' => 3.00, 'grade' => 'B', 'label' => 'Chuẩn mực'],
        ['min' => 2.00, 'grade' => 'C', 'label' => 'Chưa đạt chuẩn'],
        ['min' => 0.00, 'grade' => 'D', 'label' => 'Cần cải thiện'],
    ],

    'grade_scale_legend' => [
        ['grade' => 'S', 'points' => 5, 'completion' => '>120%', 'summary' => 'Đột phá, Kaizen có đo lường, role model.'],
        ['grade' => 'A', 'points' => 4, 'completion' => '100–110%', 'summary' => 'Vượt kỳ vọng, ≥1 Kaizen có kết quả.'],
        ['grade' => 'B', 'points' => 3, 'completion' => '85–99%', 'summary' => 'Đạt chuẩn kỳ vọng.'],
        ['grade' => 'C', 'points' => 2, 'completion' => '70–84%', 'summary' => 'Cần coaching, PIP nếu kéo dài.'],
        ['grade' => 'D', 'points' => 1, 'completion' => '<70%', 'summary' => 'Không đạt yêu cầu tối thiểu.'],
    ],

    'tracks' => [
        'dev' => [
            'label' => 'Developer',
            'criteria' => [
                // P1 — Position (20%)
                ['id' => 'dev_p1_1', 'pillar' => 'p1', 'weight' => 0.08, 'code' => 'P1.1', 'cluster' => 'P1 — POSITION', 'title' => 'Hiểu domain & nghiệp vụ', 'hint' => 'JD, domain, giải thích logic cho non-tech.', 'frequency' => 'Hàng quý', 'data_source' => 'PM Review + BA Feedback'],
                ['id' => 'dev_p1_2', 'pillar' => 'p1', 'weight' => 0.07, 'code' => 'P1.2', 'cluster' => 'P1 — POSITION', 'title' => 'Thiết kế & kiến trúc', 'hint' => 'DB schema, API, architecture review.', 'frequency' => 'Hàng quý', 'data_source' => 'Tech Lead + Code Review'],
                ['id' => 'dev_p1_3', 'pillar' => 'p1', 'weight' => 0.05, 'code' => 'P1.3', 'cluster' => 'P1 — POSITION', 'title' => 'Security awareness', 'hint' => 'OWASP, Sonar, coverage.', 'frequency' => 'Hàng quý', 'data_source' => 'Security Scan + PR Review'],
                ['id' => 'dev_p2_1', 'pillar' => 'p2', 'weight' => 0.05, 'code' => 'P2.1', 'cluster' => 'P2 — Person (Hard)', 'title' => 'Ngôn ngữ / stack chính', 'hint' => 'Clean code, paradigm, idiomatic.', 'frequency' => 'Hàng quý', 'data_source' => 'Code Review + Tech Assessment'],
                ['id' => 'dev_p2_2', 'pillar' => 'p2', 'weight' => 0.05, 'code' => 'P2.2', 'cluster' => 'P2 — Person (Hard)', 'title' => 'Framework & ecosystem', 'hint' => 'Lifecycle, best practice.', 'frequency' => 'Hàng quý', 'data_source' => 'PR Review + Tech Demo'],
                ['id' => 'dev_p2_3', 'pillar' => 'p2', 'weight' => 0.05, 'code' => 'P2.3', 'cluster' => 'P2 — Person (Hard)', 'title' => 'Database & data layer', 'hint' => 'Query, index, N+1.', 'frequency' => 'Hàng quý', 'data_source' => 'DBA Review + Query Log'],
                ['id' => 'dev_p2_4', 'pillar' => 'p2', 'weight' => 0.05, 'code' => 'P2.4', 'cluster' => 'P2 — Person (Hard)', 'title' => 'DevOps & tooling', 'hint' => 'CI/CD, Docker, Git.', 'frequency' => 'Hàng quý', 'data_source' => 'DevOps Lead + Git History'],
                ['id' => 'dev_p2_5', 'pillar' => 'p2', 'weight' => 0.05, 'code' => 'P2.5', 'cluster' => 'P2 — Person (Hard)', 'title' => 'Testing & chất lượng', 'hint' => 'Coverage, Sonar, security.', 'frequency' => 'Hàng quý', 'data_source' => 'SonarQube + Security Scan'],
                ['id' => 'dev_p2_6', 'pillar' => 'p2', 'weight' => 0.08, 'code' => 'P2.6', 'cluster' => 'P2 — Person (Kaizen)', 'title' => 'Kaizen — cải tiến', 'hint' => '≥1 cải tiến có đo lường.', 'frequency' => 'Hàng quý', 'data_source' => 'Kaizen Log (module Kaizen / TL xác nhận)'],
                ['id' => 'dev_p2_7', 'pillar' => 'p2', 'weight' => 0.07, 'code' => 'P2.7', 'cluster' => 'P2 — Person (Soft)', 'title' => 'Horenso & teamwork', 'hint' => 'Standup, PR feedback 24h, peer.', 'frequency' => 'Hàng tháng', 'data_source' => 'Daily Log + PM Feedback + 360°'],
                ['id' => 'dev_p3_1', 'pillar' => 'p3', 'weight' => 0.10, 'code' => 'P3.1', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Tiến độ task / timeline', 'hint' => 'On-time rate theo sprint.', 'frequency' => 'Hàng sprint', 'data_source' => 'Jira / Linear Dashboard'],
                ['id' => 'dev_p3_2', 'pillar' => 'p3', 'weight' => 0.10, 'code' => 'P3.2', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Chất lượng / bug rate', 'hint' => 'Bug index theo SP.', 'frequency' => 'Hàng sprint', 'data_source' => 'QA Report + Bug Tracker'],
                ['id' => 'dev_p3_3', 'pillar' => 'p3', 'weight' => 0.10, 'code' => 'P3.3', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Code review & đóng góp', 'hint' => 'Peer NPS / review PR.', 'frequency' => 'Hàng quý', 'data_source' => '360° Survey (ẩn danh)'],
                ['id' => 'dev_p3_4', 'pillar' => 'p3', 'weight' => 0.10, 'code' => 'P3.4', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Đóng góp kỹ thuật', 'hint' => 'PR merge, lib nội bộ, ADR.', 'frequency' => 'Hàng quý', 'data_source' => 'GitHub / GitLab Stats'],
            ],
        ],
        'ba_pm_uiux' => [
            'label' => 'BA / PM / UI-UX',
            /** Trọng số chia đều trong từng trụ P1/P2/P3 (20% / 40% / 40%). */
            'equal_weights_per_pillar' => true,
            'criteria' => [
                ['id' => 'ba_p1_1', 'pillar' => 'p1', 'code' => 'P1.1', 'cluster' => 'P1 — POSITION', 'title' => 'Domain & vai trò', 'hint' => 'Hiểu domain, stakeholder, RACI.', 'frequency' => 'Hàng quý', 'data_source' => 'PM Review + Client Feedback'],
                ['id' => 'ba_p1_2', 'pillar' => 'p1', 'code' => 'P1.2', 'cluster' => 'P1 — POSITION', 'title' => 'Scope & change control', 'hint' => 'Baseline, CR, MoSCoW.', 'frequency' => 'Hàng quý', 'data_source' => 'Change Log + Project Report'],
                ['id' => 'ba_p1_3', 'pillar' => 'p1', 'code' => 'P1.3', 'cluster' => 'P1 — POSITION', 'title' => 'Stakeholder management', 'hint' => 'Communication plan, NPS.', 'frequency' => 'Hàng quý', 'data_source' => 'Stakeholder Survey + PM Review'],
                ['id' => 'ba_p2_1', 'pillar' => 'p2', 'code' => 'P2.1', 'cluster' => 'P2A — BA', 'title' => 'Requirements / research', 'hint' => 'User story, AC, workshop.', 'frequency' => 'Hàng sprint', 'data_source' => 'PRD Review + Dev/QA Feedback'],
                ['id' => 'ba_p2_2', 'pillar' => 'p2', 'code' => 'P2.2', 'cluster' => 'P2A — BA', 'title' => 'Tài liệu & thiết kế', 'hint' => 'Flow, ERD, sequence.', 'frequency' => 'Hàng sprint', 'data_source' => 'Dev Survey + Doc Review'],
                ['id' => 'ba_p2_3', 'pillar' => 'p2', 'code' => 'P2.3', 'cluster' => 'P2A — BA', 'title' => 'Kỹ thuật BA / nâng cao', 'hint' => '5 Whys, SWOT, gap analysis.', 'frequency' => 'Hàng quý', 'data_source' => 'Analysis Deliverable Review'],
                ['id' => 'ba_p2_4', 'pillar' => 'p2', 'code' => 'P2.4', 'cluster' => 'P2B — PM', 'title' => 'Lập kế hoạch / Agile', 'hint' => 'WBS, milestone, velocity.', 'frequency' => 'Hàng sprint', 'data_source' => 'Project Plan + Jira Report'],
                ['id' => 'ba_p2_5', 'pillar' => 'p2', 'code' => 'P2.5', 'cluster' => 'P2B — PM', 'title' => 'Risk / UX flow', 'hint' => 'Risk register, RAID, IA.', 'frequency' => 'Hàng sprint', 'data_source' => 'Risk Register + Sprint Retro'],
                ['id' => 'ba_p2_6', 'pillar' => 'p2', 'code' => 'P2.6', 'cluster' => 'P2D — Kaizen', 'title' => 'Kaizen quy trình', 'hint' => 'Cải tiến có đo lường.', 'frequency' => 'Hàng quý', 'data_source' => 'Kaizen Log + TL/PM'],
                ['id' => 'ba_p2_7', 'pillar' => 'p2', 'code' => 'P2.7', 'cluster' => 'P2D — Soft skills', 'title' => 'Horenso & product sense', 'hint' => 'Data-driven, executive summary.', 'frequency' => 'Hàng tháng', 'data_source' => 'Meeting Log + Metrics Dashboard'],
                ['id' => 'ba_p3_1', 'pillar' => 'p3', 'code' => 'P3.1', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Milestone / sprint', 'hint' => 'Timeline adherence.', 'frequency' => 'Hàng sprint', 'data_source' => 'Project Report + Jira'],
                ['id' => 'ba_p3_2', 'pillar' => 'p3', 'code' => 'P3.2', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Chất lượng deliverable', 'hint' => 'PRD/Design approval rate.', 'frequency' => 'Hàng sprint', 'data_source' => 'QA/Dev Feedback + Survey'],
                ['id' => 'ba_p3_3', 'pillar' => 'p3', 'code' => 'P3.3', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'CSAT / stakeholder', 'hint' => 'Client & team NPS.', 'frequency' => 'Hàng quý', 'data_source' => 'Survey ẩn danh + Client Interview'],
                ['id' => 'ba_p3_4', 'pillar' => 'p3', 'code' => 'P3.4', 'cluster' => 'P3 — PERFORMANCE', 'title' => 'Học hỏi & phát triển', 'hint' => 'Cert, talk, portfolio.', 'frequency' => 'Hàng quý', 'data_source' => 'HR Learning Log + Portfolio'],
            ],
        ],
    ],

    /** Ngũ giác Kaizen — trục radar = nhóm tiêu chí. */
    'radar_axes' => [
        ['id' => 'position', 'label_key' => 'evaluationsPage.radarPosition', 'pillar' => 'p1'],
        ['id' => 'craft', 'label_key' => 'evaluationsPage.radarCraft', 'pillar' => 'p2'],
        ['id' => 'kaizen', 'label_key' => 'evaluationsPage.radarKaizen', 'pillar' => 'p2'],
        ['id' => 'collab', 'label_key' => 'evaluationsPage.radarHorenso', 'pillar' => 'p2'],
        ['id' => 'delivery', 'label_key' => 'evaluationsPage.radarDelivery', 'pillar' => 'p3'],
    ],
];

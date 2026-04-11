<?php

/**
 * Build database/data/lean_framework.json từ config PHP (công cụ dev).
 * Ứng dụng không phụ thuộc Excel; không đọc .xlsx tại đây.
 */
$le = require __DIR__.'/../config/lean_evaluation.php';
$ex = require __DIR__.'/../config/lean_evaluation_extras.php';

$strip = function (array $criteria) {
    $out = [];
    foreach ($criteria as $row) {
        $out[] = array_intersect_key($row, array_flip(['id', 'pillar', 'weight', 'code']));
    }

    return $out;
};

$tracks = [];
foreach ($le['tracks'] as $slug => $def) {
    $tracks[$slug] = [
        'label_key' => $slug === 'dev' ? 'evaluationsPage.trackDev' : 'evaluationsPage.trackBa',
        'criteria' => $strip($def['criteria']),
    ];
    if (! empty($def['equal_weights_per_pillar'])) {
        $tracks[$slug]['equal_weights_per_pillar'] = true;
    }
}

$radar = [
    'dev' => [
        'position' => ['dev_p1_1', 'dev_p1_2', 'dev_p1_3'],
        'craft' => ['dev_p2_1', 'dev_p2_2', 'dev_p2_3', 'dev_p2_4', 'dev_p2_5'],
        'kaizen' => ['dev_p2_6'],
        'collab' => ['dev_p2_7'],
        'delivery' => ['dev_p3_1', 'dev_p3_2', 'dev_p3_3', 'dev_p3_4'],
    ],
    'ba_pm_uiux' => [
        'position' => ['ba_p1_1', 'ba_p1_2', 'ba_p1_3'],
        'craft' => ['ba_p2_1', 'ba_p2_2', 'ba_p2_3', 'ba_p2_4', 'ba_p2_5'],
        'kaizen' => ['ba_p2_6'],
        'collab' => ['ba_p2_7'],
        'delivery' => ['ba_p3_1', 'ba_p3_2', 'ba_p3_3', 'ba_p3_4'],
    ],
];

$gradeLegend = [];
foreach ($le['grade_scale_legend'] as $row) {
    $g = $row['grade'];
    $gradeLegend[] = [
        'grade' => $g,
        'points' => $row['points'],
        'completion_key' => 'evaluationsPage.legendRows.'.$g.'.completion',
        'summary_key' => 'evaluationsPage.legendRows.'.$g.'.summary',
    ];
}

$gs = [];
foreach ($ex['grading_scale'] as $row) {
    $gs[] = ['grade' => $row['grade'], 'points' => $row['points']];
}

$lm = [];
foreach ($ex['level_matrix'] as $row) {
    $lm[] = ['dim' => $row['dim'], 'name_key' => $row['name_key']];
}

$dh = [];
foreach ($ex['distribution_hints'] as $k => $v) {
    $dh[$k] = 'evaluationsPage.distHint.'.$k;
}

$sm = [];
foreach ($ex['sheet_map'] as $k => $v) {
    $sm[$k] = 'evaluationsPage.sheetMap.'.$k;
}

$cl = [];
foreach ($ex['career_levels'] as $row) {
    $cl[] = [
        'id' => $row['id'],
        'label_key' => $row['label_key'],
        'years_key' => 'evaluationsPage.careerYears.'.$row['id'],
    ];
}

$gradeBands = [];
foreach ($le['grade_bands'] as $row) {
    $g = $row['grade'];
    $gradeBands[] = [
        'min' => $row['min'],
        'grade' => $g,
        'label_key' => 'evaluationsPage.gradeBands.'.$g,
    ];
}

$payload = [
    'version' => '2.0',
    'pillar_weights' => $le['pillar_weights'],
    'grade_bands' => $gradeBands,
    'grade_scale_legend' => $gradeLegend,
    'tracks' => $tracks,
    'radar_axes' => $le['radar_axes'],
    'radar_criteria_map' => $radar,
    'extras' => [
        'framework_version' => $ex['framework_version'] ?? $ex['excel_version'] ?? '2.0',
        'sheet_map_keys' => $sm,
        'grading_scale' => $gs,
        'level_matrix' => $lm,
        'career_levels' => $cl,
        'distribution_hint_keys' => $dh,
        'kaizen_log_policy_key' => 'evaluationsPage.kaizenLogPolicy',
        'heatmap_guide_key' => 'evaluationsPage.heatmapGuideFull',
    ],
];

$dir = __DIR__.'/../database/data';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$path = $dir.'/lean_framework.json';
file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Wrote $path (".filesize($path)." bytes)\n";

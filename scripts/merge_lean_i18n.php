<?php

/**
 * Một lần / khi cần: đồng bộ chuỗi từ config PHP vào vi.json (sau đó chỉ sửa JSON i18n).
 * Không đọc file Excel — runtime chỉ dùng JSON/DB + i18n.
 */
$le = require __DIR__.'/../config/lean_evaluation.php';
$ex = require __DIR__.'/../config/lean_evaluation_extras.php';

$viPath = __DIR__.'/../resources/js/i18n/locales/vi.json';
$enPath = __DIR__.'/../resources/js/i18n/locales/en.json';

$vi = json_decode(file_get_contents($viPath), true, 512, JSON_THROW_ON_ERROR);
$en = json_decode(file_get_contents($enPath), true, 512, JSON_THROW_ON_ERROR);

$criteriaVi = [];
$criteriaEn = [];
foreach ($le['tracks'] as $def) {
    foreach ($def['criteria'] as $row) {
        $id = $row['id'];
        $criteriaVi[$id] = [
            'title' => $row['title'] ?? '',
            'hint' => $row['hint'] ?? '',
            'cluster' => $row['cluster'] ?? '',
            'frequency' => $row['frequency'] ?? '',
            'dataSource' => $row['data_source'] ?? '',
        ];
        $criteriaEn[$id] = [
            'title' => $row['title'] ?? '',
            'hint' => $row['hint'] ?? '',
            'cluster' => $row['cluster'] ?? '',
            'frequency' => $row['frequency'] ?? '',
            'dataSource' => $row['data_source'] ?? '',
        ];
    }
}

$vi['evaluationsPage']['criteria'] = $criteriaVi;
$en['evaluationsPage']['criteria'] = $criteriaEn;

$legendVi = $legendEn = [];
foreach ($le['grade_scale_legend'] as $row) {
    $g = $row['grade'];
    $legendVi[$g] = [
        'completion' => $row['completion'],
        'summary' => $row['summary'],
    ];
    $legendEn[$g] = [
        'completion' => $row['completion'],
        'summary' => $row['summary'],
    ];
}
$vi['evaluationsPage']['legendRows'] = $legendVi;
$en['evaluationsPage']['legendRows'] = $legendEn;

$gbVi = $gbEn = [];
foreach ($le['grade_bands'] as $row) {
    $gbVi[$row['grade']] = $row['label'];
    $gbEn[$row['grade']] = $row['label'];
}
$vi['evaluationsPage']['gradeBands'] = $gbVi;
$en['evaluationsPage']['gradeBands'] = $gbEn;

$gdVi = $gdEn = [];
foreach ($ex['grading_scale'] as $row) {
    $g = $row['grade'];
    $gdVi[$g] = [
        'completion' => $row['completion'] ?? '',
        'label' => $row['label'] ?? '',
        'kaizen_rule' => $row['kaizen_rule'] ?? '',
        'outcome' => $row['outcome'] ?? '',
    ];
    $gdEn[$g] = $gdVi[$g];
}
$vi['evaluationsPage']['gradingDetail'] = $gdVi;
$en['evaluationsPage']['gradingDetail'] = $gdEn;

$lmVi = $lmEn = [];
foreach ($ex['level_matrix'] as $row) {
    $d = 'dim'.$row['dim'];
    $lmVi[$d] = [
        'junior' => $row['junior'],
        'middle' => $row['middle'],
        'senior' => $row['senior'],
        'lead' => $row['lead'],
    ];
    $lmEn[$d] = $lmVi[$d];
}
$vi['evaluationsPage']['levelMatrix'] = $lmVi;
$en['evaluationsPage']['levelMatrix'] = $lmEn;

$vi['evaluationsPage']['kaizenLogPolicy'] = $ex['kaizen_log_policy'];
$en['evaluationsPage']['kaizenLogPolicy'] = $ex['kaizen_log_policy'];

$vi['evaluationsPage']['heatmapGuideFull'] = $ex['heatmap_guide'];
$en['evaluationsPage']['heatmapGuideFull'] = $ex['heatmap_guide'];

$dhVi = $dhEn = [];
foreach ($ex['distribution_hints'] as $k => $v) {
    $dhVi[$k] = $v;
    $dhEn[$k] = $v;
}
$vi['evaluationsPage']['distHint'] = $dhVi;
$en['evaluationsPage']['distHint'] = $dhEn;

$smVi = $smEn = [];
foreach ($ex['sheet_map'] as $k => $v) {
    $smVi[$k] = $v;
    $smEn[$k] = $v;
}
$vi['evaluationsPage']['sheetMap'] = $smVi;
$en['evaluationsPage']['sheetMap'] = $smEn;

$cyVi = ['junior' => '0–2', 'middle' => '2–4', 'senior' => '4–7', 'lead' => '7+'];
$cyEn = $cyVi;
$vi['evaluationsPage']['careerYears'] = $cyVi;
$en['evaluationsPage']['careerYears'] = $cyEn;

file_put_contents($viPath, json_encode($vi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
file_put_contents($enPath, json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

echo "Merged lean i18n into vi.json and en.json\n";

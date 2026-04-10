<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Support\Str;

/**
 * Mã dự án mặc định khi tạo: 2 chữ cái từ tên nhóm + số thứ tự 6 chữ số (vd. AS-000001, HW-000001).
 * - Hai từ trở lên: chữ cái đầu của 2 từ đầu (Application Software → AS).
 * - Một từ: chia đôi chuỗi, lấy chữ đầu mỗi nửa (Hardware → Hard|ware → HW).
 * Không có nhóm: giữ PRJ-000001 theo id.
 */
class ProjectCodeGenerator
{
    public static function assignIfEmpty(Project $project): void
    {
        if (trim((string) $project->code) !== '') {
            return;
        }

        $team = null;
        if ($project->team_id) {
            $team = Team::query()->find($project->team_id);
        }

        if ($team && trim((string) $team->name) !== '') {
            $prefix = self::twoLetterPrefixFromTeamName($team->name);
            $project->code = self::nextCodeForPrefix($prefix);
        } else {
            $project->code = 'PRJ-'.str_pad((string) $project->id, 6, '0', STR_PAD_LEFT);
        }

        $project->save();
    }

    /**
     * Luôn trả đúng 2 ký tự A–Z (fallback X).
     */
    public static function twoLetterPrefixFromTeamName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'PR';
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) >= 2) {
            $a = self::asciiLetterFromChar(mb_substr($words[0], 0, 1));
            $b = self::asciiLetterFromChar(mb_substr($words[1], 0, 1));

            return strtoupper($a.$b);
        }

        $w = $words[0] ?? '';
        $len = mb_strlen($w);
        if ($len < 1) {
            return 'PR';
        }
        if ($len === 1) {
            $a = self::asciiLetterFromChar(mb_substr($w, 0, 1));

            return strtoupper($a.'X');
        }

        $mid = (int) floor($len / 2);
        if ($mid < 1) {
            $mid = 1;
        }
        $left = mb_substr($w, 0, $mid);
        $right = mb_substr($w, $mid);
        $a = self::asciiLetterFromChar(mb_substr($left, 0, 1));
        $b = self::asciiLetterFromChar(mb_substr($right, 0, 1));

        return strtoupper($a.$b);
    }

    private static function asciiLetterFromChar(string $char): string
    {
        $c = mb_substr($char, 0, 1);
        $ascii = Str::ascii($c);
        $ascii = preg_replace('/[^A-Za-z]/', '', $ascii ?? '');

        return $ascii !== '' ? strtoupper($ascii[0]) : 'X';
    }

    public static function nextCodeForPrefix(string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $prefix) ?? '');
        if (strlen($prefix) < 2) {
            $prefix = str_pad($prefix, 2, 'X');
        }
        $prefix = substr($prefix, 0, 2);

        $like = $prefix.'-%';
        $maxSeq = 0;
        foreach (Project::query()->where('code', 'like', $like)->pluck('code') as $code) {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d{6})$/', (string) $code, $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }

        $next = $maxSeq + 1;
        if ($next > 999999) {
            throw new \RuntimeException('Project code sequence overflow for prefix '.$prefix);
        }

        return $prefix.'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}

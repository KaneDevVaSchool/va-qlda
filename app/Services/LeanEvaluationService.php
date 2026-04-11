<?php

namespace App\Services;

class LeanEvaluationService
{
    /** @return array<string, mixed> */
    private static function fw(): array
    {
        return app(LeanFrameworkConfigService::class)->payload();
    }

    /**
     * @return array{criteria: list<array<string, mixed>>}
     */
    public static function criteriaForTrack(string $track): array
    {
        $tracks = self::fw()['tracks'] ?? [];
        if (! isset($tracks[$track])) {
            return ['criteria' => []];
        }
        $def = $tracks[$track];
        $pillars = self::fw()['pillar_weights'] ?? [];
        $out = [];
        foreach ($def['criteria'] as $row) {
            $w = $row['weight'] ?? null;
            if ($w === null && ! empty($def['equal_weights_per_pillar'])) {
                $pillar = $row['pillar'];
                $same = array_filter($def['criteria'], fn ($r) => $r['pillar'] === $pillar);
                $n = max(1, count($same));
                $w = (float) ($pillars[$pillar] ?? 0) / $n;
            }
            if ($w === null) {
                continue;
            }
            $out[] = array_merge($row, ['weight' => round((float) $w, 6)]);
        }

        return ['criteria' => $out];
    }

    /**
     * @param  array<string, int|float|string>  $scores  criterion_id => 1..5
     * @return array{p1: ?float, p2: ?float, p2_raw: ?float, p3: ?float, total: ?float, grade: ?string, radar: array<string, float>, criteria_filled: int, criteria_total: int, kaizen_cap_applied: bool}
     */
    public static function compute(string $track, array $scores, bool $kaizenVerified = true): array
    {
        $resolved = self::criteriaForTrack($track);
        $criteria = $resolved['criteria'];
        $byPillar = ['p1' => [], 'p2' => [], 'p3' => []];
        $weightSum = ['p1' => 0.0, 'p2' => 0.0, 'p3' => 0.0];
        $weighted = ['p1' => 0.0, 'p2' => 0.0, 'p3' => 0.0];

        foreach ($criteria as $c) {
            $id = $c['id'];
            $pillar = $c['pillar'];
            if (! isset($scores[$id]) || $scores[$id] === '' || $scores[$id] === null) {
                continue;
            }
            $s = (float) $scores[$id];
            $s = max(1.0, min(5.0, $s));
            $w = (float) $c['weight'];
            $weighted[$pillar] += $s * $w;
            $weightSum[$pillar] += $w;
            $byPillar[$pillar][] = ['s' => $s, 'w' => $w];
        }

        $pillars = self::fw()['pillar_weights'] ?? [];
        $p1 = $weightSum['p1'] > 0 ? $weighted['p1'] / $weightSum['p1'] : null;
        $p2Raw = $weightSum['p2'] > 0 ? $weighted['p2'] / $weightSum['p2'] : null;
        $p3 = $weightSum['p3'] > 0 ? $weighted['p3'] / $weightSum['p3'] : null;

        $kaizenCapApplied = false;
        $p2 = $p2Raw;
        if ($p2 !== null && ! $kaizenVerified && $p2 > 3.0) {
            $p2 = 3.0;
            $kaizenCapApplied = true;
        }
        if ($p2 !== null) {
            $p2 = round($p2, 2);
        }
        if ($p2Raw !== null) {
            $p2Raw = round($p2Raw, 2);
        }

        $total = null;
        if ($p1 !== null && $p2 !== null && $p3 !== null) {
            $total =
                ($p1 * (float) $pillars['p1'])
                + ($p2 * (float) $pillars['p2'])
                + ($p3 * (float) $pillars['p3']);
            $total = round($total, 2);
        }

        $grade = $total !== null ? self::gradeLean($total) : null;

        $radar = self::radarSnapshot($track, $scores);

        return [
            'p1' => $p1 !== null ? round($p1, 2) : null,
            'p2' => $p2,
            'p2_raw' => $p2Raw,
            'p3' => $p3 !== null ? round($p3, 2) : null,
            'total' => $total,
            'grade' => $grade,
            'radar' => $radar,
            'criteria_filled' => count(array_filter($criteria, fn ($c) => isset($scores[$c['id']]) && $scores[$c['id']] !== '' && $scores[$c['id']] !== null)),
            'criteria_total' => count($criteria),
            'kaizen_cap_applied' => $kaizenCapApplied,
        ];
    }

    public static function gradeLean(float $total15): string
    {
        foreach (self::fw()['grade_bands'] ?? [] as $band) {
            if ($total15 >= (float) $band['min']) {
                return (string) $band['grade'];
            }
        }

        return 'D';
    }

    /**
     * @param  array<string, int|float>  $scores
     * @return array<string, float>
     */
    public static function radarSnapshot(string $track, array $scores): array
    {
        $map = self::fw()['radar_criteria_map'][$track] ?? [];

        $out = [];
        foreach ($map as $axis => $ids) {
            $vals = [];
            foreach ($ids as $id) {
                if (isset($scores[$id]) && $scores[$id] !== '' && $scores[$id] !== null) {
                    $vals[] = max(1.0, min(5.0, (float) $scores[$id]));
                }
            }
            $out[$axis] = count($vals) ? round(array_sum($vals) / count($vals), 2) : 0.0;
        }

        return $out;
    }
}

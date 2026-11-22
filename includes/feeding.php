<?php
declare(strict_types=1);

/*
 |-------------------------------------------------------------
 | Feeding guide: calculation and display helpers
 |-------------------------------------------------------------
 | The maths is kept in plain functions that take arrays (no database)
 | so it can be unit-tested: php tests/feeding_test.php
 |
 | Methods (feeding_plans.method):
 |   energy     amount = factor x RER, RER = 70 x kg^0.75 kcal/day   (dogs, cats)
 |   pct_dm     amount = % of body weight as dry matter per day       (ruminants, horses)
 |   pct_asfed  amount = % of body weight as fed per day              (e.g. rabbit pellets)
 |   fixed      amount = grams per animal per day                     (e.g. laying hen)
 |   table      amount = grams per day interpolated by body weight    (e.g. growing pigs)
 */

const FEED_CATEGORIES = [
    'green_forage' => 'Green forage',
    'dry_forage' => 'Dry forage and crop residues',
    'concentrate' => 'Concentrates and protein feeds',
    'complete_feed' => 'Complete manufactured feeds',
    'kitchen_food' => 'Kitchen and fresh foods',
    'supplement' => 'Minerals and supplements',
];

const FEEDING_METHODS = [
    'energy' => 'Energy (multiple of resting energy requirement)',
    'pct_dm' => '% of body weight as dry matter',
    'pct_asfed' => '% of body weight as fed',
    'fixed' => 'Fixed grams per animal per day',
    'table' => 'Table of grams per day by body weight',
];

const UNSAFE_SEVERITIES = [
    'toxic' => 'Toxic: never feed',
    'avoid' => 'Avoid',
    'limit' => 'Limit, or introduce carefully',
];

/** Resting energy requirement in kcal/day: 70 x (body weight in kg) to the power 0.75. */
function feeding_resting_energy(float $weightKg): float
{
    return 70.0 * ($weightKg ** 0.75);
}

/** True when a body weight lies inside a plan's weight band (a missing limit means "no limit"). */
function feeding_plan_applies(array $plan, float $weightKg): bool
{
    $min = $plan['min_weight_kg'] ?? null;
    $max = $plan['max_weight_kg'] ?? null;
    if ($min !== null && $min !== '' && $weightKg < (float) $min) {
        return false;
    }
    if ($max !== null && $max !== '' && $weightKg > (float) $max) {
        return false;
    }
    return true;
}

/**
 * Straight-line interpolation of a weight/amount table.
 * Returns null when the weight is outside the table (no extrapolation).
 *
 * @param list<array{weight_kg:float|string,amount_g:float|string}> $points
 */
function feeding_interpolate(array $points, float $weightKg): ?float
{
    $n = count($points);
    if ($n < 2) {
        return null;
    }
    usort($points, static fn (array $a, array $b): int => (float) $a['weight_kg'] <=> (float) $b['weight_kg']);
    $first = (float) $points[0]['weight_kg'];
    $last = (float) $points[$n - 1]['weight_kg'];
    if ($weightKg < $first - 1e-9 || $weightKg > $last + 1e-9) {
        return null;
    }
    for ($i = 0; $i < $n - 1; $i++) {
        $w1 = (float) $points[$i]['weight_kg'];
        $w2 = (float) $points[$i + 1]['weight_kg'];
        if ($weightKg <= $w2 + 1e-9) {
            $a1 = (float) $points[$i]['amount_g'];
            $a2 = (float) $points[$i + 1]['amount_g'];
            $span = $w2 - $w1;
            if ($span <= 0) {
                return $a2;
            }
            return $a1 + (($weightKg - $w1) / $span) * ($a2 - $a1);
        }
    }
    return (float) $points[$n - 1]['amount_g'];
}

/**
 * The animal's total daily target for a plan.
 *
 * @param array<string, mixed> $plan a feeding_plans row
 * @param list<array<string, mixed>> $points rows of feeding_plan_points (method 'table' only)
 * @return array{ok:bool, error?:string, kind?:string, unit?:string, min?:float, max?:float, typical?:float, rer?:float}
 */
function feeding_plan_target(array $plan, array $points, float $weightKg): array
{
    $min = isset($plan['amount_min']) && $plan['amount_min'] !== '' ? (float) $plan['amount_min'] : null;
    $max = isset($plan['amount_max']) && $plan['amount_max'] !== '' ? (float) $plan['amount_max'] : null;
    $typ = isset($plan['amount_typical']) && $plan['amount_typical'] !== '' ? (float) $plan['amount_typical'] : null;
    $method = (string) ($plan['method'] ?? '');

    if ($method === 'table') {
        $value = feeding_interpolate($points, $weightKg);
        if ($value === null) {
            return ['ok' => false, 'error' => 'This body weight is outside the weight range covered by the table for this plan.'];
        }
        return ['ok' => true, 'kind' => 'grams', 'unit' => 'g', 'min' => $value, 'max' => $value, 'typical' => $value];
    }

    if ($min === null || $max === null) {
        return ['ok' => false, 'error' => 'This plan has no amounts yet.'];
    }
    $typ ??= ($min + $max) / 2;

    switch ($method) {
        case 'energy':
            $rer = feeding_resting_energy($weightKg);
            return ['ok' => true, 'kind' => 'energy', 'unit' => 'kcal', 'min' => $min * $rer, 'max' => $max * $rer, 'typical' => $typ * $rer, 'rer' => $rer];
        case 'pct_dm':
            return ['ok' => true, 'kind' => 'dm', 'unit' => 'kg', 'min' => $weightKg * $min / 100, 'max' => $weightKg * $max / 100, 'typical' => $weightKg * $typ / 100];
        case 'pct_asfed':
            return ['ok' => true, 'kind' => 'grams', 'unit' => 'g', 'min' => $weightKg * $min * 10, 'max' => $weightKg * $max * 10, 'typical' => $weightKg * $typ * 10];
        case 'fixed':
            return ['ok' => true, 'kind' => 'grams', 'unit' => 'g', 'min' => $min, 'max' => $max, 'typical' => $typ];
    }
    return ['ok' => false, 'error' => 'Unknown calculation method.'];
}

/**
 * Turn a plan's total target into amounts of each feed, grouped by diet option.
 *
 * Each item row needs: diet_name, feed_id, feed_name, feed_category, share_percent, fixed_min_g,
 * fixed_max_g, notes and (as available) dry_matter_percent, crude_protein_percent, me_mj_per_kg_dm,
 * energy_kcal_per_kg.
 *
 * @param array<string, mixed> $plan
 * @param array<string, mixed> $target result of feeding_plan_target() (must be ok)
 * @param list<array<string, mixed>> $items
 * @param array{diet:string,kcal:float}|null $energyOverride replaces the energy density of the
 *        first complete manufactured feed in the named diet (the value from the product label)
 * @return list<array<string, mixed>>
 */
function feeding_build_diets(array $plan, array $target, array $items, ?array $energyOverride = null): array
{
    $meals = max(1, (int) ($plan['meals_per_day'] ?? 1));
    $diets = [];

    foreach ($items as $item) {
        $diets[$item['diet_name']][] = $item;
    }

    $result = [];
    foreach ($diets as $dietName => $dietItems) {
        $overrideApplied = false;
        $rows = [];
        $cpSum = 0.0;
        $meSum = 0.0;
        $shareSum = 0.0;
        $allHaveCp = true;
        $allHaveMe = true;

        foreach ($dietItems as $item) {
            $share = $item['share_percent'] !== null && $item['share_percent'] !== '' ? (float) $item['share_percent'] : null;
            $fixedMin = $item['fixed_min_g'] !== null && $item['fixed_min_g'] !== '' ? (float) $item['fixed_min_g'] : null;
            $fixedMax = $item['fixed_max_g'] !== null && $item['fixed_max_g'] !== '' ? (float) $item['fixed_max_g'] : null;

            $row = [
                'feed_id' => (int) $item['feed_id'],
                'feed_name' => (string) $item['feed_name'],
                'feed_category' => (string) ($item['feed_category'] ?? ''),
                'share' => $share,
                'notes' => (string) ($item['notes'] ?? ''),
                'mode' => 'free', // free | fixed | amount | dm_only | no_data
                'min_g' => null, 'max_g' => null, 'typical_g' => null,
                'dm_kg_typical' => null,
                'kcal_per_kg_used' => null,
                'override_used' => false,
            ];

            if ($fixedMin !== null && $fixedMax !== null) {
                $row['mode'] = 'fixed';
                $row['min_g'] = $fixedMin;
                $row['max_g'] = $fixedMax;
                $row['typical_g'] = ($fixedMin + $fixedMax) / 2;
            } elseif ($share !== null) {
                $fraction = $share / 100;
                if ($target['kind'] === 'energy') {
                    $kcalPerKg = isset($item['energy_kcal_per_kg']) && $item['energy_kcal_per_kg'] !== null ? (float) $item['energy_kcal_per_kg'] : null;
                    if ($energyOverride !== null && !$overrideApplied && $energyOverride['diet'] === $dietName
                        && ($item['feed_category'] ?? '') === 'complete_feed' && $energyOverride['kcal'] > 0) {
                        $kcalPerKg = $energyOverride['kcal'];
                        $overrideApplied = true;
                        $row['override_used'] = true;
                    }
                    if ($kcalPerKg !== null && $kcalPerKg > 0) {
                        $row['mode'] = 'amount';
                        $row['kcal_per_kg_used'] = $kcalPerKg;
                        foreach (['min', 'max', 'typical'] as $k) {
                            $row[$k . '_g'] = $target[$k] * $fraction / $kcalPerKg * 1000;
                        }
                    } else {
                        $row['mode'] = 'no_data';
                    }
                } elseif ($target['kind'] === 'dm') {
                    $dmPercent = isset($item['dry_matter_percent']) && $item['dry_matter_percent'] !== null ? (float) $item['dry_matter_percent'] : null;
                    $row['dm_kg_typical'] = $target['typical'] * $fraction;
                    if ($dmPercent !== null && $dmPercent > 0) {
                        $row['mode'] = 'amount';
                        foreach (['min', 'max', 'typical'] as $k) {
                            $row[$k . '_g'] = $target[$k] * $fraction / ($dmPercent / 100) * 1000;
                        }
                    } else {
                        $row['mode'] = 'dm_only';
                    }
                } else { // grams as fed
                    $row['mode'] = 'amount';
                    foreach (['min', 'max', 'typical'] as $k) {
                        $row[$k . '_g'] = $target[$k] * $fraction;
                    }
                }

                // Ration quality (dry-matter plans): weighted by each feed's share of the dry matter.
                $shareSum += $share;
                if (isset($item['crude_protein_percent']) && $item['crude_protein_percent'] !== null) {
                    $cpSum += $share * (float) $item['crude_protein_percent'];
                } else {
                    $allHaveCp = false;
                }
                if (isset($item['me_mj_per_kg_dm']) && $item['me_mj_per_kg_dm'] !== null) {
                    $meSum += $share * (float) $item['me_mj_per_kg_dm'];
                } else {
                    $allHaveMe = false;
                }
            }

            if ($row['typical_g'] !== null) {
                $row['per_meal_min_g'] = $row['min_g'] / $meals;
                $row['per_meal_max_g'] = $row['max_g'] / $meals;
                $row['per_meal_typical_g'] = $row['typical_g'] / $meals;
            }
            $rows[] = $row;
        }

        $cp = null;
        $me = null;
        $warnings = [];
        if ($target['kind'] === 'dm' && $shareSum > 0) {
            $cp = $allHaveCp ? $cpSum / $shareSum : null;
            $me = $allHaveMe ? $meSum / $shareSum : null;
            $cpMin = isset($plan['target_cp_min']) && $plan['target_cp_min'] !== null ? (float) $plan['target_cp_min'] : null;
            if ($cp !== null && $cpMin !== null && $cp + 1e-9 < $cpMin) {
                $warnings[] = sprintf('Estimated crude protein of this ration is %.1f%% of dry matter, below the %.1f%% this plan aims for. Add a higher-protein feed or ask a livestock nutritionist.', $cp, $cpMin);
            }
        }

        $result[] = [
            'name' => (string) $dietName,
            'items' => $rows,
            'cp_percent' => $cp,
            'me_mj_per_kg_dm' => $me,
            'warnings' => $warnings,
            'share_total' => $shareSum,
        ];
    }
    return $result;
}

// ---------------------------------------------------------------------------
// Display helpers (round to amounts a person can actually measure)
// ---------------------------------------------------------------------------

function feeding_round_grams(float $grams): float
{
    if ($grams >= 1000) {
        return round($grams / 100) * 100; // nearest 0.1 kg
    }
    if ($grams >= 200) {
        return round($grams / 10) * 10;
    }
    if ($grams >= 50) {
        return round($grams / 5) * 5;
    }
    return round($grams);
}

function feeding_format_grams(float $grams): string
{
    $r = feeding_round_grams($grams);
    if ($r >= 1000) {
        return rtrim(rtrim(number_format($r / 1000, 1, '.', ''), '0'), '.') . ' kg';
    }
    return number_format($r, 0, '.', '') . ' g';
}

/** "150-170 g", "2.4-2.8 kg", or a single value when the rounded ends are equal. */
function feeding_format_range(float $min, float $max): string
{
    $a = feeding_round_grams($min);
    $b = feeding_round_grams($max);
    if ($a === $b) {
        return feeding_format_grams($a);
    }
    if ($a >= 1000 && $b >= 1000) {
        $fmt = static fn (float $v): string => rtrim(rtrim(number_format($v / 1000, 1, '.', ''), '0'), '.');
        return $fmt($a) . '–' . $fmt($b) . ' kg';
    }
    if ($a < 1000 && $b < 1000) {
        return number_format($a, 0, '.', '') . '–' . number_format($b, 0, '.', '') . ' g';
    }
    return feeding_format_grams($a) . '–' . feeding_format_grams($b);
}

function feeding_format_kcal(float $kcal): string
{
    return number_format($kcal >= 100 ? round($kcal / 10) * 10 : round($kcal), 0, '.', ',');
}

function feeding_format_kcal_range(float $min, float $max): string
{
    $a = feeding_format_kcal($min);
    $b = feeding_format_kcal($max);
    return $a === $b ? $a : $a . '–' . $b;
}

/** Dry-matter kilograms, to 0.1 kg. */
function feeding_format_kg(float $kg): string
{
    return rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.') . ' kg';
}

function feeding_format_kg_range(float $min, float $max): string
{
    $a = round($min, 1);
    $b = round($max, 1);
    if ($a === $b) {
        return feeding_format_kg($a);
    }
    return rtrim(rtrim(number_format($a, 1, '.', ''), '0'), '.') . '–' . feeding_format_kg($b);
}

// ---------------------------------------------------------------------------
// Database loaders
// ---------------------------------------------------------------------------

/**
 * Public feeding plans of one animal, in display order.
 *
 * @return list<array<string, mixed>>
 */
function feeding_plans_for_animal(PDO $pdo, int $animalId): array
{
    return run_query(
        $pdo,
        "SELECT * FROM feeding_plans WHERE animal_id = :id AND review_status <> 'draft' ORDER BY sort_order, id",
        ['id' => $animalId]
    )->fetchAll();
}

/**
 * Diet items (with feed data) of a plan.
 *
 * @return list<array<string, mixed>>
 */
function feeding_items_for_plan(PDO $pdo, int $planId): array
{
    return run_query(
        $pdo,
        'SELECT i.diet_name, i.feed_id, i.share_percent, i.fixed_min_g, i.fixed_max_g, i.notes, i.sort_order,
                f.feed_name, f.feed_category, f.dry_matter_percent, f.crude_protein_percent, f.me_mj_per_kg_dm,
                f.energy_kcal_per_kg, f.review_status AS feed_review_status
         FROM feeding_plan_items i
         INNER JOIN feeds f ON f.id = i.feed_id
         WHERE i.plan_id = :id
         ORDER BY i.sort_order, f.feed_name',
        ['id' => $planId]
    )->fetchAll();
}

/** @return list<array<string, mixed>> */
function feeding_points_for_plan(PDO $pdo, int $planId): array
{
    return run_query(
        $pdo,
        'SELECT weight_kg, amount_g FROM feeding_plan_points WHERE plan_id = :id ORDER BY weight_kg',
        ['id' => $planId]
    )->fetchAll();
}

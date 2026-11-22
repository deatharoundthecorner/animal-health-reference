<?php
declare(strict_types=1);

/*
 * Unit tests for the feeding calculator (no database needed).
 *   php tests/feeding_test.php
 * Exits with status 1 if any check fails. Expected values were worked out by hand.
 */

if (PHP_SAPI !== 'cli') {
    exit('Run from the command line.');
}

require_once __DIR__ . '/../includes/feeding.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ok    {$name}\n";
    } else {
        $failed++;
        echo "  FAIL  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . "\n";
    }
}

function near(float $actual, float $expected, float $tolerance = 0.01): bool
{
    return abs($actual - $expected) <= $tolerance;
}

function item(string $diet, int $feedId, string $name, string $category, ?float $share, array $extra = []): array
{
    return array_merge([
        'diet_name' => $diet, 'feed_id' => $feedId, 'feed_name' => $name, 'feed_category' => $category,
        'share_percent' => $share, 'fixed_min_g' => null, 'fixed_max_g' => null, 'notes' => '',
        'dry_matter_percent' => null, 'crude_protein_percent' => null, 'me_mj_per_kg_dm' => null, 'energy_kcal_per_kg' => null,
    ], $extra);
}

echo "Resting energy requirement\n";
check('RER of 10 kg = 70 x 10^0.75 = 393.6 kcal', near(feeding_resting_energy(10.0), 393.64, 0.05), (string) feeding_resting_energy(10.0));
check('RER of 1 kg = 70 kcal', near(feeding_resting_energy(1.0), 70.0));
check('RER of 30 kg = 897.3 kcal', near(feeding_resting_energy(30.0), 897.3, 0.2), (string) feeding_resting_energy(30.0));

echo "Dog, energy method\n";
$dogPlan = ['method' => 'energy', 'amount_min' => 1.6, 'amount_max' => 1.6, 'amount_typical' => 1.6, 'meals_per_day' => 2, 'min_weight_kg' => 1.5, 'max_weight_kg' => 90];
$target = feeding_plan_target($dogPlan, [], 10.0);
check('target is ok and in kcal', $target['ok'] && $target['kind'] === 'energy' && $target['unit'] === 'kcal');
check('1.6 x RER for 10 kg = 629.8 kcal', near($target['typical'], 629.8, 0.2), (string) $target['typical']);
$items = [
    item('Dry', 1, 'Dry complete dog food', 'complete_feed', 100.0, ['energy_kcal_per_kg' => 3800]),
    item('Wet', 2, 'Wet complete dog food', 'complete_feed', 100.0, ['energy_kcal_per_kg' => 1000]),
    item('Home', 3, 'Cooked rice', 'kitchen_food', 61.0, ['energy_kcal_per_kg' => 1300]),
    item('Home', 4, 'Cooked chicken', 'kitchen_food', 39.0, ['energy_kcal_per_kg' => 1650]),
];
$diets = feeding_build_diets($dogPlan, $target, $items);
$byName = [];
foreach ($diets as $d) {
    $byName[$d['name']] = $d;
}
check('three diet options', count($diets) === 3);
check('dry food: 629.8 / 3800 = 165.7 g', near($byName['Dry']['items'][0]['typical_g'], 165.7, 0.2), (string) $byName['Dry']['items'][0]['typical_g']);
check('wet food: about 630 g (3.8 times dry food)', near($byName['Wet']['items'][0]['typical_g'], 629.8, 0.2));
check('wet is 3.8x dry by weight', near($byName['Wet']['items'][0]['typical_g'] / $byName['Dry']['items'][0]['typical_g'], 3.8, 0.01));
check('rice: 61% of energy = 295.5 g', near($byName['Home']['items'][0]['typical_g'], 295.5, 0.3), (string) $byName['Home']['items'][0]['typical_g']);
check('chicken: 39% of energy = 148.9 g', near($byName['Home']['items'][1]['typical_g'], 148.9, 0.3), (string) $byName['Home']['items'][1]['typical_g']);
check('per meal = half the daily amount (2 meals)', near($byName['Dry']['items'][0]['per_meal_typical_g'], $byName['Dry']['items'][0]['typical_g'] / 2, 0.001));
$over = feeding_build_diets($dogPlan, $target, $items, ['diet' => 'Dry', 'kcal' => 3500.0]);
check('label override changes only the named diet (dry: 629.8/3500 = 180 g)', near($over[0]['items'][0]['typical_g'], 179.9, 0.2) && near($over[1]['items'][0]['typical_g'], 629.8, 0.2));
check('override flagged on the row', $over[0]['items'][0]['override_used'] === true && $over[1]['items'][0]['override_used'] === false);
check('weight band: 10 kg fits, 100 kg does not', feeding_plan_applies($dogPlan, 10.0) && !feeding_plan_applies($dogPlan, 100.0));
check('weight band: 1 kg below 1.5 minimum does not fit', !feeding_plan_applies($dogPlan, 1.0));

echo "Cattle, dry-matter method\n";
$cowPlan = ['method' => 'pct_dm', 'amount_min' => 2.5, 'amount_max' => 3.3, 'amount_typical' => 3.0, 'meals_per_day' => 3, 'target_cp_min' => 13.5];
$cowTarget = feeding_plan_target($cowPlan, [], 450.0);
check('dry matter for 450 kg at 3.0% = 13.5 kg', $cowTarget['kind'] === 'dm' && near($cowTarget['typical'], 13.5));
check('range 11.25 to 14.85 kg', near($cowTarget['min'], 11.25) && near($cowTarget['max'], 14.85));
$cowItems = [
    item('Ration', 1, 'CO-3', 'green_forage', 20.0, ['dry_matter_percent' => 18.8, 'crude_protein_percent' => 8.8, 'me_mj_per_kg_dm' => 8.0]),
    item('Ration', 2, 'Guinea', 'green_forage', 15.0, ['dry_matter_percent' => 23.6, 'crude_protein_percent' => 8.0, 'me_mj_per_kg_dm' => 7.5]),
    item('Ration', 3, 'Gliricidia', 'green_forage', 20.0, ['dry_matter_percent' => 26.0, 'crude_protein_percent' => 17.7, 'me_mj_per_kg_dm' => 10.0]),
    item('Ration', 4, 'Formulated feed', 'concentrate', 45.0, ['dry_matter_percent' => 89.0, 'crude_protein_percent' => 15.7, 'me_mj_per_kg_dm' => 12.8]),
    item('Ration', 5, 'Calcium mineral mixture', 'supplement', null, ['fixed_min_g' => 60.0, 'fixed_max_g' => 90.0]),
    item('Ration', 6, 'Salt', 'supplement', null),
];
$cowDiets = feeding_build_diets($cowPlan, $cowTarget, $cowItems);
$rows = $cowDiets[0]['items'];
check('CO-3: 2.7 kg DM / 18.8% = 14.36 kg fresh', near($rows[0]['typical_g'] / 1000, 14.36, 0.01), (string) ($rows[0]['typical_g'] / 1000));
check('Guinea: 2.025 kg DM / 23.6% = 8.58 kg fresh', near($rows[1]['typical_g'] / 1000, 8.58, 0.01));
check('Gliricidia: 2.7 kg DM / 26% = 10.38 kg fresh', near($rows[2]['typical_g'] / 1000, 10.38, 0.01));
check('Formulated feed: 6.075 kg DM / 89% = 6.83 kg', near($rows[3]['typical_g'] / 1000, 6.83, 0.01));
check('fixed item keeps its own 60-90 g, not scaled by weight', $rows[4]['mode'] === 'fixed' && $rows[4]['min_g'] === 60.0 && $rows[4]['max_g'] === 90.0);
check('item without share or fixed amount is free choice', $rows[5]['mode'] === 'free');
check('ration crude protein = 13.57% of DM', $cowDiets[0]['cp_percent'] !== null && near($cowDiets[0]['cp_percent'], 13.565, 0.01), (string) $cowDiets[0]['cp_percent']);
check('ration energy = 10.5 MJ/kg DM (0.2x8.0 + 0.15x7.5 + 0.2x10.0 + 0.45x12.8)', $cowDiets[0]['me_mj_per_kg_dm'] !== null && near($cowDiets[0]['me_mj_per_kg_dm'], 10.485, 0.01), (string) $cowDiets[0]['me_mj_per_kg_dm']);
check('no protein warning at 13.57% vs target 13.5%', $cowDiets[0]['warnings'] === []);
$lowProtein = array_slice($cowItems, 0, 2);
$lowProtein[0]['share_percent'] = 50.0;
$lowProtein[1]['share_percent'] = 50.0;
$lowDiets = feeding_build_diets($cowPlan, $cowTarget, $lowProtein);
check('protein warning when ration is grass only (8.4% < 13.5%)', count($lowDiets[0]['warnings']) === 1 && str_contains($lowDiets[0]['warnings'][0], '13.5'));
$noDm = [item('X', 1, 'Mystery feed', 'concentrate', 100.0)];
$noDmDiets = feeding_build_diets($cowPlan, $cowTarget, $noDm);
check('feed without dry-matter %: shows dry matter only, no fake as-fed weight', $noDmDiets[0]['items'][0]['mode'] === 'dm_only' && $noDmDiets[0]['items'][0]['typical_g'] === null);

echo "Table plans (interpolation)\n";
$pigPoints = [['weight_kg' => 13.0, 'amount_g' => 500.0], ['weight_kg' => 110.0, 'amount_g' => 2500.0]];
check('pig at 13 kg = 500 g', near((float) feeding_interpolate($pigPoints, 13.0), 500.0));
check('pig at 110 kg = 2500 g', near((float) feeding_interpolate($pigPoints, 110.0), 2500.0));
check('pig at 61.5 kg (midpoint) = 1500 g', near((float) feeding_interpolate($pigPoints, 61.5), 1500.0));
check('no extrapolation below the table', feeding_interpolate($pigPoints, 10.0) === null);
check('no extrapolation above the table', feeding_interpolate($pigPoints, 150.0) === null);
check('unsorted points are sorted first', near((float) feeding_interpolate(array_reverse($pigPoints), 61.5), 1500.0));
check('a single point cannot be interpolated', feeding_interpolate([['weight_kg' => 5, 'amount_g' => 10]], 5.0) === null);
$pullet = [['weight_kg' => 0.49, 'amount_g' => 37.0], ['weight_kg' => 0.6, 'amount_g' => 41.0]];
check('pullet at 0.5 kg = 37.4 g', near((float) feeding_interpolate($pullet, 0.5), 37.36, 0.02));
$tablePlan = ['method' => 'table', 'meals_per_day' => 2];
$tableTarget = feeding_plan_target($tablePlan, $pigPoints, 61.5);
check('table plan target is grams', $tableTarget['ok'] && $tableTarget['kind'] === 'grams' && near($tableTarget['typical'], 1500.0));
check('table plan outside range reports an error', feeding_plan_target($tablePlan, $pigPoints, 5.0)['ok'] === false);
$pigDiets = feeding_build_diets($tablePlan, $tableTarget, [item('Complete', 1, 'Pig feed', 'complete_feed', 100.0)]);
check('pig feed 100% share = 1500 g, 750 g per meal', near($pigDiets[0]['items'][0]['typical_g'], 1500.0) && near($pigDiets[0]['items'][0]['per_meal_typical_g'], 750.0));

echo "Fixed and percent-as-fed plans\n";
$hen = feeding_plan_target(['method' => 'fixed', 'amount_min' => 100, 'amount_max' => 115, 'amount_typical' => 105], [], 1.8);
check('laying hen 100-115 g, typical 105 g, independent of weight', $hen['min'] === 100.0 && $hen['max'] === 115.0 && $hen['typical'] === 105.0);
$rabbit = feeding_plan_target(['method' => 'pct_asfed', 'amount_min' => 1.5, 'amount_max' => 2.5, 'amount_typical' => 2.0], [], 2.0);
check('rabbit 2 kg: pellets 30-50 g, typical 40 g', near($rabbit['min'], 30.0) && near($rabbit['max'], 50.0) && near($rabbit['typical'], 40.0));
check('typical defaults to the midpoint when missing', near(feeding_plan_target(['method' => 'pct_asfed', 'amount_min' => 1.0, 'amount_max' => 3.0], [], 10.0)['typical'], 200.0));
check('plan without amounts reports an error', feeding_plan_target(['method' => 'energy'], [], 10.0)['ok'] === false);
check('unknown method reports an error', feeding_plan_target(['method' => 'nonsense', 'amount_min' => 1, 'amount_max' => 1], [], 10.0)['ok'] === false);

echo "Display rounding\n";
check('165.7 g shows as 165 g', feeding_format_grams(165.7) === '165 g', feeding_format_grams(165.7));
check('629.8 g shows as 630 g', feeding_format_grams(629.8) === '630 g');
check('14360 g shows as 14.4 kg', feeding_format_grams(14360.0) === '14.4 kg', feeding_format_grams(14360.0));
check('2000 g shows as 2 kg', feeding_format_grams(2000.0) === '2 kg');
check('40 g stays 40 g', feeding_format_grams(40.0) === '40 g');
check('range 30-50 g', feeding_format_range(30.0, 50.0) === '30–50 g');
check('range 2.4-2.8 kg', feeding_format_range(2400.0, 2800.0) === '2.4–2.8 kg', feeding_format_range(2400.0, 2800.0));
check('equal ends collapse to one value', feeding_format_range(165.0, 166.0) === '165 g');
check('range across g and kg', feeding_format_range(900.0, 1200.0) === '900 g–1.2 kg', feeding_format_range(900.0, 1200.0));
check('kcal rounding 629.8 -> 630', feeding_format_kcal(629.8) === '630');
check('kcal thousands separator', feeding_format_kcal(1234.0) === '1,230', feeding_format_kcal(1234.0));
check('dry matter kg', feeding_format_kg(13.5) === '13.5 kg' && feeding_format_kg_range(11.25, 14.85) === '11.3–14.9 kg', feeding_format_kg_range(11.25, 14.85));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);

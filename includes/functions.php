<?php
declare(strict_types=1);

/*
 |-------------------------------------------------------------
 | Shared helpers (public site and admin)
 |-------------------------------------------------------------
 */

const APP_NAME = 'Animal Health & Anatomy Reference';

/** Review workflow: only 'draft' records are hidden from the public site. */
const REVIEW_LABELS = [
    'draft' => 'Draft (admins only)',
    'unreviewed' => 'Not yet expert-reviewed',
    'reviewed' => 'Expert-reviewed',
];

const ANIMAL_TYPES = [
    'companion' => 'Companion animal',
    'livestock' => 'Livestock',
    'wildlife' => 'Wildlife',
    'other' => 'Other',
];

const TAXON_RANKS = ['species', 'subspecies', 'genus', 'subfamily', 'family'];

const PATHOGEN_TYPES = [
    'virus' => 'Virus',
    'bacteria' => 'Bacteria',
    'parasite' => 'Parasite',
    'fungus' => 'Fungus',
    'non-infectious' => 'Non-infectious',
    'multiple' => 'Multiple / mixed',
];

/** Escape a value for safe output in HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Keep the old per-page helper name working. */
function esc(?string $value): string
{
    return e($value);
}

/** Shorten text for cards without cutting words in half. */
function excerpt(string $text, int $length = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $length * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,;:.-") . '…';
}

/** Build a LIKE pattern from user input, escaping the wildcard characters. */
function like_term(string $term): string
{
    return '%' . addcslashes($term, '\\%_') . '%';
}

/**
 * Prepare and run a query. Integer parameters are bound as integers, which
 * MySQL requires for LIMIT/OFFSET when prepared statements are not emulated.
 * Every named placeholder must be unique within the SQL string.
 *
 * @param array<string, int|string|null> $params
 */
function run_query(PDO $pdo, string $sql, array $params = []): PDOStatement
{
    $stmt = $pdo->prepare($sql);
    foreach ($params as $name => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(ltrim((string) $name, ':'), $value, $type);
    }
    $stmt->execute();
    return $stmt;
}

/** Read a trimmed string from $_GET. */
function get_string(string $key, int $maxLength = 100): string
{
    $value = $_GET[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return mb_substr(trim($value), 0, $maxLength);
}

/** Read a positive integer from $_GET (0 when missing or invalid). */
function get_int(string $key): int
{
    $value = $_GET[$key] ?? '';
    return is_string($value) && ctype_digit($value) ? (int) $value : 0;
}

/**
 * @return array{page:int,totalPages:int,offset:int}
 */
function paginate(int $total, int $perPage, int $requestedPage): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $requestedPage), $totalPages);
    return ['page' => $page, 'totalPages' => $totalPages, 'offset' => ($page - 1) * $perPage];
}

/** @param array<string, mixed> $query current filters, kept in the page links */
function render_pagination(int $page, int $totalPages, array $query = [], string $label = 'Pagination'): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $query = array_filter($query, static fn ($v) => $v !== '' && $v !== 0 && $v !== null);
    $html = '<nav class="mt-5" aria-label="' . e($label) . '"><ul class="pagination justify-content-center flex-wrap">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $href = '?' . http_build_query(array_merge($query, ['page' => $i]));
        $active = $i === $page ? ' active' : '';
        $current = $i === $page ? ' aria-current="page"' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($href) . '"' . $current . '>' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/** Badge that tells readers how far a record has been checked. */
function review_badge(string $status): string
{
    $label = REVIEW_LABELS[$status] ?? $status;
    $class = match ($status) {
        'reviewed' => 'text-bg-success',
        'unreviewed' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

/** Color-coded badge for an IUCN-style conservation status. */
function conservation_badge(string $status): string
{
    if ($status === '') {
        return '';
    }
    $class = 'text-bg-secondary';
    if (stripos($status, 'Critically') !== false || stripos($status, 'Endangered') !== false) {
        $class = 'text-bg-danger';
    } elseif (stripos($status, 'Vulnerable') !== false) {
        $class = 'text-bg-warning';
    } elseif (stripos($status, 'Least Concern') !== false) {
        $class = 'text-bg-success';
    }
    return '<span class="badge status-pill ' . $class . '">' . e($status) . '</span>';
}

/** Path of an animal image (or the placeholder). $prefix is '' on public pages and '../' in /admin. */
function animal_image(?string $image, string $prefix = ''): string
{
    if ($image !== null && $image !== '') {
        return $prefix . 'assets/images/uploads/' . $image;
    }
    return $prefix . 'assets/images/placeholder.svg';
}

function pathogen_label(string $type): string
{
    return PATHOGEN_TYPES[$type] ?? $type;
}

/** Badges for the notable properties of a disease. */
function disease_flags(array $disease): string
{
    $html = '';
    if (!empty($disease['is_zoonotic'])) {
        $html .= '<span class="badge text-bg-danger" title="Can spread between animals and people">Zoonotic</span> ';
    }
    if (!empty($disease['woah_listed'])) {
        $html .= '<span class="badge text-bg-dark" title="Listed by the World Organisation for Animal Health; usually reportable. Check local rules.">WOAH-listed</span> ';
    }
    return trim($html);
}

// ---------------------------------------------------------------------------
// Navigation helpers: counts for the menus, home page and quick-filter pills
// ---------------------------------------------------------------------------

/** Build "page.php?a=1&b=2", leaving out empty values. @param array<string, mixed> $params */
function page_url(string $page, array $params = []): string
{
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== 0 && $v !== null && $v !== false);
    return $page . ($params ? '?' . http_build_query($params) : '');
}

/**
 * True when the feeding guide tables exist. Sites that have not run `php tools/upgrade.php` yet
 * keep working; the feeding menu and pages are simply hidden.
 */
function feeding_available(PDO $pdo): bool
{
    static $available = null;
    if ($available === null) {
        try {
            $available = (bool) $pdo->query("SHOW TABLES LIKE 'feeding_plans'")->fetchColumn()
                && (bool) $pdo->query("SHOW TABLES LIKE 'feeds'")->fetchColumn();
        } catch (PDOException $e) {
            $available = false;
        }
    }
    return $available;
}

/** @return array<string, int> public feed types per category */
function count_feeds_by_category(PDO $pdo): array
{
    $counts = [];
    $rows = $pdo->query("SELECT feed_category, COUNT(*) AS n FROM feeds WHERE review_status <> 'draft' GROUP BY feed_category")->fetchAll();
    foreach ($rows as $row) {
        $counts[$row['feed_category']] = (int) $row['n'];
    }
    return $counts;
}

/** @return array<string, int> public animals per kind (companion, livestock, wildlife, other) */
function count_animals_by_kind(PDO $pdo): array
{
    $counts = array_fill_keys(array_keys(ANIMAL_TYPES), 0);
    $rows = $pdo->query("SELECT animal_type, COUNT(*) AS n FROM animals WHERE review_status <> 'draft' GROUP BY animal_type")->fetchAll();
    foreach ($rows as $row) {
        $counts[$row['animal_type']] = (int) $row['n'];
    }
    return $counts;
}

/** @return array<string, int> public diseases per cause (virus, bacteria, ...) */
function count_diseases_by_cause(PDO $pdo): array
{
    $counts = array_fill_keys(array_keys(PATHOGEN_TYPES), 0);
    $rows = $pdo->query("SELECT pathogen_type, COUNT(*) AS n FROM diseases WHERE review_status <> 'draft' GROUP BY pathogen_type")->fetchAll();
    foreach ($rows as $row) {
        $counts[$row['pathogen_type']] = (int) $row['n'];
    }
    return $counts;
}

/** @return array{zoonotic:int, woah:int} public diseases in the special collections */
function count_disease_collections(PDO $pdo): array
{
    $row = $pdo->query(
        "SELECT COALESCE(SUM(is_zoonotic = 1), 0) AS zoonotic, COALESCE(SUM(woah_listed = 1), 0) AS woah
         FROM diseases WHERE review_status <> 'draft'"
    )->fetch();
    return ['zoonotic' => (int) $row['zoonotic'], 'woah' => (int) $row['woah']];
}

/** Public animals whose conservation status is Endangered or Critically Endangered. */
function count_threatened_animals(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM animals WHERE review_status <> 'draft' AND conservation_status LIKE '%Endangered%'"
    )->fetchColumn();
}

/** @return list<array<string, mixed>> body systems (id, system_name, part_count of public body parts) in display order */
function systems_with_counts(PDO $pdo): array
{
    return $pdo->query(
        "SELECT bs.id, bs.system_name, COUNT(bp.id) AS part_count
         FROM body_systems bs
         LEFT JOIN body_parts bp ON bp.system_id = bs.id AND bp.review_status <> 'draft'
         GROUP BY bs.id, bs.system_name, bs.sort_order
         ORDER BY bs.sort_order, bs.system_name"
    )->fetchAll();
}

/**
 * Render a row of quick-filter pills.
 *
 * @param list<array{label:string, url:string, active?:bool, count?:int|null}> $pills
 */
function render_pills(array $pills, string $label): string
{
    $html = '<nav class="pill-row mb-2" aria-label="' . e($label) . '">';
    foreach ($pills as $pill) {
        $active = !empty($pill['active']);
        $count = isset($pill['count']) ? ' <span class="pill-count">' . (int) $pill['count'] . '</span>' : '';
        $html .= '<a class="pill' . ($active ? ' active' : '') . '" href="' . e($pill['url']) . '"'
            . ($active ? ' aria-current="true"' : '') . '>' . e($pill['label']) . $count . '</a>';
    }
    return $html . '</nav>';
}

/**
 * External link for an ontology identifier (Uberon, Disease Ontology) or NCBI taxon.
 * Returns null for anything that does not match the expected formats.
 */
function ontology_url(?string $id): ?string
{
    if ($id === null || $id === '') {
        return null;
    }
    if (preg_match('/^(UBERON|DOID):(\d+)$/', $id, $m)) {
        return 'https://purl.obolibrary.org/obo/' . $m[1] . '_' . $m[2];
    }
    return null;
}

function ncbi_taxon_url(?int $taxonId): ?string
{
    return $taxonId ? 'https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=' . $taxonId : null;
}

/** Only allow http(s) links to be rendered from stored source URLs. */
function safe_url(?string $url): ?string
{
    if ($url === null || $url === '') {
        return null;
    }
    return preg_match('#^https?://#i', $url) ? $url : null;
}

/** @return list<array<string,mixed>> sources cited by one record (table: animal|disease|body_part) */
function fetch_sources(PDO $pdo, string $entity, int $id): array
{
    $map = [
        'animal' => ['animal_sources', 'animal_id'],
        'disease' => ['disease_sources', 'disease_id'],
        'body_part' => ['body_part_sources', 'body_part_id'],
        'feed' => ['feed_sources', 'feed_id'],
        'plan' => ['feeding_plan_sources', 'plan_id'],
    ];
    if (!isset($map[$entity])) {
        return [];
    }
    [$table, $column] = $map[$entity];
    return run_query(
        $pdo,
        "SELECT s.id, s.title, s.publisher, s.url, s.license
         FROM {$table} link
         INNER JOIN sources s ON s.id = link.source_id
         WHERE link.{$column} = :id
         ORDER BY s.title ASC",
        ['id' => $id]
    )->fetchAll();
}

/** Render a "Sources" list. */
function render_sources(array $sources): string
{
    if (!$sources) {
        return '<p class="text-muted mb-0">No sources have been recorded for this entry yet.</p>';
    }
    $html = '<ul class="list-unstyled mb-0 source-list">';
    foreach ($sources as $source) {
        $url = safe_url($source['url'] ?? null);
        $title = e($source['title']);
        $html .= '<li class="mb-2">';
        $html .= $url !== null
            ? '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . $title . '</a>'
            : $title;
        $meta = array_filter([$source['publisher'] ?? '', $source['license'] ?? '']);
        if ($meta) {
            $html .= '<div class="small text-muted">' . e(implode(' · ', $meta)) . '</div>';
        }
        $html .= '</li>';
    }
    return $html . '</ul>';
}

// ---------------------------------------------------------------------------
// Admin helpers (CSRF protection). The admin pages start the session first.
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** True when the submitted token matches the session token. */
function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

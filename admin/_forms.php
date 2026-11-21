<?php
declare(strict_types=1);

/*
 * Helpers shared by the admin forms: input parsing, link tables, image uploads.
 */

const UPLOAD_DIR = __DIR__ . '/../assets/images/uploads/';
const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

/**
 * Link tables the admin may write to: table => [column A, column B, has notes column].
 * Either column can be the "owner" side, depending on which edit page is being used.
 */
const LINK_TABLES = [
    'animal_body_parts' => ['animal_id', 'body_part_id', true],
    'animal_diseases' => ['animal_id', 'disease_id', true],
    'disease_body_parts' => ['disease_id', 'body_part_id', true],
    'animal_sources' => ['animal_id', 'source_id', false],
    'disease_sources' => ['disease_id', 'source_id', false],
    'body_part_sources' => ['body_part_id', 'source_id', false],
    'disease_symptoms' => ['disease_id', 'symptom_id', false],
];

/** Trimmed POST string, shortened to $maxLength characters. */
function post_string(string $key, int $maxLength = 255): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? mb_substr(trim($value), 0, $maxLength) : '';
}

function post_int(string $key): int
{
    $value = $_POST[$key] ?? '';
    return is_string($value) && ctype_digit($value) ? (int) $value : 0;
}

function post_bool(string $key): int
{
    return isset($_POST[$key]) && $_POST[$key] === '1' ? 1 : 0;
}

/**
 * Read a checkbox list with optional notes, e.g. parts[ID]=1 and part_notes[ID]=text.
 *
 * @return array<int, string|null> id => note
 */
function read_linked(string $checkField, string $noteField = ''): array
{
    $result = [];
    $checked = $_POST[$checkField] ?? [];
    if (!is_array($checked)) {
        return $result;
    }
    $notes = $noteField !== '' && isset($_POST[$noteField]) && is_array($_POST[$noteField]) ? $_POST[$noteField] : [];
    foreach ($checked as $id) {
        if (!is_string($id) || !ctype_digit($id) || (int) $id <= 0) {
            continue;
        }
        $note = isset($notes[$id]) && is_string($notes[$id]) ? trim($notes[$id]) : '';
        $result[(int) $id] = $note !== '' ? mb_substr($note, 0, 2000) : null;
    }
    return $result;
}

/**
 * Resolve a link table definition for a given owner column.
 *
 * @return array{0:string,1:bool} the other column and whether the table has a notes column
 */
function link_table_columns(string $table, string $ownerColumn): array
{
    if (!isset(LINK_TABLES[$table])) {
        throw new InvalidArgumentException('Unknown link table');
    }
    [$columnA, $columnB, $hasNotes] = LINK_TABLES[$table];
    if ($ownerColumn === $columnA) {
        return [$columnB, $hasNotes];
    }
    if ($ownerColumn === $columnB) {
        return [$columnA, $hasNotes];
    }
    throw new InvalidArgumentException('Unknown owner column');
}

/**
 * Replace all links of one owner in a link table.
 *
 * @param array<int, string|null> $links id of the other side => note
 */
function sync_links(PDO $pdo, string $table, string $ownerColumn, int $ownerId, array $links): void
{
    [$otherColumn, $hasNotes] = link_table_columns($table, $ownerColumn);

    run_query($pdo, "DELETE FROM {$table} WHERE {$ownerColumn} = :id", ['id' => $ownerId]);

    if ($hasNotes) {
        $insert = $pdo->prepare("INSERT INTO {$table} ({$ownerColumn}, {$otherColumn}, notes) VALUES (:owner, :other, :notes)");
        foreach ($links as $otherId => $note) {
            $insert->execute(['owner' => $ownerId, 'other' => $otherId, 'notes' => $note]);
        }
    } else {
        $insert = $pdo->prepare("INSERT INTO {$table} ({$ownerColumn}, {$otherColumn}) VALUES (:owner, :other)");
        foreach ($links as $otherId => $unused) {
            $insert->execute(['owner' => $ownerId, 'other' => $otherId]);
        }
    }
}

/** @return array<int, string|null> id => note for the rows currently linked to an owner */
function load_links(PDO $pdo, string $table, string $ownerColumn, int $ownerId): array
{
    [$otherColumn, $hasNotes] = link_table_columns($table, $ownerColumn);
    $notesColumn = $hasNotes ? 'notes' : 'NULL';
    $rows = run_query($pdo, "SELECT {$otherColumn} AS other_id, {$notesColumn} AS notes FROM {$table} WHERE {$ownerColumn} = :id", ['id' => $ownerId])->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[(int) $row['other_id']] = $row['notes'];
    }
    return $result;
}

/**
 * Render a scrollable "tick the ones that apply, add a note" table.
 *
 * @param array<int, string> $options  id => label
 * @param array<int, string|null> $selected id => note
 */
function render_link_picker(string $idPrefix, string $checkName, string $noteName, array $options, array $selected, bool $withNotes = true): string
{
    if (!$options) {
        return '<p class="text-muted small mb-0">Nothing to choose from yet.</p>';
    }
    $html = '<div class="link-picker border rounded"><table class="table table-sm align-middle mb-0"><tbody>';
    foreach ($options as $id => $label) {
        $checked = array_key_exists($id, $selected);
        $inputId = $idPrefix . '-' . $id;
        $html .= '<tr><td class="text-center" style="width:2.5rem"><input class="form-check-input" type="checkbox" name="' . e($checkName) . '[]" value="' . $id . '" id="' . e($inputId) . '"' . ($checked ? ' checked' : '') . '></td>';
        $html .= '<td><label class="form-check-label" for="' . e($inputId) . '">' . e($label) . '</label></td>';
        if ($withNotes) {
            $html .= '<td style="width:55%"><input type="text" class="form-control form-control-sm" name="' . e($noteName) . '[' . $id . ']" maxlength="2000" placeholder="Optional species-specific note" value="' . e((string) ($selected[$id] ?? '')) . '" aria-label="Note for ' . e($label) . '"></td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table></div>';
}

/** Split "fever, vomiting" style input into unique, normalised names. @return list<string> */
function parse_tag_list(string $input, int $maxItems = 40): array
{
    $names = [];
    foreach (preg_split('/[,\n]+/', $input) ?: [] as $raw) {
        $name = mb_strtolower(trim(preg_replace('/\s+/', ' ', $raw) ?? ''));
        if ($name !== '' && mb_strlen($name) <= 150) {
            $names[$name] = $name;
        }
    }
    return array_slice(array_values($names), 0, $maxItems);
}

/** Look up symptoms by name (creating missing ones) and return their ids as links. @param list<string> $names @return array<int, null> */
function symptom_ids_for_names(PDO $pdo, array $names): array
{
    $ids = [];
    $find = $pdo->prepare('SELECT id FROM symptoms WHERE symptom_name = :name LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO symptoms (symptom_name) VALUES (:name)');
    foreach ($names as $name) {
        $find->execute(['name' => $name]);
        $id = $find->fetchColumn();
        if ($id === false) {
            $insert->execute(['name' => $name]);
            $id = $pdo->lastInsertId();
        }
        $ids[(int) $id] = null;
    }
    return $ids;
}

/**
 * Validate and store an uploaded image.
 * The file type is detected from the file contents and the stored extension is chosen
 * from that type; the name supplied by the browser is never used.
 *
 * @param array<string, mixed> $file entry of $_FILES
 * @param list<string> $errors
 * @return string|null stored file name, or null when nothing was stored
 */
function store_uploaded_image(array $file, array &$errors): ?string
{
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($code !== UPLOAD_ERR_OK) {
        $errors[] = $code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE
            ? 'The image is larger than the allowed size (2 MB).'
            : 'Image upload failed (error code ' . (int) $code . ').';
        return null;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        $errors[] = 'Image upload failed.';
        return null;
    }
    if ((int) ($file['size'] ?? 0) > MAX_IMAGE_BYTES) {
        $errors[] = 'Image size must be less than 2 MB.';
        return null;
    }

    $info = @getimagesize($tmp);
    $extensions = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($extensions[$info[2]])) {
        $errors[] = 'Only JPG, PNG and WEBP images are allowed.';
        return null;
    }

    $name = 'animal_' . bin2hex(random_bytes(10)) . '.' . $extensions[$info[2]];
    if (!move_uploaded_file($tmp, UPLOAD_DIR . $name)) {
        $errors[] = 'Could not save the uploaded image.';
        return null;
    }
    return $name;
}

/** Delete a previously uploaded image file (ignores anything that is not a plain file name). */
function delete_uploaded_image(?string $name): void
{
    if ($name === null || $name === '' || $name !== basename($name) || !preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
        return;
    }
    $path = UPLOAD_DIR . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}

/** True when a PDOException is a duplicate-key violation. */
function is_duplicate_error(PDOException $e): bool
{
    return ($e->errorInfo[1] ?? 0) === 1062;
}

<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$resources = [
    'categories' => [
        'title' => 'Categories', 'table' => 'categories', 'search' => ['name', 'slug'], 'columns' => ['name', 'slug', 'status'],
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug', 'type' => 'slug'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            'display_order' => ['label' => 'Display order', 'type' => 'integer'],
        ],
    ],
    'destinations' => [
        'title' => 'Destinations', 'table' => 'destinations', 'search' => ['name', 'location', 'short_description'], 'columns' => ['name', 'location', 'status'],
        'fields' => [
            'category_id' => ['label' => 'Category', 'type' => 'relation', 'table' => 'categories'],
            'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug', 'type' => 'slug'],
            'short_description' => ['label' => 'Short description', 'type' => 'text'],
            'description' => ['label' => 'Full description', 'type' => 'textarea'],
            'cover_image' => ['label' => 'Cover image URL or local path', 'type' => 'image_path'],
            'location' => ['label' => 'Location', 'type' => 'text'],
            'latitude' => ['label' => 'Latitude', 'type' => 'decimal'],
            'longitude' => ['label' => 'Longitude', 'type' => 'decimal'],
            'map_url' => ['label' => 'Map URL', 'type' => 'url'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive']],
            'featured' => ['label' => 'Featured on homepage', 'type' => 'checkbox'],
        ],
    ],
    'attractions' => [
        'title' => 'Attractions', 'table' => 'attractions', 'search' => ['name', 'short_description'], 'columns' => ['name', 'destination_id', 'status'],
        'fields' => [
            'destination_id' => ['label' => 'Destination', 'type' => 'relation', 'table' => 'destinations'],
            'category_id' => ['label' => 'Category', 'type' => 'relation', 'table' => 'categories'],
            'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug', 'type' => 'slug'],
            'short_description' => ['label' => 'Short description', 'type' => 'text'],
            'description' => ['label' => 'Full description', 'type' => 'textarea'],
            'main_image' => ['label' => 'Main image URL or local path', 'type' => 'image_path'],
            'youtube_url' => ['label' => 'YouTube URL', 'type' => 'url'],
            'website_url' => ['label' => 'Website URL', 'type' => 'url'],
            'map_url' => ['label' => 'Map URL', 'type' => 'url'],
            'latitude' => ['label' => 'Latitude', 'type' => 'decimal'],
            'longitude' => ['label' => 'Longitude', 'type' => 'decimal'],
            'opening_hours' => ['label' => 'Opening hours', 'type' => 'text'],
            'entry_information' => ['label' => 'Entry information', 'type' => 'text'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive']],
            'display_order' => ['label' => 'Display order', 'type' => 'integer'],
        ],
    ],
];
$key = $resource ?? (string) ($_GET['type'] ?? '');
if (!isset($resources[$key])) {
    http_response_code(404);
    exit('Unknown content type.');
}
$definition = $resources[$key];
$table = $definition['table'];
$error = '';
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT) ?: null;
$record = [];
if ($editId) {
    $statement = db()->prepare("SELECT * FROM {$table} WHERE id = ?");
    $statement->execute([$editId]);
    $record = $statement->fetch() ?: [];
    if (!$record) {
        $editId = null;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'delete') {
        $deleteId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($deleteId) {
            $statement = db()->prepare("DELETE FROM {$table} WHERE id = ?");
            $statement->execute([$deleteId]);
            record_activity($key . '_deleted', $definition['title'] . ' record #' . $deleteId . ' deleted');
            set_flash('success', $definition['title'] . ' record deleted.');
        }
        header('Location: ' . app_url('admin/' . $key . '.php'));
        exit;
    }

    $values = [];
    $name = trim((string) ($_POST['name'] ?? ''));
    foreach ($definition['fields'] as $field => $metadata) {
        $raw = $_POST[$field] ?? '';
        $type = $metadata['type'];
        if ($type === 'checkbox') {
            $values[$field] = isset($_POST[$field]) ? 1 : 0;
        } elseif ($type === 'slug') {
            $slugSource = trim((string) $raw) !== '' ? (string) $raw : $name;
            $slugSource = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slugSource) ?: $slugSource;
            $values[$field] = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $slugSource) ?? ''), '-');
        } elseif ($type === 'integer' || $type === 'relation') {
            $values[$field] = $raw === '' ? ($type === 'integer' ? 0 : null) : filter_var($raw, FILTER_VALIDATE_INT);
            if ($raw !== '' && $values[$field] === false) {
                $error = 'Enter a valid number for ' . $metadata['label'] . '.';
            }
        } elseif ($type === 'decimal') {
            $values[$field] = trim((string) $raw) === '' ? null : filter_var($raw, FILTER_VALIDATE_FLOAT);
            if (trim((string) $raw) !== '' && $values[$field] === false) {
                $error = 'Enter a valid number for ' . $metadata['label'] . '.';
            }
        } elseif ($type === 'select') {
            $values[$field] = (string) $raw;
            if (!array_key_exists($values[$field], $metadata['options'])) {
                $error = 'Choose a valid ' . strtolower($metadata['label']) . '.';
            }
        } elseif ($type === 'url' || $type === 'image_path') {
            $value = trim((string) $raw);
            if ($value !== '' && $type === 'url' && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true))) {
                $error = 'Enter a valid URL for ' . $metadata['label'] . '.';
            }
            $remoteImage = filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
            $localImage = preg_match('#^assets/(?:uploads|images)/[A-Za-z0-9._/-]+$#D', $value) === 1 && !str_contains($value, '..');
            if ($value !== '' && $type === 'image_path' && !$remoteImage && !$localImage) {
                $error = 'Use an HTTPS image URL or a relative path inside the site.';
            }
            $values[$field] = $value === '' ? null : $value;
        } else {
            $values[$field] = trim((string) $raw);
            if (($metadata['required'] ?? false) && $values[$field] === '') {
                $error = $metadata['label'] . ' is required.';
            }
        }
    }
    if (!isset($values['slug']) || $values['slug'] === '') {
        $error = 'Name must produce a valid slug.';
    }
    if ($key === 'attractions' && empty($values['destination_id'])) {
        $error = 'Choose a destination for this attraction.';
    }
    if ($error === '') {
        try {
            $fields = array_keys($values);
            $parameters = array_values($values);
            if ($editId) {
                $assignments = implode(', ', array_map(static fn(string $field): string => "{$field} = ?", $fields));
                $parameters[] = $editId;
                $statement = db()->prepare("UPDATE {$table} SET {$assignments} WHERE id = ?");
                $statement->execute($parameters);
                record_activity($key . '_updated', $definition['title'] . ' record #' . $editId . ' updated');
            } else {
                $columnList = implode(', ', $fields);
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                $statement = db()->prepare("INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})");
                $statement->execute($parameters);
                record_activity($key . '_created', $definition['title'] . ' record #' . db()->lastInsertId() . ' created');
            }
            set_flash('success', $definition['title'] . ' record saved.');
            header('Location: ' . app_url('admin/' . $key . '.php'));
            exit;
        } catch (PDOException) {
            $error = 'The record could not be saved. Check that its slug is unique and its linked records exist.';
        }
    }
    $record = array_merge($record, $values, ['name' => $name]);
}
$search = trim((string) ($_GET['q'] ?? ''));
$conditions = [];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(' . implode(' OR ', array_map(static fn(string $field): string => "{$field} LIKE ?", $definition['search'])) . ')';
    foreach ($definition['search'] as $_) {
        $parameters[] = '%' . $search . '%';
    }
}
$query = "SELECT * FROM {$table}" . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY updated_at DESC LIMIT 100';
$listStatement = db()->prepare($query);
$listStatement->execute($parameters);
$rows = $listStatement->fetchAll();
$pageTitle = $definition['title'];
require __DIR__ . '/../includes/header.php';
?>
<section class="container py-5"><div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4"><div><p class="eyebrow eyebrow-dark">CONTENT STUDIO</p><h1 class="h2 mb-0">Manage <?= e(strtolower($definition['title'])) ?></h1></div><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/index.php')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Dashboard</a></div>
<div class="row g-4"><div class="col-lg-7"><div class="admin-section"><form method="get" class="d-flex gap-2 mb-3"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($definition['title'])) ?>" aria-label="Search <?= e(strtolower($definition['title'])) ?>"><button class="btn btn-success" type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button></form><div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach ($definition['columns'] as $column): ?><th><?= e(ucwords(str_replace('_', ' ', $column))) ?></th><?php endforeach; ?><th class="text-end">Actions</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($definition['columns'] as $column): ?><td><?php if ($column === 'destination_id'): ?><?= e(db()->prepare('SELECT name FROM destinations WHERE id = ?')->execute([$row[$column]]) ? (db()->query('SELECT name FROM destinations WHERE id = ' . (int) $row[$column])->fetchColumn() ?: 'Unknown') : 'Unknown') ?><?php else: ?><?= e($row[$column] ?? '') ?><?php endif; ?></td><?php endforeach; ?><td class="text-end"><a class="btn btn-sm btn-outline-success" href="?edit=<?= (int) $row['id'] ?>">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Delete this record? This may also remove linked content.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="<?= count($definition['columns']) + 1 ?>" class="text-secondary py-4">No records found.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="col-lg-5"><div class="admin-section"><h2 class="h5 mb-3"><?= $editId ? 'Edit record' : 'Add new' ?></h2><?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php foreach ($definition['fields'] as $field => $metadata): $value = $record[$field] ?? ''; ?><div class="mb-3"><?php if ($metadata['type'] === 'checkbox'): ?><div class="form-check"><input class="form-check-input" type="checkbox" id="field-<?= e($field) ?>" name="<?= e($field) ?>" value="1" <?= $value ? 'checked' : '' ?>><label class="form-check-label" for="field-<?= e($field) ?>"><?= e($metadata['label']) ?></label></div><?php else: ?><label class="form-label" for="field-<?= e($field) ?>"><?= e($metadata['label']) ?></label><?php if ($metadata['type'] === 'relation'): ?><select class="form-select" id="field-<?= e($field) ?>" name="<?= e($field) ?>"><option value="">None</option><?php foreach (db()->query('SELECT id, name FROM ' . $metadata['table'] . ' ORDER BY name')->fetchAll() as $option): ?><option value="<?= (int) $option['id'] ?>" <?= (string) $value === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option><?php endforeach; ?></select><?php elseif ($metadata['type'] === 'select'): ?><select class="form-select" id="field-<?= e($field) ?>" name="<?= e($field) ?>"><?php foreach ($metadata['options'] as $optionValue => $label): ?><option value="<?= e($optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?php elseif ($metadata['type'] === 'textarea'): ?><textarea class="form-control" id="field-<?= e($field) ?>" name="<?= e($field) ?>" rows="3"><?= e($value) ?></textarea><?php else: ?><input class="form-control" id="field-<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($value) ?>" type="<?= in_array($metadata['type'], ['integer', 'decimal'], true) ? 'number' : 'text' ?>" <?= $metadata['type'] === 'decimal' ? 'step="any"' : '' ?> <?= !empty($metadata['required']) ? 'required' : '' ?>><?php endif; ?><?php endif; ?></div><?php endforeach; ?><button class="btn btn-success" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save</button><?php if ($editId): ?><a class="btn btn-outline-secondary ms-2" href="<?= e(app_url('admin/' . $key . '.php')) ?>">Cancel</a><?php endif; ?></form></div></div></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

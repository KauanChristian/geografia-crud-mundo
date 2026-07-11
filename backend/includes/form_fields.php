<?php
declare(strict_types=1);

/** @param array<string, mixed> $attributes */
function form_attributes(array $attributes): string
{
    $parts = [];

    foreach ($attributes as $name => $value) {
        if (!preg_match('/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/', (string) $name) || $value === false || $value === null) {
            continue;
        }

        if ($value === true) {
            $parts[] = e($name);
            continue;
        }

        $parts[] = e($name) . '="' . e((string) $value) . '"';
    }

    return implode(' ', $parts);
}

/** @param array<string, string> $errors @param array<string, mixed> $attributes */
function render_input_field(string $name, string $label, $value, array $errors, string $type = 'text', array $attributes = [], string $help = ''): void
{
    $error = $errors[$name] ?? '';
    $required = !empty($attributes['required']);
    $describedBy = [];

    if ($help !== '') {
        $describedBy[] = $name . '-help';
    }
    if ($error !== '') {
        $describedBy[] = $name . '-error';
    }
    ?>
    <div class="field<?= $error !== '' ? ' has-error' : '' ?>">
        <label for="<?= e($name) ?>">
            <?= e($label) ?><?= $required ? ' <span class="required" aria-label="obrigatório">*</span>' : '' ?>
        </label>
        <input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" value="<?= e($value) ?>"<?= $describedBy !== [] ? ' aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '' ?><?= $error !== '' ? ' aria-invalid="true"' : '' ?> <?= form_attributes($attributes) ?>>
        <?php if ($help !== ''): ?>
            <small id="<?= e($name) ?>-help" class="field-help"><?= e($help) ?></small>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <small id="<?= e($name) ?>-error" class="field-error"><?= e($error) ?></small>
        <?php endif; ?>
    </div>
    <?php
}

/** @param array<int, array{id: int|string, label: string}> $options @param array<string, string> $errors @param array<string, mixed> $attributes */
function render_select_field(string $name, string $label, $value, array $options, array $errors, array $attributes = [], string $placeholder = 'Selecione uma opção', string $help = ''): void
{
    $error = $errors[$name] ?? '';
    $required = !empty($attributes['required']);
    $describedBy = [];

    if ($help !== '') {
        $describedBy[] = $name . '-help';
    }
    if ($error !== '') {
        $describedBy[] = $name . '-error';
    }
    ?>
    <div class="field<?= $error !== '' ? ' has-error' : '' ?>">
        <label for="<?= e($name) ?>">
            <?= e($label) ?><?= $required ? ' <span class="required" aria-label="obrigatório">*</span>' : '' ?>
        </label>
        <select id="<?= e($name) ?>" name="<?= e($name) ?>"<?= $describedBy !== [] ? ' aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '' ?><?= $error !== '' ? ' aria-invalid="true"' : '' ?> <?= form_attributes($attributes) ?>>
            <option value=""><?= e($placeholder) ?></option>
            <?php foreach ($options as $option): ?>
                <option value="<?= e($option['id']) ?>"<?= (string) $value === (string) $option['id'] ? ' selected' : '' ?>><?= e($option['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($help !== ''): ?>
            <small id="<?= e($name) ?>-help" class="field-help"><?= e($help) ?></small>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <small id="<?= e($name) ?>-error" class="field-error"><?= e($error) ?></small>
        <?php endif; ?>
    </div>
    <?php
}

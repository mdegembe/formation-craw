<?php

declare(strict_types=1);

/**
 * Mode « fond + calque » : l'administrateur depose une image A4 et positionne
 * les blocs de texte. Chaque bloc du calque porte sa position en millimetres,
 * sa taille de police, sa couleur et son alignement.
 *
 * @var string                            $backgroundUri
 * @var array<int, array<string, mixed>>  $layout
 * @var array<string, string>             $values
 */
?>
<?php if ($backgroundUri !== ''): ?>
    <img src="<?= $backgroundUri ?>" alt="" class="diploma-background">
<?php endif; ?>

<?php foreach ($layout as $field): ?>
    <?php
    $key = (string) ($field['key'] ?? '');
    $value = $values[$key] ?? '';

    if ($value === '') {
        continue;
    }

    $style = sprintf(
        'top: %smm; left: %smm; width: %smm; font-size: %spt; color: %s; text-align: %s;%s',
        (float) ($field['top'] ?? 0),
        (float) ($field['left'] ?? 0),
        (float) ($field['width'] ?? 100),
        (float) ($field['size'] ?? 12),
        preg_match('/^#[0-9a-f]{3,8}$/i', (string) ($field['color'] ?? '')) === 1
            ? (string) $field['color']
            : '#212529',
        in_array((string) ($field['align'] ?? 'left'), ['left', 'center', 'right'], true)
            ? (string) $field['align']
            : 'left',
        !empty($field['bold']) ? ' font-weight: bold;' : ''
    );
    ?>

    <?php if ($key === 'qr_verification'): ?>
        <div class="diploma-field" style="<?= e($style) ?>">
            <img src="<?= $value ?>" alt="" class="diploma-qr">
        </div>
    <?php else: ?>
        <div class="diploma-field" style="<?= e($style) ?>">
            <?php if (!empty($field['prefix'])): ?>
                <span><?= e((string) $field['prefix']) ?></span>
            <?php endif; ?>
            <?= e($value) ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

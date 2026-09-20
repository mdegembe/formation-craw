<?php

declare(strict_types=1);

/**
 * Registration form progress indicator (English).
 *
 * @var int $step Current step (1 to 3)
 */
$labels = [
    1 => 'Identity and contact',
    2 => 'Dietary preferences',
    3 => 'Review and confirmation',
];
?>
<ol class="steps">
    <?php foreach ($labels as $index => $label): ?>
        <li <?= $index === $step ? 'aria-current="step"' : '' ?>
            <?= $index < $step ? 'data-state="done"' : '' ?>>
            <?= e($label) ?>
            <?php if ($index < $step): ?>
                <span class="visually-hidden">(step completed)</span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>

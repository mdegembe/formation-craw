<?php

declare(strict_types=1);

/**
 * Indicateur de progression du formulaire d'inscription.
 *
 * @var int $step Etape courante (1 a 3)
 */
$labels = [
    1 => 'Identité et contact',
    2 => 'Préférences alimentaires',
    3 => 'Récapitulatif et validation',
];
?>
<ol class="steps">
    <?php foreach ($labels as $index => $label): ?>
        <li <?= $index === $step ? 'aria-current="step"' : '' ?>
            <?= $index < $step ? 'data-state="done"' : '' ?>>
            <?= e($label) ?>
            <?php if ($index < $step): ?>
                <span class="visually-hidden">(étape terminée)</span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>

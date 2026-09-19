<?php

declare(strict_types=1);

/**
 * Message d'erreur d'un champ, relie au champ par aria-describedby.
 *
 * @var array<string, string> $errors
 * @var string                $field
 */
if (!isset($errors[$field])) {
    return;
}
?>
<p class="form-error" id="<?= e($field) ?>-error">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
    </svg>
    <?= e($errors[$field]) ?>
</p>

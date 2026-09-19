<?php

declare(strict_types=1);

use Craw\Core\Session;

/**
 * Messages flash. role="alert" pour que les lecteurs d'ecran les annoncent
 * immediatement apres une action.
 */
$flashes = Session::takeFlashes();

if ($flashes === []) {
    return;
}

$icons = [
    'success' => 'M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.73.73 0 0 1-1.065.02L3.217 8.384a.75.75 0 0 1 0-1.06.73.73 0 0 1 1.04 0l3.052 3.093 5.4-6.425z',
    'error' => 'M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z',
    'warning' => 'M7.938 2.016a.13.13 0 0 1 .125 0 .13.13 0 0 1 .06.055l6.857 11.667c.05.09.002.163-.043.187a.13.13 0 0 1-.064.015H1.127a.13.13 0 0 1-.064-.015c-.045-.024-.093-.097-.043-.187zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.437-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2',
    'info' => 'M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2',
];
?>
<div class="container mt-3">
    <?php foreach ($flashes as $flash): ?>
        <?php $type = (string) $flash['type']; ?>
        <div class="alert alert--<?= e($type) ?>" role="alert">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="<?= $icons[$type] ?? $icons['info'] ?>"/>
            </svg>
            <div class="alert__body"><?= e((string) $flash['message']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

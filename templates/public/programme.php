<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $training
 * @var array<string, array<int, array<string, mixed>>> $days
 */
View::extend('layouts/public');

$subjectPending = (bool) $training['subject_pending'] || ($training['subject'] ?? '') === '';
?>

<section class="section">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="small text-muted mb-3">
            <a href="<?= e(url('/')) ?>">Accueil</a> › <span aria-current="page">Programme</span>
        </nav>

        <div class="section__header">
            <p class="section__eyebrow">Programme</p>
            <h1>Déroulé de la formation</h1>
            <p class="lead">
                Du <?= e(fr_date((string) $training['starts_on'], true)) ?>
                au <?= e(fr_date((string) $training['ends_on'], true)) ?>,
                au <?= e((string) $training['location_name']) ?>.
            </p>
        </div>

        <?php if ($subjectPending): ?>
            <div class="alert alert--info" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                </svg>
                <div class="alert__body">
                    <strong>Le programme détaillé est en cours de finalisation.</strong>
                    La structure de la semaine ci-dessous est arrêtée&nbsp;; les contenus et les
                    intervenants seront précisés prochainement. Les personnes déjà inscrites
                    recevront le programme complet par courriel.
                </div>
            </div>
        <?php endif; ?>

        <ol class="timeline">
            <?php foreach ($days as $date => $slots): ?>
                <?php $day = new DateTimeImmutable((string) $date); ?>
                <li class="timeline__day">
                    <div>
                        <p class="timeline__date mb-0"><?= e(ucfirst(fr_date($day, true))) ?></p>
                        <p class="small text-muted mb-0"><?= e(count($slots)) ?> session(s)</p>
                    </div>
                    <ul class="timeline__slots">
                        <?php foreach ($slots as $slot): ?>
                            <li class="timeline__slot">
                                <span class="timeline__time">
                                    <?= e((string) $slot['starts_at']) ?>–<?= e((string) $slot['ends_at']) ?>
                                </span>
                                <div>
                                    <strong><?= e((string) ($slot['topic'] ?? 'À définir')) ?></strong>
                                    <?php if (!empty($slot['speaker'])): ?>
                                        <br><span class="text-muted small">
                                            Intervenant&nbsp;: <?= e((string) $slot['speaker']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!(bool) $slot['counts_for_attendance']): ?>
                                        <br><span class="badge badge--neutral">Hors calcul de présence</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="panel panel--accent mt-4">
            <h2 style="font-size: 1.125rem;">Diplôme de participation</h2>
            <p class="mb-0">
                Un diplôme nominatif au format PDF est délivré aux participants ayant assisté à au moins
                <strong><?= e(\Craw\Domain\AttendanceCalculator::format(\Craw\Core\Settings::minAttendanceRate())) ?></strong>
                des créneaux de la semaine. Il est envoyé par courriel à l'issue de la formation et porte
                un code de vérification permettant d'en attester l'authenticité.
            </p>
        </div>

        <div class="btn-group mt-4">
            <a class="btn btn--primary btn--pill" href="<?= e(url('/inscription')) ?>">S'inscrire</a>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/infos-pratiques')) ?>">Infos pratiques</a>
        </div>
    </div>
</section>

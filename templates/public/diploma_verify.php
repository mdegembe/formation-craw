<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * Verification publique d'un diplome.
 *
 * On n'affiche que ce qui atteste l'authenticite : aucune coordonnee.
 *
 * @var array<string, mixed>|null $diploma
 */
View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow">
        <?php if ($diploma === null): ?>
            <div class="alert alert--error" role="alert">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                </svg>
                <div class="alert__body">
                    <strong>Ce code de vérification ne correspond à aucun diplôme délivré.</strong>
                    Vérifiez la saisie du code, ou contactez le CRA-W si vous avez un doute sur
                    l'authenticité du document présenté.
                </div>
            </div>

            <h1>Diplôme non reconnu</h1>
            <p class="lead">
                Le code figurant sur le document que vous vérifiez n'est pas enregistré dans notre
                base de données.
            </p>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/contact')) ?>">Nous contacter</a>
        <?php else: ?>
            <div class="alert alert--success" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.73.73 0 0 1-1.065.02L3.217 8.384a.75.75 0 0 1 0-1.06.73.73 0 0 1 1.04 0l3.052 3.093 5.4-6.425z"/>
                </svg>
                <div class="alert__body">
                    <strong>Diplôme authentique.</strong>
                    Ce document a bien été délivré par le Centre wallon de Recherches agronomiques.
                </div>
            </div>

            <h1>Vérification d'un diplôme</h1>

            <div class="panel panel--accent mt-3">
                <dl class="definition-list">
                    <dt>Numéro du diplôme</dt>
                    <dd class="fw-600"><?= e((string) $diploma['number']) ?></dd>

                    <dt>Titulaire</dt>
                    <dd class="fw-600">
                        <?= e(trim((string) $diploma['first_name'] . ' ' . (string) $diploma['last_name'])) ?>
                    </dd>

                    <dt>Formation</dt>
                    <dd>
                        <?php $subject = (string) ($diploma['subject'] ?? ''); ?>
                        <?= e($subject !== '' ? $subject : (string) $diploma['training_title']) ?>
                    </dd>

                    <dt>Période</dt>
                    <dd>
                        Du <?= e(fr_date((string) $diploma['starts_on'])) ?>
                        au <?= e(fr_date((string) $diploma['ends_on'])) ?>
                    </dd>

                    <dt>Taux de présence</dt>
                    <dd>
                        <?= e(\Craw\Domain\AttendanceCalculator::format((float) $diploma['attendance_rate'])) ?>
                    </dd>

                    <dt>Date de délivrance</dt>
                    <dd><?= e(fr_date((string) $diploma['generated_at'])) ?></dd>
                </dl>
            </div>

            <p class="small text-muted mt-3">
                Cette page atteste uniquement de l'existence et de la validité du diplôme portant ce
                code. Aucune coordonnée du titulaire n'y est divulguée.
            </p>
        <?php endif; ?>
    </div>
</section>

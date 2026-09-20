<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * Public certificate verification (English).
 *
 * Only what confirms authenticity is shown: no contact details.
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
                    <strong>This verification code does not match any certificate issued.</strong>
                    Check the code you entered, or contact CRA-W if you have any doubt about the
                    authenticity of the document presented.
                </div>
            </div>

            <h1>Certificate not recognised</h1>
            <p class="lead">
                The code shown on the document you are checking is not registered in our
                database.
            </p>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/contact')) ?>">Contact us</a>
        <?php else: ?>
            <div class="alert alert--success" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.73.73 0 0 1-1.065.02L3.217 8.384a.75.75 0 0 1 0-1.06.73.73 0 0 1 1.04 0l3.052 3.093 5.4-6.425z"/>
                </svg>
                <div class="alert__body">
                    <strong>Authentic certificate.</strong>
                    This document was indeed issued by the Walloon Agricultural Research Centre.
                </div>
            </div>

            <h1>Certificate verification</h1>

            <div class="panel panel--accent mt-3">
                <dl class="definition-list">
                    <dt>Certificate number</dt>
                    <dd class="fw-600"><?= e((string) $diploma['number']) ?></dd>

                    <dt>Holder</dt>
                    <dd class="fw-600">
                        <?= e(trim((string) $diploma['first_name'] . ' ' . (string) $diploma['last_name'])) ?>
                    </dd>

                    <dt>Training</dt>
                    <dd>
                        <?php $subject = (string) ($diploma['subject'] ?? ''); ?>
                        <?= e($subject !== '' ? $subject : (string) $diploma['training_title']) ?>
                    </dd>

                    <dt>Period</dt>
                    <dd>
                        From <?= e(en_date((string) $diploma['starts_on'])) ?>
                        to <?= e(en_date((string) $diploma['ends_on'])) ?>
                    </dd>

                    <dt>Attendance rate</dt>
                    <dd>
                        <?= e(\Craw\Domain\AttendanceCalculator::format((float) $diploma['attendance_rate'])) ?>
                    </dd>

                    <dt>Date issued</dt>
                    <dd><?= e(en_date((string) $diploma['generated_at'])) ?></dd>
                </dl>
            </div>

            <p class="small text-muted mt-3">
                This page only confirms the existence and validity of the certificate bearing this
                code. No contact details of the holder are disclosed here.
            </p>
        <?php endif; ?>
    </div>
</section>

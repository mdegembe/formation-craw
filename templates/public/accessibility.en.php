<?php

declare(strict_types=1);

use Craw\Core\View;

View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow prose">
        <h1>Accessibility statement</h1>
        <p class="lead">
            The Walloon Agricultural Research Centre is committed to making this site accessible,
            in accordance with Directive (EU) 2016/2102 and the Walloon decree on the accessibility
            of public bodies' websites and mobile applications.
        </p>

        <h2>Compliance status</h2>
        <p>
            This site is designed to be <strong>compliant with WCAG 2.1 level AA</strong>.
            A full assessment by a third-party body has yet to be carried out; this statement is
            based on a self-assessment.
        </p>

        <h2>Measures implemented</h2>
        <ul>
            <li>semantic structure: a single level-1 heading per page and a continuous
                heading hierarchy;</li>
            <li>a skip link giving direct access to the main content;</li>
            <li>navigation fully usable with the keyboard, with a permanently visible focus
                indicator;</li>
            <li>every form field carries an explicit label; errors are described in text and
                linked to the relevant field;</li>
            <li>colour contrasts checked at AA level, in both light and dark themes;</li>
            <li>no information is conveyed by colour alone: statuses always combine an icon and a
                label;</li>
            <li>decorative images hidden from assistive technologies, informative images
                described;</li>
            <li>animations reduced when the system reports the
                <code>prefers-reduced-motion</code> preference;</li>
            <li>the site remains fully usable when JavaScript is disabled.</li>
        </ul>

        <h2>Non-accessible content</h2>
        <p>
            Certificates and payment instructions are provided as PDF files. These documents are
            generated from textual content and remain readable by a screen reader, but they have
            not undergone full PDF/UA tagging. An on-screen text version of this information is
            available on the payment page and in the participant area.
        </p>

        <h2>Report a problem</h2>
        <p>
            If you have difficulty accessing information or a feature on this site, write to us at
            <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>.
            We are committed to responding and providing the information you need by another means
            as soon as possible.
        </p>

        <h2>Recourse</h2>
        <p>
            If you do not receive a satisfactory response, you can contact the Ombudsman of
            Wallonia and the Wallonia-Brussels Federation:
            <a href="https://www.le-mediateur.be" target="_blank" rel="noopener noreferrer">
                www.le-mediateur.be</a>.
        </p>

        <p class="text-muted small">
            Statement drawn up on <?= e(en_date(date('Y-m-d'))) ?>, based on a self-assessment.
        </p>
    </div>
</section>

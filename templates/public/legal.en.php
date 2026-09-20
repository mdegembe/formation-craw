<?php

declare(strict_types=1);

use Craw\Core\View;

View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow prose">
        <h1>Legal notice</h1>

        <h2>Site publisher</h2>
        <p>
            <strong>Walloon Agricultural Research Centre (CRA-W)</strong><br>
            Rue de Liroux 9<br>
            5030 Gembloux — Belgium<br>
            Phone: <?= e((string) setting('contact.phone')) ?><br>
            Email: <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>
        </p>
        <p>
            CRA-W is a public interest body of the Walloon Region. Its institutional
            website is available at
            <a href="https://www.cra.wallonie.be/fr" target="_blank" rel="noopener noreferrer">
                www.cra.wallonie.be</a>.
        </p>

        <h2>Purpose of the site</h2>
        <p>
            This site is dedicated to presenting and managing registrations for a training
            organised by CRA-W. It does not replace the Centre's institutional website.
        </p>

        <h2>Intellectual property</h2>
        <p>
            Content published on this site (text, images, documents) is the property of CRA-W,
            unless stated otherwise. Any reproduction or representation, in whole or in part, for
            purposes other than individual and private consultation, requires prior authorisation.
        </p>

        <h2>Liability</h2>
        <p>
            CRA-W makes every effort to ensure the accuracy of the information published. The
            programme, speakers and practical arrangements may nevertheless change: registered
            participants are informed of any change by email.
        </p>
        <p>
            Links to third-party sites are provided for information purposes only; CRA-W has no
            control over their content and cannot be held responsible for it.
        </p>

        <h2>Personal data</h2>
        <p>
            The processing of personal data is described in the
            <a href="<?= e(url('/confidentialite')) ?>">privacy notice</a>.
        </p>

        <h2>Accessibility</h2>
        <p>
            CRA-W's digital accessibility commitments are set out in the
            <a href="<?= e(url('/accessibilite')) ?>">accessibility statement</a>.
        </p>

        <h2>Hosting</h2>
        <p>
            This site is hosted on the infrastructure chosen by CRA-W for its digital services.
            The host's contact details are provided on written request to the contact address
            above.
        </p>
    </div>
</section>

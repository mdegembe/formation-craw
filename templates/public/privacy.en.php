<?php

declare(strict_types=1);

use Craw\Core\View;

/** @var int $retentionYears */
View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow prose">
        <h1>Privacy notice</h1>
        <p class="lead">
            This notice describes how the Walloon Agricultural Research Centre (CRA-W)
            processes personal data collected on this site as part of registration for the
            training.
        </p>

        <h2>Who is the data controller?</h2>
        <p>
            The data controller is the <strong>Walloon Agricultural Research Centre</strong>,
            Rue de Liroux 9, 5030 Gembloux, Belgium.
            For any question about your data, write to
            <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>.
        </p>

        <h2>What data is collected?</h2>
        <ul>
            <li>
                <strong>Identity and contact details</strong>: title, last name, first name, email
                address, phone number, date of birth (optional), organisation and job title.
            </li>
            <li>
                <strong>Billing address</strong>: only if you request an invoice
                (street, postal code, city, country, VAT number).
            </li>
            <li>
                <strong>Dietary preferences</strong>: chosen diet and associated comments.
            </li>
            <li>
                <strong>Allergies and intolerances</strong>: this information is
                <strong>health-related data</strong>. It is only collected with your explicit and
                distinct consent, and only if you declare having any.
            </li>
            <li>
                <strong>Payment data</strong>: amount due, structured reference, date and amount of
                payment received. No banking data (card number, credentials) is collected by this
                site.
            </li>
            <li>
                <strong>Attendance</strong>: the sessions you did not attend, in order to calculate
                the attendance rate that determines eligibility for the certificate.
            </li>
            <li>
                <strong>Technical data</strong>: a non-reversible fingerprint of your IP address and
                your browser, kept to prevent abuse (automated registrations).
            </li>
        </ul>

        <h2>Why is this data processed?</h2>
        <table class="table">
            <caption class="visually-hidden">Purposes, legal bases and retention periods</caption>
            <thead>
                <tr><th scope="col">Purpose</th><th scope="col">Legal basis</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Managing your registration and participation</td>
                    <td>Performance of pre-contractual and contractual measures (GDPR art. 6.1.b)</td>
                </tr>
                <tr>
                    <td>Invoicing and payment tracking</td>
                    <td>Legal obligation and performance of the contract (GDPR art. 6.1.b and 6.1.c)</td>
                </tr>
                <tr>
                    <td>Adapting meals (allergies, diets)</td>
                    <td>Explicit consent (GDPR art. 9.2.a)</td>
                </tr>
                <tr>
                    <td>Issuing the certificate of attendance</td>
                    <td>Performance of the contract (GDPR art. 6.1.b)</td>
                </tr>
                <tr>
                    <td>Abuse prevention and site security</td>
                    <td>Legitimate interest (GDPR art. 6.1.f)</td>
                </tr>
            </tbody>
        </table>

        <h2>Who has access to this data?</h2>
        <p>
            Only CRA-W staff in charge of organising the training have access to it.
            Meal-related information — headcounts by diet and allergy details —
            is passed on to the caterer providing the catering, and to them only.
        </p>
        <p>
            <strong>No data is passed on to any third-party audience measurement, advertising or
            social network service.</strong> This site uses no external service: fonts, images and
            scripts are served from our own servers.
        </p>

        <h2>How long is it kept?</h2>
        <ul>
            <li>
                <strong>Unfinished registrations</strong>: automatically deleted after 48 hours.
            </li>
            <li>
                <strong>Registration record</strong>: <?= e((string) $retentionYears) ?> years after the
                end of the training, then anonymised.
            </li>
            <li>
                <strong>Allergies and dietary preferences</strong>: deleted as soon as the training
                ends, as the purpose is then fulfilled.
            </li>
            <li>
                <strong>Accounting records</strong>: kept for the legal periods applicable in
                Belgium.
            </li>
            <li>
                <strong>Certificates issued</strong>: kept indefinitely, so that the authenticity of
                a certificate presented later can be verified.
            </li>
        </ul>

        <h2>What are your rights?</h2>
        <p>
            You have the right to access, rectify, erase, restrict and object to the processing of
            your data, as well as the right to data portability. Where processing relies on your
            consent (allergies), you may withdraw it at any time, without affecting the lawfulness
            of processing carried out beforehand.
        </p>
        <p>
            To exercise these rights, write to
            <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>
            stating your registration reference.
        </p>
        <p>
            You may also lodge a complaint with the Belgian Data Protection Authority:
            <a href="https://www.autoriteprotectiondonnees.be" target="_blank"
                              rel="noopener noreferrer">www.autoriteprotectiondonnees.be</a>.
        </p>

        <h2>Cookies</h2>
        <p>
            This site uses <strong>no audience-measurement or advertising cookies</strong>.
            Only the following are used:
        </p>
        <ul>
            <li>a <strong>technical session cookie</strong>, required for forms to work and for
                security (CSRF protection), deleted when the browser is closed;</li>
            <li>a <strong>theme preference</strong> (light or dark), stored locally in your browser
                and never sent to our servers.</li>
        </ul>
        <p>
            As these elements are strictly necessary or purely local, no consent banner is
            required.
        </p>

        <p class="text-muted small">Last updated: <?= e(en_date(date('Y-m-d'))) ?>.</p>
    </div>
</section>

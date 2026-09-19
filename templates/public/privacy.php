<?php

declare(strict_types=1);

use Craw\Core\View;

/** @var int $retentionYears */
View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow prose">
        <h1>Déclaration de confidentialité</h1>
        <p class="lead">
            Cette déclaration décrit comment le Centre wallon de Recherches agronomiques (CRA-W)
            traite les données personnelles collectées sur ce site dans le cadre de l'inscription
            à la formation.
        </p>

        <h2>Qui est responsable du traitement&nbsp;?</h2>
        <p>
            Le responsable du traitement est le <strong>Centre wallon de Recherches agronomiques</strong>,
            Rue de Liroux 9, 5030 Gembloux, Belgique.
            Pour toute question relative à vos données, écrivez à
            <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>.
        </p>

        <h2>Quelles données sont collectées&nbsp;?</h2>
        <ul>
            <li>
                <strong>Identité et contact</strong> : civilité, nom, prénom, adresse e-mail, téléphone,
                date de naissance (facultative), organisation et fonction.
            </li>
            <li>
                <strong>Adresse de facturation</strong> : uniquement si vous demandez une facture
                (rue, code postal, localité, pays, numéro de TVA).
            </li>
            <li>
                <strong>Préférences alimentaires</strong> : régime choisi et commentaires associés.
            </li>
            <li>
                <strong>Allergies et intolérances</strong> : ces informations relèvent des
                <strong>données de santé</strong>. Elles ne sont collectées qu'avec votre
                consentement explicite et distinct, et uniquement si vous déclarez en avoir.
            </li>
            <li>
                <strong>Données de paiement</strong> : montant dû, communication structurée, date et
                montant du paiement reçu. Aucune donnée bancaire (numéro de carte, identifiants) n'est
                collectée par ce site.
            </li>
            <li>
                <strong>Présences</strong> : les créneaux auxquels vous n'avez pas assisté, et le motif
                éventuel, afin de calculer le taux de présence conditionnant la délivrance du diplôme.
            </li>
            <li>
                <strong>Données techniques</strong> : une empreinte non réversible de votre adresse IP
                et votre navigateur, conservées pour prévenir les abus (inscriptions automatisées).
            </li>
        </ul>

        <h2>Pourquoi ces données sont-elles traitées&nbsp;?</h2>
        <table class="table">
            <caption class="visually-hidden">Finalités, bases légales et durées de conservation</caption>
            <thead>
                <tr><th scope="col">Finalité</th><th scope="col">Base légale</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gestion de votre inscription et de votre participation</td>
                    <td>Exécution d'une mesure précontractuelle et contractuelle (art. 6.1.b RGPD)</td>
                </tr>
                <tr>
                    <td>Facturation et suivi du paiement</td>
                    <td>Obligation légale et exécution du contrat (art. 6.1.b et 6.1.c RGPD)</td>
                </tr>
                <tr>
                    <td>Adaptation des repas (allergies, régimes)</td>
                    <td>Consentement explicite (art. 9.2.a RGPD)</td>
                </tr>
                <tr>
                    <td>Délivrance du diplôme de participation</td>
                    <td>Exécution du contrat (art. 6.1.b RGPD)</td>
                </tr>
                <tr>
                    <td>Prévention des abus et sécurité du site</td>
                    <td>Intérêt légitime (art. 6.1.f RGPD)</td>
                </tr>
            </tbody>
        </table>

        <h2>Qui a accès à ces données&nbsp;?</h2>
        <p>
            Seules les personnes du CRA-W chargées de l'organisation de la formation y accèdent.
            Les informations relatives aux repas — effectifs par régime et détail des allergies —
            sont transmises au traiteur assurant la restauration, et à lui seul.
        </p>
        <p>
            <strong>Aucune donnée n'est transmise à un service tiers d'analyse d'audience, de publicité
            ou de réseau social.</strong> Ce site n'utilise aucun service externe&nbsp;: les polices de
            caractères, les images et les scripts sont servis depuis nos propres serveurs.
        </p>

        <h2>Combien de temps sont-elles conservées&nbsp;?</h2>
        <ul>
            <li>
                <strong>Inscriptions non finalisées</strong> : supprimées automatiquement après 48 heures.
            </li>
            <li>
                <strong>Dossier d'inscription</strong> : <?= e((string) $retentionYears) ?> ans après la fin
                de la formation, puis anonymisé.
            </li>
            <li>
                <strong>Allergies et régimes alimentaires</strong> : supprimés dès la fin de la formation,
                la finalité étant alors épuisée.
            </li>
            <li>
                <strong>Pièces comptables</strong> : conservées selon les durées légales applicables
                en Belgique.
            </li>
            <li>
                <strong>Diplômes délivrés</strong> : conservés durablement, afin de pouvoir attester
                l'authenticité d'un diplôme présenté ultérieurement.
            </li>
        </ul>

        <h2>Quels sont vos droits&nbsp;?</h2>
        <p>
            Vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation et
            d'opposition, ainsi que d'un droit à la portabilité de vos données. Lorsque le traitement
            repose sur votre consentement (allergies), vous pouvez le retirer à tout moment, sans que
            cela remette en cause la licéité du traitement effectué auparavant.
        </p>
        <p>
            Pour exercer ces droits, écrivez à
            <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>
            en précisant votre référence d'inscription.
        </p>
        <p>
            Vous pouvez également introduire une réclamation auprès de l'Autorité de protection des
            données&nbsp;: <a href="https://www.autoriteprotectiondonnees.be" target="_blank"
                              rel="noopener noreferrer">www.autoriteprotectiondonnees.be</a>.
        </p>

        <h2>Cookies</h2>
        <p>
            Ce site n'utilise <strong>aucun cookie de mesure d'audience ni de publicité</strong>.
            Seuls sont employés&nbsp;:
        </p>
        <ul>
            <li>un <strong>cookie de session technique</strong>, nécessaire au fonctionnement des
                formulaires et à la sécurité (protection anti-CSRF), supprimé à la fermeture du
                navigateur&nbsp;;</li>
            <li>une <strong>préférence de thème</strong> (clair ou sombre), stockée localement dans
                votre navigateur et jamais transmise à nos serveurs.</li>
        </ul>
        <p>
            Ces éléments étant strictement nécessaires ou purement locaux, aucun bandeau de
            consentement n'est requis.
        </p>

        <p class="text-muted small">Dernière mise à jour&nbsp;: <?= e(fr_date(date('Y-m-d'))) ?>.</p>
    </div>
</section>

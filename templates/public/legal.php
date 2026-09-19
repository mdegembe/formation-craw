<?php

declare(strict_types=1);

use Craw\Core\View;

View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow prose">
        <h1>Mentions légales</h1>

        <h2>Éditeur du site</h2>
        <p>
            <strong>Centre wallon de Recherches agronomiques (CRA-W)</strong><br>
            Rue de Liroux 9<br>
            5030 Gembloux — Belgique<br>
            Téléphone&nbsp;: <?= e((string) setting('contact.phone')) ?><br>
            Courriel&nbsp;: <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>
        </p>
        <p>
            Le CRA-W est un organisme d'intérêt public de la Région wallonne. Son site institutionnel
            est accessible à l'adresse
            <a href="https://www.cra.wallonie.be/fr" target="_blank" rel="noopener noreferrer">
                www.cra.wallonie.be</a>.
        </p>

        <h2>Objet du site</h2>
        <p>
            Ce site est dédié à la présentation et à la gestion des inscriptions d'une formation
            organisée par le CRA-W. Il ne se substitue pas au site institutionnel du Centre.
        </p>

        <h2>Propriété intellectuelle</h2>
        <p>
            Les contenus publiés sur ce site (textes, images, documents) sont la propriété du CRA-W,
            sauf mention contraire. Toute reproduction ou représentation, totale ou partielle, à
            d'autres fins que la consultation individuelle et privée, est soumise à autorisation
            préalable.
        </p>

        <h2>Responsabilité</h2>
        <p>
            Le CRA-W met tout en œuvre pour assurer l'exactitude des informations publiées. Le
            programme, les intervenants et les modalités pratiques sont néanmoins susceptibles
            d'évoluer&nbsp;: les personnes inscrites sont informées de toute modification par courriel.
        </p>
        <p>
            Les liens vers des sites tiers sont fournis à titre d'information&nbsp;; le CRA-W n'exerce
            aucun contrôle sur leur contenu et ne saurait en être tenu responsable.
        </p>

        <h2>Données personnelles</h2>
        <p>
            Le traitement des données personnelles est décrit dans la
            <a href="<?= e(url('/confidentialite')) ?>">déclaration de confidentialité</a>.
        </p>

        <h2>Accessibilité</h2>
        <p>
            Les engagements du CRA-W en matière d'accessibilité numérique figurent dans la
            <a href="<?= e(url('/accessibilite')) ?>">déclaration d'accessibilité</a>.
        </p>

        <h2>Hébergement</h2>
        <p>
            Ce site est hébergé sur l'infrastructure retenue par le CRA-W pour ses services
            numériques. Les coordonnées de l'hébergeur sont communiquées sur demande écrite à
            l'adresse de contact ci-dessus.
        </p>
    </div>
</section>

<?php

declare(strict_types=1);

use Craw\Core\View;

View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow prose">
        <h1>Déclaration d'accessibilité</h1>
        <p class="lead">
            Le Centre wallon de Recherches agronomiques s'engage à rendre ce site accessible,
            conformément à la directive (UE) 2016/2102 et au décret wallon relatif à l'accessibilité
            des sites internet et applications mobiles des organismes publics.
        </p>

        <h2>État de conformité</h2>
        <p>
            Ce site est conçu pour être <strong>conforme au niveau AA des WCAG 2.1</strong>.
            Une évaluation complète par un organisme tiers reste à réaliser&nbsp;; la présente
            déclaration repose sur une auto-évaluation.
        </p>

        <h2>Mesures mises en œuvre</h2>
        <ul>
            <li>structure sémantique&nbsp;: un seul titre de niveau&nbsp;1 par page et hiérarchie
                de titres continue&nbsp;;</li>
            <li>lien d'évitement permettant d'accéder directement au contenu principal&nbsp;;</li>
            <li>navigation entièrement utilisable au clavier, avec un indicateur de focus visible
                en permanence&nbsp;;</li>
            <li>tous les champs de formulaire portent une étiquette explicite&nbsp;; les erreurs sont
                décrites en texte et rattachées au champ concerné&nbsp;;</li>
            <li>contrastes de couleurs vérifiés au niveau AA, en thème clair comme en thème
                sombre&nbsp;;</li>
            <li>aucune information n'est portée par la seule couleur&nbsp;: les statuts associent
                systématiquement une icône et un libellé&nbsp;;</li>
            <li>images décoratives masquées aux technologies d'assistance, images informatives
                décrites&nbsp;;</li>
            <li>animations réduites lorsque le système signale la préférence
                <code>prefers-reduced-motion</code>&nbsp;;</li>
            <li>le site reste entièrement utilisable lorsque JavaScript est désactivé.</li>
        </ul>

        <h2>Contenus non accessibles</h2>
        <p>
            Les diplômes et les instructions de paiement sont fournis au format PDF. Ces documents
            sont générés à partir de contenus textuels et restent lisibles par un lecteur d'écran,
            mais ils n'ont pas fait l'objet d'un balisage PDF/UA complet. Une version texte de ces
            informations est disponible à l'écran, sur la page de paiement et dans l'espace
            participant.
        </p>

        <h2>Signaler un problème</h2>
        <p>
            Si vous rencontrez une difficulté d'accès à une information ou à une fonctionnalité de ce
            site, écrivez-nous à
            <a href="mailto:<?= e((string) setting('contact.email')) ?>">
                <?= e((string) setting('contact.email')) ?></a>.
            Nous nous engageons à vous répondre et à vous fournir l'information recherchée par un
            autre moyen dans les meilleurs délais.
        </p>

        <h2>Voie de recours</h2>
        <p>
            Si vous n'obtenez pas de réponse satisfaisante, vous pouvez saisir le Médiateur de la
            Wallonie et de la Fédération Wallonie-Bruxelles&nbsp;:
            <a href="https://www.le-mediateur.be" target="_blank" rel="noopener noreferrer">
                www.le-mediateur.be</a>.
        </p>

        <p class="text-muted small">
            Déclaration établie le <?= e(fr_date(date('Y-m-d'))) ?>, sur la base d'une auto-évaluation.
        </p>
    </div>
</section>

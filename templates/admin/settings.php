<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\Settings;
use Craw\Core\View;

/**
 * @var array<string, mixed>             $training
 * @var array<int, array<string, mixed>> $days
 * @var float                            $minRate
 */
View::extend('layouts/admin');
?>

<div class="admin-header">
    <div>
        <h1>Configuration</h1>
        <p class="text-muted mb-0">
            Contenus publics, tarif, seuil de présence, coordonnées bancaires et paramètres RGPD.
        </p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/configuration')) ?>" data-once>
    <?= Csrf::field() ?>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">La formation</h2>

        <div class="form-grid form-grid--2 mt-3">
            <div class="form-field form-field--full">
                <label class="form-label" for="title">Intitulé</label>
                <input class="form-control" type="text" id="title" name="title" maxlength="190"
                       value="<?= e((string) $training['title']) ?>">
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="subject">Sujet de la session</label>
                <input class="form-control" type="text" id="subject" name="subject" maxlength="190"
                       value="<?= e((string) ($training['subject'] ?? '')) ?>"
                       placeholder="Ex. : Agriculture de conservation des sols">
                <div class="form-check mt-2">
                    <input type="checkbox" id="subject_pending" name="subject_pending" value="1"
                        <?= (int) $training['subject_pending'] === 1 ? 'checked' : '' ?>>
                    <label for="subject_pending">
                        Le sujet n'est pas encore arrêté (affiche « en cours de définition » sur le site)
                    </label>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="starts_on">Premier jour</label>
                <input class="form-control" type="date" id="starts_on" name="starts_on"
                       value="<?= e((string) $training['starts_on']) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="ends_on">Dernier jour</label>
                <input class="form-control" type="date" id="ends_on" name="ends_on"
                       value="<?= e((string) $training['ends_on']) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="location_name">Bâtiment</label>
                <input class="form-control" type="text" id="location_name" name="location_name" maxlength="190"
                       value="<?= e((string) $training['location_name']) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="location_address">Adresse</label>
                <input class="form-control" type="text" id="location_address" name="location_address" maxlength="255"
                       value="<?= e((string) $training['location_address']) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="price">Prix par participant (€)</label>
                <input class="form-control" type="text" id="price" name="price" inputmode="decimal"
                       value="<?= e(number_format((int) $training['price_cents'] / 100, 2, ',', '')) ?>">
                <p class="form-hint">
                    Mettre <code>0</code> rend la formation gratuite&nbsp;: tout le circuit de paiement
                    disparaît alors du site et des courriels.
                </p>
            </div>

            <div class="form-field">
                <label class="form-label" for="capacity">Nombre de places</label>
                <input class="form-control" type="number" id="capacity" name="capacity" min="1" max="5000"
                       value="<?= e((string) $training['capacity']) ?>">
                <p class="form-hint">Au-delà, les demandes basculent en liste d'attente.</p>
            </div>

            <div class="form-field">
                <label class="form-label" for="registration_deadline">Clôture des inscriptions</label>
                <input class="form-control" type="date" id="registration_deadline" name="registration_deadline"
                       value="<?= e((string) ($training['registration_deadline'] ?? '')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="status">État de la formation</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach ([
                        'draft' => 'Brouillon (non publiée)',
                        'open' => 'Inscriptions ouvertes',
                        'closed' => 'Inscriptions closes',
                        'archived' => 'Archivée',
                    ] as $value => $label): ?>
                        <option value="<?= e($value) ?>"
                            <?= (string) $training['status'] === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="summary">Résumé (accroche de l'accueil)</label>
                <textarea class="form-control" id="summary" name="summary" rows="2"
                ><?= e((string) ($training['summary'] ?? '')) ?></textarea>
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="description">Présentation détaillée</label>
                <textarea class="form-control" id="description" name="description" rows="10"
                          style="font-family: var(--craw-font-mono); font-size: 0.9375rem;"
                ><?= e((string) ($training['description'] ?? '')) ?></textarea>
                <p class="form-hint">Markdown accepté (## titres, **gras**, listes, liens).</p>
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="practical_info">Informations pratiques</label>
                <textarea class="form-control" id="practical_info" name="practical_info" rows="10"
                          style="font-family: var(--craw-font-mono); font-size: 0.9375rem;"
                ><?= e((string) ($training['practical_info'] ?? '')) ?></textarea>
                <p class="form-hint">Accès, repas, hébergement, conditions d'annulation.</p>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Diplômes et présences</h2>
        <p class="text-muted small">
            La présence est binaire : un participant est présent ou absent, sans motif ni
            exception à arbitrer.
        </p>

        <div class="form-grid form-grid--2 mt-3">
            <div class="form-field">
                <label class="form-label" for="min_attendance">
                    Taux de présence minimal (%)
                </label>
                <input class="form-control" type="number" id="min_attendance" name="min_attendance"
                       min="0" max="100" step="1"
                       value="<?= e((string) round($minRate * 100)) ?>">
                <p class="form-hint">
                    Condition d'obtention du diplôme. Valeur par défaut&nbsp;: 80&nbsp;%.
                    Également paramétrable via <code>DIPLOMA_MIN_ATTENDANCE</code> dans le
                    fichier <code>.env</code>, cette valeur-ci ayant la priorité.
                </p>
            </div>

            <div class="form-field">
                <label class="form-label" for="diploma_prefix">Préfixe des numéros de diplôme</label>
                <input class="form-control" type="text" id="diploma_prefix" name="diploma_prefix" maxlength="10"
                       value="<?= e((string) Settings::get('diploma.number_prefix')) ?>">
                <p class="form-hint">Ex. : <code>CRAW</code> donne <code>CRAW-2027-0001</code>.</p>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Coordonnées bancaires</h2>
        <p class="text-muted small">
            Ces informations alimentent la page de paiement, le QR code SEPA et les courriels.
            <strong>Remplacez l'IBAN de démonstration avant la mise en production.</strong>
        </p>

        <div class="form-grid form-grid--2 mt-3">
            <div class="form-field form-field--full">
                <label class="form-label" for="beneficiary">Bénéficiaire</label>
                <input class="form-control" type="text" id="beneficiary" name="beneficiary" maxlength="70"
                       value="<?= e((string) Settings::get('payment.beneficiary')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="iban">IBAN</label>
                <input class="form-control" type="text" id="iban" name="iban" maxlength="40"
                       style="font-family: var(--craw-font-mono);"
                       value="<?= e((string) Settings::get('payment.iban')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="bic">BIC</label>
                <input class="form-control" type="text" id="bic" name="bic" maxlength="11"
                       style="font-family: var(--craw-font-mono);"
                       value="<?= e((string) Settings::get('payment.bic')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="deadline_days">Délai de paiement (jours)</label>
                <input class="form-control" type="number" id="deadline_days" name="deadline_days" min="1" max="365"
                       value="<?= e((string) Settings::get('payment.deadline_days')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="reminder_days">Relances (jours avant échéance)</label>
                <input class="form-control" type="text" id="reminder_days" name="reminder_days" maxlength="40"
                       value="<?= e((string) Settings::get('payment.reminder_days')) ?>">
                <p class="form-hint">
                    Liste séparée par des virgules, ex. <code>7,3</code>. Un dernier rappel part
                    automatiquement une fois l'échéance dépassée.
                </p>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Contact, RGPD et référencement</h2>

        <div class="form-grid form-grid--2 mt-3">
            <div class="form-field">
                <label class="form-label" for="contact_email">Adresse de contact</label>
                <input class="form-control" type="email" id="contact_email" name="contact_email" maxlength="190"
                       value="<?= e((string) Settings::get('contact.email')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="contact_phone">Téléphone</label>
                <input class="form-control" type="text" id="contact_phone" name="contact_phone" maxlength="40"
                       value="<?= e((string) Settings::get('contact.phone')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="retention_years">Conservation des données (années)</label>
                <input class="form-control" type="number" id="retention_years" name="retention_years"
                       min="1" max="30"
                       value="<?= e((string) Settings::get('privacy.retention_years')) ?>">
                <p class="form-hint">Durée annoncée dans la déclaration de confidentialité.</p>
            </div>

            <div class="form-field">
                <label class="form-label" for="seo_description">Description par défaut (SEO)</label>
                <textarea class="form-control" id="seo_description" name="seo_description" rows="2" maxlength="255"
                ><?= e((string) Settings::get('seo.default_description')) ?></textarea>
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="meta_title">Titre SEO de la page d'accueil</label>
                <input class="form-control" type="text" id="meta_title" name="meta_title" maxlength="190"
                       value="<?= e((string) ($training['meta_title'] ?? '')) ?>">
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="meta_description">Description SEO de la page d'accueil</label>
                <textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="255"
                ><?= e((string) ($training['meta_description'] ?? '')) ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--pill">Enregistrer la configuration</button>
    </div>
</form>

<form method="post" action="<?= e(url('/admin/configuration/programme')) ?>" class="mt-4" data-once>
    <?= Csrf::field() ?>

    <div class="panel">
        <h2 style="font-size: 1.125rem;">Programme et créneaux</h2>
        <p class="text-muted small">
            Chaque ligne correspond à un créneau de présence. Décocher « compte pour la présence »
            exclut le créneau du calcul du taux (utile pour un accueil ou un cocktail de clôture).
        </p>

        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Créneaux de la formation</caption>
                <thead>
                    <tr>
                        <th scope="col">Créneau</th>
                        <th scope="col">Début</th>
                        <th scope="col">Fin</th>
                        <th scope="col">Intitulé</th>
                        <th scope="col">Intervenant</th>
                        <th scope="col">Présence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <?php $id = (int) $day['id']; ?>
                        <tr>
                            <th scope="row" style="font-weight: 500; text-align: left; white-space: nowrap;">
                                <?= e((string) ($day['label'] ?? $day['day_date'])) ?>
                            </th>
                            <td>
                                <label class="visually-hidden" for="starts_at_<?= e((string) $id) ?>">Heure de début</label>
                                <input class="form-control" style="width: 6.5rem;" type="time"
                                       id="starts_at_<?= e((string) $id) ?>" name="starts_at_<?= e((string) $id) ?>"
                                       value="<?= e((string) ($day['starts_at'] ?? '')) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="ends_at_<?= e((string) $id) ?>">Heure de fin</label>
                                <input class="form-control" style="width: 6.5rem;" type="time"
                                       id="ends_at_<?= e((string) $id) ?>" name="ends_at_<?= e((string) $id) ?>"
                                       value="<?= e((string) ($day['ends_at'] ?? '')) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="topic_<?= e((string) $id) ?>">Intitulé de la session</label>
                                <input class="form-control" type="text" maxlength="190"
                                       id="topic_<?= e((string) $id) ?>" name="topic_<?= e((string) $id) ?>"
                                       value="<?= e((string) ($day['topic'] ?? '')) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="speaker_<?= e((string) $id) ?>">Intervenant</label>
                                <input class="form-control" type="text" maxlength="190"
                                       id="speaker_<?= e((string) $id) ?>" name="speaker_<?= e((string) $id) ?>"
                                       value="<?= e((string) ($day['speaker'] ?? '')) ?>">
                            </td>
                            <td class="text-center">
                                <input type="checkbox" id="counts_<?= e((string) $id) ?>"
                                       name="counts_<?= e((string) $id) ?>" value="1"
                                    <?= (int) $day['counts_for_attendance'] === 1 ? 'checked' : '' ?>>
                                <label class="visually-hidden" for="counts_<?= e((string) $id) ?>">
                                    Ce créneau compte pour le calcul de présence
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary btn--pill">Enregistrer le programme</button>
        </div>
    </div>
</form>

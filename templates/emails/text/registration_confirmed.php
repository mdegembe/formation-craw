<?php

declare(strict_types=1);

/**
 * Version texte brut : lisible partout, y compris sur un client qui bloque le
 * HTML, et indispensable pour ne pas etre classe en indesirable.
 */
$dates = sprintf(
    'du %s au %s',
    fr_date((string) $training['starts_on']),
    fr_date((string) $training['ends_on'])
);

$lines = [];
$lines[] = 'Bonjour ' . (string) $registration['first_name'] . ',';
$lines[] = '';

if ($isWaitlist) {
    $lines[] = 'Nous avons bien recu votre demande d\'inscription a la formation';
    $lines[] = (string) $training['title'] . ', ' . $dates . '.';
    $lines[] = '';
    $lines[] = 'La formation affiche complet : votre demande est enregistree en liste';
    $lines[] = 'd\'attente. Nous vous contacterons si une place se libere.';
} else {
    $lines[] = 'Votre inscription a la formation "' . (string) $training['title'] . '"';
    $lines[] = 'est enregistree. Elle se tiendra ' . $dates . ' au';
    $lines[] = (string) $training['location_name'] . ', ' . (string) $training['location_address'] . '.';
}

$lines[] = '';
$lines[] = 'Reference : ' . (string) $registration['reference'];
$lines[] = 'Participant : ' . trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name']);

if (!$isWaitlist && !$isFree) {
    $lines[] = '';
    $lines[] = '--- REGLEMENT PAR VIREMENT ---';
    $lines[] = 'Beneficiaire : ' . (string) $payment['beneficiary'];
    $lines[] = 'IBAN         : ' . (string) $payment['iban'];
    $lines[] = 'BIC          : ' . (string) $payment['bic'];
    $lines[] = 'Montant      : ' . (string) $payment['amount'];
    $lines[] = 'Communication structuree : ' . (string) $payment['communication'];
    $lines[] = '';
    $lines[] = 'Payer avec un QR code : ' . (string) $links['payment'];
} elseif ($isFree) {
    $lines[] = '';
    $lines[] = 'La participation est gratuite : aucun paiement ne vous sera demande.';
}

$lines[] = '';
$lines[] = 'Votre espace personnel (conservez ce lien) :';
$lines[] = (string) $links['space'];
$lines[] = '';
$lines[] = 'Une question ? ' . (string) $contact['email'] . ' - ' . (string) $contact['phone'];
$lines[] = '';
$lines[] = 'L\'equipe formation du CRA-W';
$lines[] = 'Centre wallon de Recherches agronomiques';
$lines[] = 'Rue de Liroux 9, 5030 Gembloux, Belgique';

echo implode("\n", $lines);

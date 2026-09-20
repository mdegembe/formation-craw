<?php

declare(strict_types=1);

/**
 * Plain-text version (English): readable everywhere, including clients that
 * block HTML, and required to avoid being flagged as spam.
 */
$dates = sprintf(
    'from %s to %s',
    en_date((string) $training['starts_on']),
    en_date((string) $training['ends_on'])
);

$lines = [];
$lines[] = 'Hello ' . (string) $registration['first_name'] . ',';
$lines[] = '';

if ($isWaitlist) {
    $lines[] = 'We have received your registration request for the training';
    $lines[] = (string) $training['title'] . ', ' . $dates . '.';
    $lines[] = '';
    $lines[] = 'The training is full: your request has been placed on the waiting';
    $lines[] = 'list. We will contact you if a place becomes available.';
} else {
    $lines[] = 'Your registration for the training "' . (string) $training['title'] . '"';
    $lines[] = 'has been recorded. It will take place ' . $dates . ' at';
    $lines[] = (string) $training['location_name'] . ', ' . (string) $training['location_address'] . '.';
}

$lines[] = '';
$lines[] = 'Reference   : ' . (string) $registration['reference'];
$lines[] = 'Participant : ' . trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name']);

if (!$isWaitlist && !$isFree) {
    $lines[] = '';
    $lines[] = '--- BANK TRANSFER ---';
    $lines[] = 'Beneficiary : ' . (string) $payment['beneficiary'];
    $lines[] = 'IBAN        : ' . (string) $payment['iban'];
    $lines[] = 'BIC         : ' . (string) $payment['bic'];
    $lines[] = 'Amount      : ' . (string) $payment['amount'];
    $lines[] = 'Structured reference : ' . (string) $payment['communication'];
    $lines[] = '';
    $lines[] = 'Pay with a QR code: ' . (string) $links['payment'];
} elseif ($isFree) {
    $lines[] = '';
    $lines[] = 'Participation is free: no payment will be requested from you.';
}

$lines[] = '';
$lines[] = 'Your personal area (keep this link):';
$lines[] = (string) $links['space'];
$lines[] = '';
$lines[] = 'Any question? ' . (string) $contact['email'] . ' - ' . (string) $contact['phone'];
$lines[] = '';
$lines[] = 'The CRA-W training team';
$lines[] = 'Walloon Agricultural Research Centre';
$lines[] = 'Rue de Liroux 9, 5030 Gembloux, Belgium';

echo implode("\n", $lines);

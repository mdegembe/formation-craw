<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Hello ' . (string) $registration['first_name'] . ',';
$lines[] = '';

if ($isFinal) {
    $lines[] = 'FINAL REMINDER: the payment deadline has passed.';
    $lines[] = 'Without payment in the coming days, your place will be released';
    $lines[] = 'to people on the waiting list.';
} else {
    $lines[] = 'Unless we are mistaken, we have not yet received payment';
    $lines[] = 'for your registration for the training "' . (string) $training['title'] . '".';
    $lines[] = '';
    $lines[] = 'If you made the transfer in the last few days, this message may';
    $lines[] = 'have crossed with its processing: please disregard it in that case.';
}

$lines[] = '';
$lines[] = '--- BANK TRANSFER DETAILS ---';
$lines[] = 'Beneficiary : ' . (string) $payment['beneficiary'];
$lines[] = 'IBAN        : ' . (string) $payment['iban'];
$lines[] = 'BIC         : ' . (string) $payment['bic'];
$lines[] = 'Amount      : ' . (string) $payment['amount'];
$lines[] = 'Structured reference : ' . (string) $payment['communication'];
$lines[] = '';
$lines[] = 'Pay with a QR code: ' . (string) $links['payment'];
$lines[] = '';
$lines[] = 'Your registration reference: ' . (string) $registration['reference'];
$lines[] = '';
$lines[] = 'Any difficulty? ' . (string) $contact['email'] . ' - ' . (string) $contact['phone'];
$lines[] = '';
$lines[] = 'The CRA-W training team';

echo implode("\n", $lines);

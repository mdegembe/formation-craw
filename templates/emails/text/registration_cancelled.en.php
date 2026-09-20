<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Hello ' . (string) $registration['first_name'] . ',';
$lines[] = '';
$lines[] = 'We confirm the cancellation of your registration';
$lines[] = '(reference ' . (string) $registration['reference'] . ') for the training';
$lines[] = '"' . (string) $training['title'] . '".';

if (($reason ?? null) !== null && $reason !== '') {
    $lines[] = '';
    $lines[] = 'Reason: ' . (string) $reason;
}

$lines[] = '';
$lines[] = 'If a payment had already been made, any refund will be handled';
$lines[] = 'according to the cancellation terms communicated at registration.';
$lines[] = '';
$lines[] = 'Does this cancellation seem wrong to you? Write to us at ' . (string) $contact['email'];
$lines[] = 'stating your reference.';
$lines[] = '';
$lines[] = 'The CRA-W training team';

echo implode("\n", $lines);

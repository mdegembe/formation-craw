<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Hello ' . (string) $registration['first_name'] . ',';
$lines[] = '';
$lines[] = 'We have received your payment: your registration is now';
$lines[] = 'definitively confirmed.';
$lines[] = '';
$lines[] = 'Reference    : ' . (string) $registration['reference'];
$lines[] = 'Amount paid  : ' . (string) $payment['amount'];
$lines[] = '';
$lines[] = '--- SEE YOU THERE ---';
$lines[] = 'From ' . en_date((string) $training['starts_on'], true);
$lines[] = 'to ' . en_date((string) $training['ends_on'], true) . ', from 9:00am to 5:00pm.';
$lines[] = (string) $training['location_name'];
$lines[] = (string) $training['location_address'];
$lines[] = 'Check-in from 8:30am on the first day.';
$lines[] = '';
$lines[] = 'Breaks and lunches are included. The dietary requirements you reported';
$lines[] = 'have been passed on to the caterer.';
$lines[] = '';
$lines[] = 'Your area: ' . (string) $links['space'];
$lines[] = '';
$lines[] = 'See you soon,';
$lines[] = 'The CRA-W training team';

echo implode("\n", $lines);

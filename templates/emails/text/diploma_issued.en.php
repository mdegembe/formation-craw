<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Hello ' . (string) $registration['first_name'] . ',';
$lines[] = '';
$lines[] = 'You attended the training "' . (string) $training['title'] . '",';
$lines[] = 'from ' . en_date((string) $training['starts_on']) . ' to ' . en_date((string) $training['ends_on']) . '.';
$lines[] = '';
$lines[] = 'Your certificate of attendance is attached to this message, as a PDF.';
$lines[] = '';
$lines[] = 'Certificate number : ' . (string) $diploma['number'];
$lines[] = 'Attendance rate    : ' . \Craw\Domain\AttendanceCalculator::format((float) $diploma['attendance_rate']);
$lines[] = 'Date issued        : ' . en_date((string) $diploma['generated_at']);
$lines[] = '';
$lines[] = 'Online verification of authenticity:';
$lines[] = (string) $verificationUrl;
$lines[] = '';
$lines[] = 'Thank you for your participation.';
$lines[] = '';
$lines[] = 'The CRA-W training team';

echo implode("\n", $lines);

<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Bonjour ' . (string) $registration['first_name'] . ',';
$lines[] = '';
$lines[] = 'Vous avez suivi la formation "' . (string) $training['title'] . '",';
$lines[] = 'du ' . fr_date((string) $training['starts_on']) . ' au ' . fr_date((string) $training['ends_on']) . '.';
$lines[] = '';
$lines[] = 'Votre diplome de participation est joint a ce message, au format PDF.';
$lines[] = '';
$lines[] = 'Numero du diplome  : ' . (string) $diploma['number'];
$lines[] = 'Taux de presence   : ' . \Craw\Domain\AttendanceCalculator::format((float) $diploma['attendance_rate']);
$lines[] = 'Date de delivrance : ' . fr_date((string) $diploma['generated_at']);
$lines[] = '';
$lines[] = 'Verification en ligne de l\'authenticite :';
$lines[] = (string) $verificationUrl;
$lines[] = '';
$lines[] = 'Merci de votre participation.';
$lines[] = '';
$lines[] = 'L\'equipe formation du CRA-W';

echo implode("\n", $lines);

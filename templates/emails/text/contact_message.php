<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Message envoye depuis le formulaire de contact du site de la formation.';
$lines[] = '';
$lines[] = 'Expediteur : ' . $senderName;
$lines[] = 'E-mail     : ' . $senderEmail;
$lines[] = 'Objet      : ' . $messageSubject;
$lines[] = '';
$lines[] = '--- MESSAGE ---';
$lines[] = $messageBody;
$lines[] = '';
$lines[] = 'Pour repondre, ecrivez directement a ' . $senderEmail;

echo implode("\n", $lines);

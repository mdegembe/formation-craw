<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Bonjour ' . (string) $registration['first_name'] . ',';
$lines[] = '';

if ($isFinal) {
    $lines[] = 'DERNIER RAPPEL : la date limite de paiement est depassee.';
    $lines[] = 'Sans reglement dans les prochains jours, votre place sera liberee';
    $lines[] = 'au profit des personnes inscrites en liste d\'attente.';
} else {
    $lines[] = 'Sauf erreur de notre part, nous n\'avons pas encore recu le paiement';
    $lines[] = 'de votre inscription a la formation "' . (string) $training['title'] . '".';
    $lines[] = '';
    $lines[] = 'Si vous avez effectue le virement ces derniers jours, ce message peut';
    $lines[] = 'se croiser avec son traitement : merci de ne pas en tenir compte.';
}

$lines[] = '';
$lines[] = '--- COORDONNEES DU VIREMENT ---';
$lines[] = 'Beneficiaire : ' . (string) $payment['beneficiary'];
$lines[] = 'IBAN         : ' . (string) $payment['iban'];
$lines[] = 'BIC          : ' . (string) $payment['bic'];
$lines[] = 'Montant      : ' . (string) $payment['amount'];
$lines[] = 'Communication structuree : ' . (string) $payment['communication'];
$lines[] = '';
$lines[] = 'Payer avec un QR code : ' . (string) $links['payment'];
$lines[] = '';
$lines[] = 'Reference de votre inscription : ' . (string) $registration['reference'];
$lines[] = '';
$lines[] = 'Une difficulte ? ' . (string) $contact['email'] . ' - ' . (string) $contact['phone'];
$lines[] = '';
$lines[] = 'L\'equipe formation du CRA-W';

echo implode("\n", $lines);

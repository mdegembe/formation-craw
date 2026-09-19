<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Bonjour ' . (string) $registration['first_name'] . ',';
$lines[] = '';
$lines[] = 'Nous vous confirmons l\'annulation de votre inscription';
$lines[] = '(reference ' . (string) $registration['reference'] . ') a la formation';
$lines[] = '"' . (string) $training['title'] . '".';

if (($reason ?? null) !== null && $reason !== '') {
    $lines[] = '';
    $lines[] = 'Motif : ' . (string) $reason;
}

$lines[] = '';
$lines[] = 'Si un paiement avait deja ete effectue, son eventuel remboursement sera';
$lines[] = 'traite selon les conditions d\'annulation communiquees a l\'inscription.';
$lines[] = '';
$lines[] = 'Cette annulation vous semble erronee ? Ecrivez-nous a ' . (string) $contact['email'];
$lines[] = 'en rappelant votre reference.';
$lines[] = '';
$lines[] = 'L\'equipe formation du CRA-W';

echo implode("\n", $lines);

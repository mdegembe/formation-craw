<?php

declare(strict_types=1);

$lines = [];
$lines[] = 'Bonjour ' . (string) $registration['first_name'] . ',';
$lines[] = '';
$lines[] = 'Nous avons bien recu votre paiement : votre inscription est desormais';
$lines[] = 'definitivement confirmee.';
$lines[] = '';
$lines[] = 'Reference     : ' . (string) $registration['reference'];
$lines[] = 'Montant regle : ' . (string) $payment['amount'];
$lines[] = '';
$lines[] = '--- RENDEZ-VOUS ---';
$lines[] = 'Du ' . fr_date((string) $training['starts_on'], true);
$lines[] = 'au ' . fr_date((string) $training['ends_on'], true) . ', de 9h00 a 17h00.';
$lines[] = (string) $training['location_name'];
$lines[] = (string) $training['location_address'];
$lines[] = 'Accueil des 8h30 le premier jour.';
$lines[] = '';
$lines[] = 'Les pauses et repas de midi sont compris. Vos contraintes alimentaires';
$lines[] = 'ont ete transmises au traiteur.';
$lines[] = '';
$lines[] = 'Votre espace : ' . (string) $links['space'];
$lines[] = '';
$lines[] = 'A bientot,';
$lines[] = 'L\'equipe formation du CRA-W';

echo implode("\n", $lines);

<?php
define('_ECRIRE_INC_VERSION', 1);
require dirname(__DIR__) . '/inc/association_dons_regles.php';
function tester($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
foreach (array('40' => 4000, '40,50' => 4050, '0.01' => 1, '100.2' => 10020) as $saisie => $centimes) {
	tester(association_dons_centimes($saisie) === $centimes, 'Conversion monétaire incorrecte.');
}
foreach (array('-1', '1e2', '1.001', '<script>', '', '100000000') as $saisie) {
	tester(association_dons_centimes($saisie) === null, 'Montant invalide accepté.');
}
tester(association_dons_bornes_periode('2026-11-12') === array('annee' => 2027, 'date_debut' => '2026-01-01', 'date_fin' => '2027-12-31'), 'Première campagne incorrecte.');
tester(association_dons_bornes_periode('2028-02-29')['date_fin'] === '2028-12-31', 'Année bissextile refusée.');
tester(association_dons_bornes_periode('2027-02-29') === array(), 'Date invalide acceptée.');
$periodes = array(array('statut' => 'a_jour', 'date_debut' => '2026-01-01', 'date_fin' => '2027-12-31'));
tester(association_dons_statut(array(), $periodes, '2027-12-31') === 'a_jour', 'Fin de période non inclusive.');
tester(association_dons_statut(array(), $periodes, '2028-01-01') === 'echu', 'Expiration incorrecte.');
tester(association_dons_statut(array('archive' => 'oui'), $periodes, '2027-01-01') === 'ancien', 'Archivage ignoré.');
tester(association_dons_statut(array(), array(), '2027-01-01') === 'attente', 'Donateur sans don traité comme payé.');
$periodes[] = array('statut' => 'attente', 'date_debut' => '2028-01-01', 'date_fin' => '2028-12-31');
tester(association_dons_statut(array(), $periodes, '2027-12-01') === 'a_jour', 'Le renouvellement impayé masque une période payée.');
tester(association_dons_statut(array(), $periodes, '2028-01-01') === 'attente', 'Renouvellement en attente ignoré.');
$identite = array('nom' => 'RECETTE', 'email' => 'ami@example.invalid', 'type_donateur' => 'particulier');
tester(!association_dons_identite_verifier($identite), 'Identité valide refusée.');
$identite['type_donateur'] = 'organisation';
tester(isset(association_dons_identite_verifier($identite)['raison_sociale']), 'Organisation sans nom acceptée.');
$identite['raison_sociale'] = 'Organisation synthétique';
tester(!association_dons_identite_verifier($identite), 'Organisation valide refusée.');
echo "OK : montants, dates, campagne 2026–2027, statuts et identités.\n";

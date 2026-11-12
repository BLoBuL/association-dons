<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

/** Montant en centimes ; aucun arrondi silencieux d'une saisie invalide. */
function association_dons_centimes($valeur) {
	$valeur = str_replace(',', '.', trim((string)$valeur));
	if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $valeur)) { return null; }
	$parties = explode('.', $valeur, 2);
	return (int)$parties[0] * 100 + (int)str_pad($parties[1] ?? '', 2, '0');
}

/** La première campagne couvre exceptionnellement 2026 et 2027. */
function association_dons_bornes_periode($date) {
	$date_objet = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$date);
	if (!$date_objet || $date_objet->format('Y-m-d') !== $date) { return array(); }
	$annee = (int)$date_objet->format('Y');
	$annee_fin = $annee === 2026 ? 2027 : $annee;
	return array('annee' => $annee_fin, 'date_debut' => $annee . '-01-01', 'date_fin' => $annee_fin . '-12-31');
}

/** Statut administratif calculé, sans création de compte ni écriture financière. */
function association_dons_statut($donateur, $periodes, $date) {
	if (($donateur['archive'] ?? 'non') === 'oui') { return 'ancien'; }
	$attente = false;
	foreach ($periodes as $periode) {
		if (($periode['statut'] ?? '') === 'a_jour' && $periode['date_debut'] <= $date && $periode['date_fin'] >= $date) { return 'a_jour'; }
		if (($periode['statut'] ?? '') === 'attente' && $periode['date_fin'] >= $date) { $attente = true; }
	}
	return $attente || !$periodes ? 'attente' : 'echu';
}

function association_dons_identite_verifier($donnees) {
	$erreurs = array();
	foreach (array('nom', 'prenom', 'raison_sociale', 'email') as $champ) {
		if (mb_strlen(trim((string)($donnees[$champ] ?? ''))) > 255) { $erreurs[$champ] = 'trop_long'; }
	}
	if (trim((string)($donnees['nom'] ?? '')) === '') { $erreurs['nom'] = 'obligatoire'; }
	if (!filter_var($donnees['email'] ?? '', FILTER_VALIDATE_EMAIL)) { $erreurs['email'] = 'email'; }
	if (!in_array($donnees['type_donateur'] ?? '', array('particulier', 'organisation'), true)) { $erreurs['type_donateur'] = 'invalide'; }
	if (($donnees['type_donateur'] ?? '') === 'organisation' && trim((string)($donnees['raison_sociale'] ?? '')) === '') { $erreurs['raison_sociale'] = 'obligatoire'; }
	if (!in_array($donnees['publication_nom'] ?? 'non', array('oui', 'non'), true)) { $erreurs['publication_nom'] = 'invalide'; }
	return $erreurs;
}

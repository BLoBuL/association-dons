<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');

function formulaires_editer_association_donateur_charger_dist($id_donateur = 0) {
	if (!autoriser('gerer', 'associationdons')) { return false; }
	$donateur = (int)$id_donateur ? association_dons_donateur($id_donateur) : array();
	if ((int)$id_donateur && !$donateur) { return false; }
	return array_merge(array('type_donateur' => 'particulier', 'nom' => '', 'prenom' => '', 'raison_sociale' => '', 'email' => '', 'id_organisation' => 0, 'publication_nom' => 'non', 'archive' => 'non', '_organisations' => test_plugin_actif('contacts')), $donateur);
}

function formulaires_editer_association_donateur_verifier_dist($id_donateur = 0) {
	if (!autoriser('gerer', 'associationdons')) { return array('message_erreur' => _T('info_acces_interdit')); }
	$donnees = array();
	foreach (array('type_donateur', 'nom', 'prenom', 'raison_sociale', 'email', 'publication_nom') as $champ) { $donnees[$champ] = _request($champ); }
	$erreurs = association_dons_identite_verifier($donnees);
	foreach ($erreurs as &$erreur) { $erreur = _T('association_dons:erreur_champ'); }
	return $erreurs;
}

function formulaires_editer_association_donateur_traiter_dist($id_donateur = 0) {
	$donnees = array();
	foreach (array('type_donateur', 'nom', 'prenom', 'raison_sociale', 'email', 'id_organisation', 'publication_nom', 'archive') as $champ) { $donnees[$champ] = _request($champ); }
	$resultat = association_dons_enregistrer_donateur($id_donateur, $donnees);
	if (empty($resultat['id_donateur'])) { return array('message_erreur' => _T('association_dons:erreur_enregistrement')); }
	return array('message_ok' => _T('association_dons:enregistrement_ok'), 'redirect' => generer_url_ecrire('association_donateur', 'id_donateur=' . $resultat['id_donateur']));
}

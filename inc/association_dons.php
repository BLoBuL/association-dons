<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons_regles');
include_spip('inc/config');
include_spip('inc/autoriser');

function association_dons_configuration() {
	return array_merge(array(
		'montants' => '40;100', 'minimum' => '40', 'devise' => 'EUR',
		'virement' => 'non', 'fondation' => 'non', 'url_fondation' => '',
		'instructions_virement' => '', 'url_confidentialite' => '',
		'journal_dons' => '',
		'imputation_dons' => (string)($GLOBALS['association_metas']['pc_dons'] ?? ''),
		'preavis_renouvellement' => '60', 'delai_rappel' => '14',
		'notifications' => 'non', 'email_secretariat' => '',
	), (array)lire_config('association_dons', array()));
}

function association_dons_donateur($id) {
	return sql_fetsel('*', 'spip_asso_donateurs', 'id_donateur=' . (int)$id) ?: array();
}

function association_dons_periodes($id) {
	return sql_allfetsel('*', 'spip_asso_donateurs_periodes', 'id_donateur=' . (int)$id, '', 'annee DESC');
}

/** Édition privée : les appels publics disposeront d'une entrée séparée. */
function association_dons_enregistrer_donateur($id, $donnees) {
	if (!autoriser('gerer', 'associationdons')) { return array('erreur' => 'interdit'); }
	$erreurs = association_dons_identite_verifier($donnees);
	$id = (int)$id;
	if ($id && !association_dons_donateur($id)) { $erreurs['id_donateur'] = 'absent'; }
	$id_organisation = (int)($donnees['id_organisation'] ?? 0);
	if ($id_organisation && (!test_plugin_actif('contacts') || !sql_countsel('spip_organisations', 'id_organisation=' . $id_organisation))) { $erreurs['id_organisation'] = 'absent'; }
	if ($erreurs) { return array('erreurs' => $erreurs); }
	$set = array();
	foreach (array('type_donateur', 'nom', 'prenom', 'raison_sociale', 'email') as $champ) { $set[$champ] = trim((string)($donnees[$champ] ?? '')); }
	$set['email'] = mb_strtolower($set['email']);
	$set['id_organisation'] = $id_organisation;
	$set['publication_nom'] = $donnees['publication_nom'] ?? 'non';
	$set['archive'] = ($donnees['archive'] ?? 'non') === 'oui' ? 'oui' : 'non';
	if ($id) {
		if (sql_updateq('spip_asso_donateurs', $set, 'id_donateur=' . $id) === false) { return array('erreur' => 'enregistrement'); }
	} else {
		$set['date_creation'] = date('Y-m-d H:i:s');
		$id = (int)sql_insertq('spip_asso_donateurs', $set);
	}
	if (!$id) { return array('erreur' => 'enregistrement'); }
	pipeline('association_dons_evenement', array('args' => array('evenement' => 'donateur_modifie', 'id_donateur' => $id), 'data' => array()));
	return array('id_donateur' => $id);
}

function association_dons_liste($statut = '', $recherche = '') {
	if (!autoriser('gerer', 'associationdons')) { return array(); }
	$where = array();
	if ($recherche !== '') {
		$mot = sql_quote('%' . str_replace(array('%', '_'), array('\\%', '\\_'), mb_substr($recherche, 0, 100)) . '%');
		$where[] = '(nom LIKE ' . $mot . ' OR prenom LIKE ' . $mot . ' OR email LIKE ' . $mot . ' OR raison_sociale LIKE ' . $mot . ')';
	}
	$resultat = array();
	foreach (sql_allfetsel('*', 'spip_asso_donateurs', $where, '', 'nom,prenom,id_donateur') as $donateur) {
		$donateur['statut_calcule'] = association_dons_statut($donateur, association_dons_periodes($donateur['id_donateur']), date('Y-m-d'));
		if ($statut === '' || $statut === $donateur['statut_calcule']) { $resultat[] = $donateur; }
	}
	return $resultat;
}

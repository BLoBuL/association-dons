<?php
/** À exécuter dans SPIP-CLI sur DEV uniquement ; aucune donnée réelle utilisée. */
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('inc/association_dons');
$resultats = array();
$ancienne_identite = $GLOBALS['visiteur_session'] ?? array();
$GLOBALS['visiteur_session'] = array();
$resultats['visiteur_refuse'] = association_dons_enregistrer_donateur(0, array()) === array('erreur' => 'interdit');
$qui = sql_fetsel('id_auteur,nom,statut,webmestre', 'spip_auteurs', "login='blobul_ai'");
if (!$qui || $qui['statut'] !== '0minirezo') { throw new RuntimeException('Compte technique non établi.'); }
$GLOBALS['visiteur_session'] = $qui;
$resultats['admin_autorise'] = autoriser('gerer', 'associationdons');
include_spip('inc/boutons');
$menus = pipeline('ajouter_menus', array());
$resultats['menu_association'] = isset($menus['association']->sousmenu['association_donateurs']);
$donnees = array('nom' => 'RECETTE AMIS API', 'prenom' => 'Synthétique', 'email' => 'recette-amis@example.invalid', 'type_donateur' => 'organisation', 'raison_sociale' => 'Organisation synthétique');
$creation = association_dons_enregistrer_donateur(0, $donnees);
$id = $creation['id_donateur'] ?? 0;
try {
	$resultats['creation_admin'] = $id > 0;
	$resultats['sans_organisation'] = sql_getfetsel('id_organisation', 'spip_asso_donateurs', 'id_donateur=' . (int)$id) == 0;
	$resultats['recherche'] = count(association_dons_liste('attente', 'RECETTE AMIS API')) === 1;
	$invalide = $donnees; $invalide['email'] = 'invalide';
	$resultats['email_invalide_refuse'] = !empty(association_dons_enregistrer_donateur($id, $invalide)['erreurs']['email']);
	$donnees['archive'] = 'oui'; association_dons_enregistrer_donateur($id, $donnees);
	$resultats['archivage'] = count(association_dons_liste('ancien', 'RECETTE AMIS API')) === 1;
	include_spip('inc/filtres'); include_spip('public/assembler');
	foreach (array('association_donateurs', 'association_donateur', 'configurer_association_dons') as $page) {
		$html = recuperer_fond('prive/squelettes/contenu/' . $page, array('id_donateur' => $id, 'recherche_donateurs' => 'RECETTE AMIS API'));
		$resultats['rendu_' . $page] = strlen($html) > 300 && strpos($html, 'erreur squelette') === false;
	}
	$resultats['erreurs_squelettes'] = count($GLOBALS['tableau_des_erreurs'] ?? array());
} finally {
	if ($id) { sql_delete('spip_asso_donateurs', 'id_donateur=' . (int)$id); }
	$GLOBALS['visiteur_session'] = $ancienne_identite;
}
echo json_encode($resultats, JSON_UNESCAPED_UNICODE);

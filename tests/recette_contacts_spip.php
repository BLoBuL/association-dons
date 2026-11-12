<?php
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('inc/association_dons'); include_spip('public/assembler'); include_spip('inc/filtres');
if (!test_plugin_actif('contacts')) { throw new RuntimeException('Contacts & Organisations doit être actif pour cette variante.'); }
$identite_avant = $GLOBALS['visiteur_session'] ?? array();
$GLOBALS['visiteur_session'] = sql_fetsel('id_auteur,nom,statut,webmestre', 'spip_auteurs', "login='blobul_ai'");
include_spip('action/editer_organisation');
$id_organisation = (int)organisation_inserer(null, array('nom' => 'RECETTE ORGANISATION AMIS'));
$id_donateur = 0; $r = array();
try {
	$donnees = array('type_donateur' => 'organisation', 'nom' => 'RECETTE ORG <img src=x onerror=alert(1)>', 'email' => 'recette-organisation@example.invalid', 'raison_sociale' => 'Organisation synthétique', 'id_organisation' => $id_organisation);
	$creation = association_dons_enregistrer_donateur(0, $donnees); $id_donateur = (int)($creation['id_donateur'] ?? 0);
	$r['rattachement_organisation'] = $id_donateur > 0 && association_dons_donateur($id_donateur)['id_organisation'] == $id_organisation;
	$html = recuperer_fond('prive/squelettes/inclure/association_dons_organisation_fiche', array('id_organisation' => $id_organisation));
	$r['fiche_organisation'] = strpos($html, 'RECETTE ORG') !== false;
	$r['identite_echappee'] = strpos($html, '<img') === false;
	$flux = pipeline('afficher_contenu_objet', array('args' => array('type' => 'organisation', 'id_objet' => $id_organisation), 'data' => 'RECETTE'));
	$r['pipeline_organisation'] = strpos($flux, 'RECETTE ORG') !== false;
	$donnees['id_organisation'] = 2147483647;
	$r['organisation_inexistante_refusee'] = !empty(association_dons_enregistrer_donateur($id_donateur, $donnees)['erreurs']['id_organisation']);
	$formulaire = recuperer_fond('prive/squelettes/contenu/association_donateur', array('id_donateur' => $id_donateur));
	$r['selecteur_organisation'] = strpos($formulaire, 'name="id_organisation"') !== false;
	$r['erreurs_squelettes'] = count($GLOBALS['tableau_des_erreurs'] ?? array());
} finally {
	if ($id_donateur) { sql_delete('spip_asso_donateurs', 'id_donateur=' . $id_donateur); }
	if ($id_organisation) { sql_delete('spip_organisations', 'id_organisation=' . $id_organisation . " AND nom='RECETTE ORGANISATION AMIS'"); }
	$GLOBALS['visiteur_session'] = $identite_avant;
}
echo json_encode($r, JSON_UNESCAPED_UNICODE);

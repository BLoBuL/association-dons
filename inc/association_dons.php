<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons_regles');
include_spip('inc/config');
include_spip('inc/autoriser');

function association_dons_configuration() {
	$config = array_merge(array(
		'prestataires' => null, 'montants' => '40;100', 'minimum' => '40', 'devise' => 'EUR',
		'virement' => 'non', 'fondation' => 'non', 'url_fondation' => '',
		'instructions_virement' => '', 'url_confidentialite' => '', 'page_confidentialite' => '',
		'journal_dons' => '',
		'imputation_dons' => (string)($GLOBALS['association_metas']['pc_dons'] ?? ''),
		'preavis_renouvellement' => '60', 'delai_rappel' => '14',
		'notifications' => 'non', 'email_secretariat' => '', 'mode_recette' => 'non',
	), (array)lire_config('association_dons', array()));
	if ($config['page_confidentialite'] !== '') {
		$id_article = sql_getfetsel('id_article', 'spip_articles', array('id_rubrique=-1', "statut='publie'", sql_quote($config['page_confidentialite']).'=page'));
		$config['url_confidentialite'] = $id_article ? generer_url_entite($id_article, 'article', '', '', true) : '';
	}
	if ($config['mode_recette'] === 'oui') { $config['notifications'] = 'non'; }
	return $config;
}

function association_dons_donateur($id) {
	return sql_fetsel('*', 'spip_asso_donateurs', 'id_donateur=' . (int)$id) ?: array();
}

function association_dons_periodes($id) {
	return sql_allfetsel('*', 'spip_asso_donateurs_periodes', 'id_donateur=' . (int)$id, '', 'annee DESC');
}

/** Ã‰dition privÃ©e : les appels publics disposeront d'une entrÃ©e sÃ©parÃ©e. */
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

/** Configurations actives Bank, identifiÃ©es par leur clÃ© stable (plusieurs comptes possibles). */
function association_dons_prestataires_disponibles() {
 include_spip('inc/bank');
 return array_filter(bank_lister_configs('acte'), function($config) { return !empty($config['actif']); });
}
function association_dons_prestataires_selectionnes() {
 $tous=association_dons_prestataires_disponibles();
 $choix=association_dons_configuration()['prestataires'];
 return $choix===null ? $tous : array_intersect_key($tous,array_flip((array)$choix));
}
function association_dons_prestataires_labels() {
 include_spip('inc/filtres');
 $data=array();
 foreach(association_dons_prestataires_disponibles() as $cle=>$config) {
  $nom=_T('bank:label_presta_'.$config['presta']);
  $label=trim((string)($config['label']??''));
  $data[$cle]=entites_html($nom.($label!==''?' â€” '.$label:'').' ['.$cle.']'.(in_array($config['mode_test']??null,array(true,1,'1','test'),true)?' â€” '._T('association_dons:presta_mode_test'):''));
 }
 return $data;
}

function association_dons_config_sections() {
 $sections = array(
  'recette' => array('mode_recette'),
  'montants' => array('montants','minimum','devise'),
  'paiements' => array('prestataires'),
  'comptabilite' => array('journal_dons','imputation_dons'),
  'confidentialite' => array('page_confidentialite'),
  'notifications' => array('notifications','email_secretariat','delai_rappel','preavis_renouvellement'),
 );
 if (!defined('_DIR_PLUGIN_ASSOCIATION') && !defined('_DIR_PLUGIN_ASSOCIATION_COMPTA')) { unset($sections['comptabilite']); }
 return $sections;
}

/** Pages uniques publiées, selon le même périmètre que la configuration Association. */
function association_dons_pages_uniques() {
 include_spip('inc/filtres');
 $pages=array();
 foreach (sql_allfetsel('page,titre','spip_articles',"id_rubrique=-1 AND statut='publie' AND page!=''",'', 'titre') as $article) {
  $pages[$article['page']]=supprimer_numero($article['titre']);
 }
 return $pages;
}

/** Liste de délais en jours ; une ancienne valeur unique reste valable. */
function association_dons_delais($valeur) {
 $elements=preg_split('/[;,\s]+/',trim((string)$valeur),-1,PREG_SPLIT_NO_EMPTY);
 if (!$elements) { return array(); }
 $delais=array();
 foreach($elements as $element) {
  if (!ctype_digit($element) || (int)$element<1 || (int)$element>365) { return array(); }
  $delais[]=(int)$element;
 }
 $delais=array_values(array_unique($delais));sort($delais);return $delais;
}

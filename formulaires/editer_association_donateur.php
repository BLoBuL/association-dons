<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');

function formulaires_editer_association_donateur_charger_dist($id_donateur = 0) {
	if (!autoriser('gerer', 'associationdons')) { return false; }
	$donateur = (int)$id_donateur ? association_dons_donateur($id_donateur) : array();
	if ((int)$id_donateur && !$donateur) { return false; }
	return array_merge(array('type_donateur' => 'particulier', 'nom' => '', 'prenom' => '', 'raison_sociale' => '', 'email' => '', 'id_organisation' => 0, 'publication_nom' => 'non', 'archive' => 'non', '_organisations' => test_plugin_actif('contacts'), 'editable'=>true, '_saisies' => formulaires_editer_association_donateur_saisies_dist($id_donateur)), $donateur);
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

function formulaires_editer_association_donateur_saisies_dist($id_donateur=0) {
 $saisies=array();
 $saisies[]=array('saisie'=>'selection','options'=>array('nom'=>'type_donateur','label'=>_T('association_dons:type_donateur'),'data'=>array('particulier'=>_T('association_dons:particulier'),'organisation'=>_T('association_dons:organisation')),'cacher_option_intro'=>'oui'));
 foreach(array('nom','prenom','raison_sociale','email') as $champ) { $saisies[]=array('saisie'=>'input','options'=>array('nom'=>$champ,'label'=>_T('association_dons:'.$champ),'type'=>$champ==='email'?'email':'text','maxlength'=>255,'obligatoire'=>in_array($champ,array('nom','email'),true)?'oui':'')); }
 if(test_plugin_actif('contacts')) {
  $data=array();foreach(sql_allfetsel('id_organisation,nom','spip_organisations','','','nom') as $org) { $data[$org['id_organisation']]=$org['nom']; }
  $saisies[]=array('saisie'=>'selection','options'=>array('nom'=>'id_organisation','label'=>_T('association_dons:organisation_liee'),'data'=>$data));
 }
 $saisies[]=array('saisie'=>'radio','options'=>array('nom'=>'publication_nom','label'=>_T('association_dons:publication_nom'),'data'=>array('oui'=>_T('item_oui'),'non'=>_T('item_non'))));
 $saisies[]=array('saisie'=>'case','options'=>array('nom'=>'archive','label_case'=>_T('association_dons:archiver'),'valeur_oui'=>'oui','valeur_non'=>'non'));
 return $saisies;
}

<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');

function association_dons_config_sections() {
 return array(
  'recette' => array('mode_recette'),
  'montants' => array('montants','minimum','devise'),
  'paiements' => array('prestataires'),
  'comptabilite' => array('journal_dons','imputation_dons'),
  'confidentialite' => array('url_confidentialite'),
  'notifications' => array('notifications','email_secretariat','delai_rappel','preavis_renouvellement'),
 );
}
function association_dons_config_valeurs() {
 $config=association_dons_configuration();
 $config['notifications']=lire_config('association_dons/notifications','non');
 return $config;
}
function association_dons_config_champs($section) {
 $sections=association_dons_config_sections();
 return $section==='' ? array_merge(...array_values($sections)) : ($sections[$section] ?? array());
}
function formulaires_configurer_association_dons_saisies_dist($section='') {
 include_spip('inc/bank');
 $devises=array(); foreach(bank_lister_devises() as $code=>$devise) { if((int)$devise['fraction']===2) { $devises[$code]=$code; } }
 $resultat=array();
 foreach(association_dons_config_sections() as $groupe=>$champs) {
  if($section!=='' && $section!==$groupe) { continue; }
  $saisies=array();
  foreach($champs as $champ) {
   $options=array('nom'=>$champ,'label'=>_T('association_dons:'.$champ),'explication'=>_T('association_dons:aide_'.$champ));
   $type='input';
   if($champ==='prestataires') {
    $type='checkbox'; $options['data']=association_dons_prestataires_labels();
   } elseif(in_array($champ,array('mode_recette','notifications','virement','fondation'),true)) {
    $type='case'; $options['label_case']=$options['label']; unset($options['label']); $options['valeur_oui']='oui'; $options['valeur_non']='non';
   } elseif($champ==='devise') {
    $type='selection'; $options['data']=$devises; $options['cacher_option_intro']='oui';
   } elseif($champ==='instructions_virement') { $type='textarea'; $options['rows']=5;
   } elseif(in_array($champ,array('minimum','delai_rappel','preavis_renouvellement'),true)) {
    $options['type']='number'; $options['min']=$champ==='minimum'?'0.01':1; $options['step']=$champ==='minimum'?'0.01':1;
    if($champ!=='minimum') { $options['max']=365; }
   } elseif(strpos($champ,'url_')===0) { $options['type']='url';
   } elseif($champ==='email_secretariat') { $options['type']='email'; }
   $saisies[]=array('saisie'=>$type,'options'=>$options);
  }
  $resultat[]=array('saisie'=>'fieldset','options'=>array('nom'=>'config_'.$groupe,'label'=>_T('association_dons:config_'.$groupe)), 'saisies'=>$saisies);
 }
 return $resultat;
}
function formulaires_configurer_association_dons_charger_dist($section='') {
 if(!autoriser('gerer','associationdons') || !association_dons_config_champs($section)) { return false; }
 $config=association_dons_config_valeurs();
 $config['_section']=$section;
 if ($config['prestataires']===null) { $config['prestataires']=array_keys(association_dons_prestataires_disponibles()); }
 $config['_comptabilite']=!empty($GLOBALS['association_metas']['comptes']);
 $config['_saisies']=formulaires_configurer_association_dons_saisies_dist($section);
 return $config;
}
function association_dons_config_soumission($section) {
 $config=association_dons_config_valeurs();
 foreach(association_dons_config_champs($section) as $champ) {
  $valeur=_request($champ);
  if($champ==='prestataires') { $config[$champ]=is_array($valeur)?array_values(array_unique(array_filter($valeur,'is_string'))):array(); }
  elseif(in_array($champ,array('mode_recette','notifications','virement','fondation'),true)) { $config[$champ]=$valeur==='oui'?'oui':'non'; }
  else { $config[$champ]=is_scalar($valeur)?trim((string)$valeur):''; }
 }
 return $config;
}
function association_dons_config_erreurs($valeurs) {
	$erreurs = array();
	if ($valeurs['prestataires']!==null && (!is_array($valeurs['prestataires']) || !$valeurs['prestataires'] || array_diff($valeurs['prestataires'],array_keys(association_dons_prestataires_disponibles())))) { $erreurs['prestataires']=_T('association_dons:erreur_prestataires'); }
	$minimum = association_dons_centimes($valeurs['minimum']);
	$vus = array();
	foreach (explode(';', (string)$valeurs['montants']) as $montant) {
		$centimes = association_dons_centimes($montant);
		if (!$centimes || isset($vus[$centimes]) || ($minimum && $centimes < $minimum)) { $erreurs['montants'] = _T('association_dons:erreur_champ'); }
		$vus[$centimes] = true;
	}
	if (!association_dons_centimes($valeurs['minimum'])) { $erreurs['minimum'] = _T('association_dons:erreur_champ'); }
	include_spip('inc/bank');
	$devises = bank_lister_devises();
	$devise = (string)$valeurs['devise'];
	if (!isset($devises[$devise]) || (int)$devises[$devise]['fraction'] !== 2) { $erreurs['devise'] = _T('association_dons:erreur_champ'); }
	foreach (array('url_fondation', 'url_confidentialite') as $champ) {
		$url = trim((string)$valeurs[$champ]);
		if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https')) { $erreurs[$champ] = _T('association_dons:erreur_champ'); }
	}
	if ($valeurs['fondation'] === 'oui' && !$valeurs['url_fondation']) { $erreurs['url_fondation'] = _T('association_dons:erreur_champ'); }
	if ($valeurs['virement'] === 'oui' && !trim((string)$valeurs['instructions_virement'])) { $erreurs['instructions_virement'] = _T('association_dons:erreur_champ'); }
	if (($valeurs['notifications'] === 'oui' || $valeurs['email_secretariat']) && !filter_var($valeurs['email_secretariat'], FILTER_VALIDATE_EMAIL)) { $erreurs['email_secretariat'] = _T('association_dons:erreur_champ'); }
	foreach (array('preavis_renouvellement', 'delai_rappel') as $champ) {
		if (!ctype_digit((string)$valeurs[$champ]) || (int)$valeurs[$champ] < 1 || (int)$valeurs[$champ] > 365) { $erreurs[$champ] = _T('association_dons:erreur_champ'); }
	}
	foreach (array('journal_dons', 'imputation_dons') as $champ) {
		if ($valeurs[$champ] !== '' && !preg_match('/^[a-zA-Z0-9_.-]{1,30}$/D', (string)$valeurs[$champ])) { $erreurs[$champ] = _T('association_dons:erreur_champ'); }
	}
	if ($valeurs['mode_recette'] !== 'oui') {
		if (!trim((string)$valeurs['url_confidentialite'])) { $erreurs['url_confidentialite'] = _T('association_dons:config_requis_ouverture'); }
		if (!empty($GLOBALS['association_metas']['comptes'])) {
			foreach (array('journal_dons', 'imputation_dons') as $champ) {
				if (!trim((string)$valeurs[$champ])) { $erreurs[$champ] = _T('association_dons:config_requis_ouverture'); }
			}
		}
	}
	return $erreurs;
}
function formulaires_configurer_association_dons_verifier_dist($section='') {
 if(!autoriser('gerer','associationdons') || !association_dons_config_champs($section)) { return array('message_erreur'=>_T('info_acces_interdit')); }
 $erreurs=association_dons_config_erreurs(association_dons_config_soumission($section));
 if($section!=='' && $section!=='recette') { $erreurs=array_intersect_key($erreurs,array_flip(association_dons_config_champs($section))); }
 if($section==='recette' && $erreurs) {
  $labels=array(); foreach(array_keys($erreurs) as $champ) { $labels[]=_T('association_dons:'.$champ); }
  $erreurs['message_erreur']=_T('association_dons:config_requis_ouverture').' '.implode(', ',$labels);
 }
 return $erreurs;
}
function formulaires_configurer_association_dons_traiter_dist($section='') {
 $erreurs=formulaires_configurer_association_dons_verifier_dist($section);
 if($erreurs) { return array('message_erreur'=>$erreurs['message_erreur']??_T('association_dons:erreur_champ')); }
 ecrire_config('association_dons',association_dons_config_soumission($section));
 return array('message_ok'=>_T('association_dons:config_enregistrement_ok'),'editable'=>true);
}

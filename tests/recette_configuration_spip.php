<?php
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('formulaires/configurer_association_dons');include_spip('public/assembler');include_spip('inc/filtres');
$avant=lire_config('association_dons',null);$identite=$GLOBALS['visiteur_session']??array();$r=array();
try {
 $GLOBALS['visiteur_session']=array();$r['visiteur_refuse']=formulaires_configurer_association_dons_charger_dist('montants')===false;
 $GLOBALS['visiteur_session']=sql_fetsel('id_auteur,nom,statut,webmestre','spip_auteurs',"login='blobul_ai'");
 $c=association_dons_config_valeurs();$c['mode_recette']='oui';$c['notifications']='oui';$c['email_secretariat']='config-recette@example.invalid';ecrire_config('association_dons',$c);
 $charge=formulaires_configurer_association_dons_charger_dist('notifications');$r['choix_notifications_conserve']=$charge['notifications']==='oui'&&association_dons_configuration()['notifications']==='non';
 foreach(association_dons_config_sections() as $section=>$champs){
  $charge=formulaires_configurer_association_dons_charger_dist($section);
  $h=recuperer_fond('formulaires/configurer_association_dons',$charge);
  $r['rendu_'.$section]=strlen($h)>600&&strpos($h,'<fieldset')!==false&&strpos($h,'erreur squelette')===false;
 }
 $r['section_inconnue_refusee']=formulaires_configurer_association_dons_charger_dist('inconnue')===false;
 set_request('montants','50;150');set_request('minimum','50');set_request('devise','EUR');set_request('intrus','oui');
 $r['validation_montants']=!formulaires_configurer_association_dons_verifier_dist('montants');
 $r['enregistrement_montants']=!empty(formulaires_configurer_association_dons_traiter_dist('montants')['message_ok']);
 $apres=lire_config('association_dons');$r['autres_rubriques_preservees']=$apres['notifications']==='oui'&&$apres['email_secretariat']===$c['email_secretariat']&&$apres['mode_recette']==='oui'&&!isset($apres['intrus'])&&!isset($apres['_saisies']);
 set_request('montants','50;50');$r['doublons_montants_refuses']=isset(formulaires_configurer_association_dons_verifier_dist('montants')['montants']);
 set_request('notifications','');set_request('email_secretariat','');set_request('delai_rappel','14');set_request('preavis_renouvellement','60');
 $r['desactivation_notifications']=!empty(formulaires_configurer_association_dons_traiter_dist('notifications')['message_ok'])&&lire_config('association_dons/notifications')==='non';
 set_request('notifications','oui');$r['contact_obligatoire']=isset(formulaires_configurer_association_dons_verifier_dist('notifications')['email_secretariat']);
 set_request('notifications','non');set_request('delai_rappel','0');$r['delai_zero_refuse']=isset(formulaires_configurer_association_dons_verifier_dist('notifications')['delai_rappel']);
 $c=association_dons_config_valeurs();$c['url_confidentialite']='';$c['journal_dons']='';$c['imputation_dons']='';ecrire_config('association_dons',$c);set_request('mode_recette','non');
 $e=formulaires_configurer_association_dons_verifier_dist('recette');$r['ouverture_incomplete_refusee']=isset($e['url_confidentialite'])&&!empty($e['message_erreur']);
 set_request('url_confidentialite','http://example.invalid');$r['url_non_https_refusee']=isset(formulaires_configurer_association_dons_verifier_dist('confidentialite')['url_confidentialite']);
 $r['erreurs_squelettes']=count($GLOBALS['tableau_des_erreurs']??array());
} finally {
 if($avant===null){effacer_config('association_dons');}else{ecrire_config('association_dons',$avant);}$GLOBALS['visiteur_session']=$identite;
}
echo json_encode($r);

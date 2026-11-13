<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('formulaires/editer_asso_dons');
include_spip('inc/autoriser');

function formulaires_editer_association_don_historique_saisies_dist($id=0) {
 $saisies=array();
 foreach(array('date_don'=>'date_aaaa_mm_jj','bienfaiteur'=>'nom_du_bienfaiteur','id_adherent'=>'nd_de_membre','argent'=>'don_financier_en_e__','colis'=>'colis','valeur'=>'contre_valeur_en_e__','contrepartie'=>'geste_association','commentaire'=>'remarques') as $champ=>$label) {
  $saisies[]=array('saisie'=>$champ==='commentaire'?'textarea':'input','options'=>array('nom'=>$champ,'label'=>_T('asso:'.$label),'obligatoire'=>$champ==='date_don'?'oui':''));
 }
 $journaux=array();foreach(sql_allfetsel('code,intitule','spip_asso_plan',array('active=1','classe='.sql_quote($GLOBALS['association_metas']['classe_banques']??'')),'','code') as $j) { $journaux[$j['code']]=$j['code'].' — '.$j['intitule']; }
 if($journaux) { array_splice($saisies,4,0,array(array('saisie'=>'selection','options'=>array('nom'=>'journal','label'=>_T('asso:prets_libelle_mode_paiement'),'data'=>$journaux)))); }
 return $saisies;
}
function formulaires_editer_association_don_historique_charger_dist($id=0) {
 if(!autoriser('associer','dons') || ((int)$id && !sql_countsel('spip_asso_dons','id_don='.(int)$id))) { return false; }
 $contexte=formulaires_editer_asso_dons_charger_dist($id?:'');
 if(!is_array($contexte)) { return false; }
 $contexte['date_don']=affdate($contexte['date_don'],'d/m/Y');
 $contexte['_saisies']=formulaires_editer_association_don_historique_saisies_dist($id);
 $contexte['editable']=true;
 return $contexte;
}
function formulaires_editer_association_don_historique_verifier_dist($id=0) {
 if(!autoriser('associer','dons')) { return array('message_erreur'=>_T('info_acces_interdit')); }
 return formulaires_editer_asso_dons_verifier_dist($id);
}
function formulaires_editer_association_don_historique_traiter_dist($id=0) {
 if($erreurs=formulaires_editer_association_don_historique_verifier_dist($id)) { return array('message_erreur'=>$erreurs['message_erreur']??_T('association_dons:erreur_champ')); }
 return formulaires_editer_asso_dons_traiter($id?:'');
}

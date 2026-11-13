<?php
if (!defined('_ECRIRE_INC_VERSION') || ($GLOBALS['meta']['adresse_site']??'')!=='https://dev.blobul.com') { throw new RuntimeException('DEV uniquement'); }
define('_ASSOCIATION_DONS_CAPTURE_EMAILS',true);
include_spip('inc/association_dons_notifications');include_spip('formulaires/configurer_association_dons');include_spip('public/assembler');include_spip('inc/filtres');include_spip('inc/bank');
$avant=lire_config('association_dons',null);$bank_avant=lire_config('bank_paiement',array());$visiteur=$GLOBALS['visiteur_session']??array();$s=array();$r=array();$total=sql_countsel('spip_asso_comptes');
try {
 $cfg=array('presta'=>'roi_baudouin','actif'=>1,'type'=>'acte','config'=>'roi_baudouin-recette','label'=>'RECETTE Fondation','url_fondation'=>'https://koningboudewijnstichting.twikey.com/web/fr/paymentForm.html');
 $b=$bank_avant;$b['config_roi_baudouin-recette']=$cfg;ecrire_config('bank_paiement',$b);
 $c=association_dons_configuration();$c['mode_recette']='non';$c['notifications']='oui';$c['email_secretariat']='association-recette@example.invalid';$c['prestataires']=array('roi_baudouin-recette');ecrire_config('association_dons',$c);
 $r['prestataire_decouvert']=in_array('roi_baudouin',bank_lister_prestas(),true);
 $r['selection_config_exacte']=array_keys(association_dons_prestataires_selectionnes())===array('roi_baudouin-recette');
 $GLOBALS['visiteur_session']=sql_fetsel('id_auteur,nom,statut,webmestre','spip_auteurs',"login='blobul_ai'");
 set_request('prestataires',array('inconnu'));$r['configuration_inconnue_refusee']=isset(formulaires_configurer_association_dons_verifier_dist('paiements')['prestataires']);
 set_request('prestataires',array());$r['selection_vide_refusee']=isset(formulaires_configurer_association_dons_verifier_dist('paiements')['prestataires']);
 set_request('prestataires',array('roi_baudouin-recette'));$r['selection_valide']=!formulaires_configurer_association_dons_verifier_dist('paiements');
 $loader=formulaires_configurer_association_dons_charger_dist('paiements');$r['saisie_configs_bank']=$loader['_saisies'][0]['saisies'][0]['saisie']==='checkbox'&&isset($loader['_saisies'][0]['saisies'][0]['options']['data']['roi_baudouin-recette']);
 $GLOBALS['visiteur_session']=array();
 $creation=association_dons_creer_soutien(array('nom'=>'RECETTE FONDATION BANK','prenom'=>'Test','email'=>'fondation-recette@example.invalid','type_donateur'=>'particulier','consentement'=>'oui'),'40','bank',bin2hex(random_bytes(32)));
 $s=$creation['suivi']??array();if(!$s){throw new RuntimeException('Création impossible');}
 foreach(sql_allfetsel('id_don_notification','spip_asso_dons_notifications','id_donateur='.(int)$s['id_donateur'])as$n){association_dons_envoyer_notification($n['id_don_notification']);}
 $t=sql_fetsel('*','spip_transactions','id_transaction='.(int)$s['id_transaction']);
 $html=recuperer_fond('modeles/association_dons_payer_acte',array('id_transaction'=>$s['id_transaction']));
 $r['seul_presta_selectionne']=strpos($html,'payer_roi_baudouin')!==false&&strpos($html,'payer_stripe')===false;
 $call=charger_fonction('response','presta/roi_baudouin/call');$response=array('id_transaction'=>$t['id_transaction'],'transaction_hash'=>$t['transaction_hash'],'autorisation_id'=>'wait');
 $attente=$call($cfg,$response);$t=sql_fetsel('*','spip_transactions','id_transaction='.(int)$s['id_transaction']);
 $r['choix_reste_attente']=$attente[1]==='wait'&&$t['statut']==='attente'&&$t['reglee']!=='oui';
 $flux=array('args'=>array('succes'=>'wait','id_transaction'=>$s['id_transaction'],'row'=>$t),'data'=>'fallback');
 $r['redirection_twikey']=pipeline('bank_redirige_apres_retour_transaction',$flux)===$cfg['url_fondation'];
 $r['aucun_don_au_retour']=!sql_getfetsel('id_don','spip_asso_dons_suivi','id_don_suivi='.(int)$s['id_don_suivi']);
 include_spip('formulaires/confirmer_association_don');include_spip('formulaires/relancer_association_don');
 $GLOBALS['visiteur_session']=sql_fetsel('id_auteur,nom,statut,webmestre','spip_auteurs',"login='blobul_ai'");
 foreach(array('confirmer_association_don'=>'preuve_reglement','relancer_association_don'=>'confirmer')as$form=>$champ){$charge=('formulaires_'.$form.'_charger_dist')($s['id_don_suivi']);$html=recuperer_fond('formulaires/'.$form,$charge);$r[$form.'_saisies_rendues']=strpos($html,'name="'.$champ.'"')!==false;set_request($champ,'');$r[$form.'_vide_refuse']=!empty(('formulaires_'.$form.'_verifier_dist')($s['id_don_suivi']));}
 $GLOBALS['visiteur_session']=array();
 $r['manuel_disponible']=association_dons_confirmation_manuelle_possible($s['id_don_suivi']);
 $r['visiteur_ne_confirme_pas']=!association_dons_confirmer_externe($s['id_don_suivi'],'preuve');
 $GLOBALS['visiteur_session']=sql_fetsel('id_auteur,nom,statut,webmestre','spip_auteurs',"login='blobul_ai'");
 // Neutralisation locale au processus du ticket Bank ; aucun autre réglage persistant n'est modifié.
 $bm=unserialize($GLOBALS['meta']['bank_paiement']);$bm['email_ticket_admin']='';$GLOBALS['meta']['bank_paiement']=serialize($bm);
 $r['confirmation_admin']=association_dons_confirmer_externe($s['id_don_suivi'],'RECETTE Fondation — vérification externe');
 $s=sql_fetsel('*','spip_asso_dons_suivi','id_don_suivi='.(int)$s['id_don_suivi']);$t=sql_fetsel('*','spip_transactions','id_transaction='.(int)$s['id_transaction']);
 $r['transaction_et_soutien_regles']=$t['reglee']==='oui'&&$s['statut']==='regle'&&$s['mode_paiement']==='fondation';
 $r['sans_recette_bancaire_locale']=!$s['id_compte']&&sql_countsel('spip_asso_comptes')===$total;
 foreach(sql_allfetsel('id_don_notification','spip_asso_dons_notifications','id_donateur='.(int)$s['id_donateur'])as$n){association_dons_envoyer_notification($n['id_don_notification']);}
 $r['deux_confirmations_capturees']=sql_countsel('spip_asso_dons_notifications','id_donateur='.(int)$s['id_donateur']." AND type_notification IN ('soutien_regle','soutien_regle_association') AND etat='capture'")===2;
 $rejeu=$call($cfg,$response);$t=sql_fetsel('*','spip_transactions','id_transaction='.(int)$s['id_transaction']);$r['attente_rejouee_ne_rouvre_pas']=$rejeu[1]===true&&$t['reglee']==='oui'&&$t['statut']==='ok';
 $r['confirmation_idempotente']=association_dons_confirmer_externe($s['id_don_suivi'],'RECETTE deuxième appel')&&sql_countsel('spip_asso_dons_notifications','id_donateur='.(int)$s['id_donateur'])===3;
 $r['erreurs_squelettes']=count($GLOBALS['tableau_des_erreurs']??array());
} finally {
 if($s){$s=sql_fetsel('*','spip_asso_dons_suivi','id_don_suivi='.(int)$s['id_don_suivi']);if($s['id_don']){sql_delete('spip_asso_dons','id_don='.(int)$s['id_don']);}sql_delete('spip_asso_dons_notifications','id_donateur='.(int)$s['id_donateur']);sql_delete('spip_asso_donateurs_periodes','id_donateur='.(int)$s['id_donateur']);sql_delete('spip_asso_donateurs','id_donateur='.(int)$s['id_donateur']);sql_delete('spip_asso_dons_suivi','id_don_suivi='.(int)$s['id_don_suivi']);sql_delete('spip_transactions','id_transaction='.(int)$s['id_transaction']);}
 ecrire_config('bank_paiement',$bank_avant);if($avant===null){effacer_config('association_dons');}else{ecrire_config('association_dons',$avant);}$GLOBALS['visiteur_session']=$visiteur;
}
echo json_encode($r);

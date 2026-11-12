<?php
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('inc/association_dons_notifications'); include_spip('public/assembler'); include_spip('inc/filtres');
$avant=lire_config('association_dons',null); $suivi=array(); $r=array();
try {
 $c=association_dons_configuration(); $c['mode_recette']='oui'; $c['notifications']='oui'; ecrire_config('association_dons',$c);
 $r['notifications_forcees_non']=association_dons_configuration()['notifications']==='non';
 $n=sql_countsel('spip_transactions');
 $s=association_dons_creer_soutien(array('nom'=>'RECETTE MODE SANS PAIEMENT','prenom'=>'Test','email'=>'recette-mode@example.invalid','type_donateur'=>'particulier','consentement'=>'oui','publication_nom'=>'non'),'40','bank',bin2hex(random_bytes(32)));
 $suivi=$s['suivi']??array(); if(!$suivi) { throw new RuntimeException('Création impossible'); }
 $r['aucune_transaction']=sql_countsel('spip_transactions')===$n && !(int)$suivi['id_transaction'];
 $h=recuperer_fond('content/association_dons_paiement',array('id_suivi'=>$suivi['id_don_suivi'],'jeton'=>association_dons_jeton_suivi($suivi)));
 $r['avertissement']=strpos($h,'RECETTE')!==false;
 $r['aucun_bouton_prestataire']=strpos($h,'<form')===false && strpos($h,'payer_mode')===false;
 $r['aucun_email_prepare']=association_dons_programmer_notification('soutien_cree',$suivi['id_donateur'],$suivi['id_don_suivi'])===0;
 $r['erreurs_squelettes']=count($GLOBALS['tableau_des_erreurs']??array());
} finally {
 if($suivi){sql_delete('spip_asso_dons_suivi','id_don_suivi='.(int)$suivi['id_don_suivi']);sql_delete('spip_asso_donateurs_periodes','id_donateur='.(int)$suivi['id_donateur']);sql_delete('spip_asso_donateurs','id_donateur='.(int)$suivi['id_donateur']);}
 if($avant===null){effacer_config('association_dons');}else{ecrire_config('association_dons',$avant);}
}
echo json_encode($r);

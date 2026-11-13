<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');
include_spip('inc/association_dons_soutiens');

function association_dons_libelle_statut($statut) {
	return in_array($statut, array('a_jour', 'attente', 'echu', 'ancien'), true) ? _T('association_dons:statut_' . $statut) : '';
}

function association_dons_libelle_notification($type) {
	return in_array($type, array('soutien_cree', 'soutien_regle', 'soutien_regle_association', 'rappel_paiement', 'renouvellement'), true) ? _T('association_dons:notification_' . $type) : '';
}

function association_dons_libelle_envoi($etat) {
	return in_array($etat, array('prepare', 'en_cours', 'accepte', 'capture', 'incertain', 'annule'), true) ? _T('association_dons:envoi_' . $etat) : '';
}

function association_dons_statut_fiche($id) {
 return association_dons_statut(association_dons_donateur($id),association_dons_periodes($id),date('Y-m-d'));
}
function association_dons_libelle_paiement($mode) {
 return in_array($mode,array('bank','virement','fondation'),true) ? _T('association_dons:paiement_'.$mode) : '';
}
function association_dons_libelle_reglement($statut) {
 return in_array($statut,array('attente','regle'),true) ? _T('association_dons:reglement_'.$statut) : '';
}

function association_dons_libelle_type($type) {
 return in_array($type,array('particulier','organisation'),true) ? _T('association_dons:'.$type) : '';
}

/** Lecture des dons historiques : leur visibilité ne dépend pas d'une jointure comptable. */
function association_dons_historique($annee='', $recherche='') {
 if (!autoriser('associer','dons')) { return array(); }
 $where=array();
 if (preg_match('/^[0-9]{4}$/D',(string)$annee)) { $where[]='date_don>='.sql_quote($annee.'-01-01');$where[]='date_don<'.sql_quote(((int)$annee+1).'-01-01'); }
 if ($recherche!=='') { $mot=sql_quote('%'.str_replace(array('%','_'),array('\\%','\\_'),mb_substr($recherche,0,100)).'%');$where[]='bienfaiteur LIKE '.$mot; }
 return sql_allfetsel('*','spip_asso_dons',$where,'','date_don DESC,id_don DESC');
}
function association_dons_historique_annees() {
 if (!autoriser('associer','dons')) { return array(); }
 $annees=array();foreach(sql_allfetsel('date_don','spip_asso_dons') as $don) { $annee=substr($don['date_don'],0,4);if(ctype_digit($annee)&&(int)$annee>0) { $annees[$annee]=$annee; } }krsort($annees);return array_values($annees);
}

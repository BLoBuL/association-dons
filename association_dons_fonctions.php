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

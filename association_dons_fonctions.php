<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');
include_spip('inc/association_dons_soutiens');

function association_dons_libelle_statut($statut) {
	return in_array($statut, array('a_jour', 'attente', 'echu', 'ancien'), true) ? _T('association_dons:statut_' . $statut) : '';
}

function association_dons_libelle_notification($type) {
	return in_array($type, array('soutien_cree', 'soutien_regle', 'rappel_paiement', 'renouvellement'), true) ? _T('association_dons:notification_' . $type) : '';
}

function association_dons_libelle_envoi($etat) {
	return in_array($etat, array('prepare', 'en_cours', 'accepte', 'capture', 'incertain', 'annule'), true) ? _T('association_dons:envoi_' . $etat) : '';
}

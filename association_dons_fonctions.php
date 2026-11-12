<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');
include_spip('inc/association_dons_soutiens');

function association_dons_libelle_statut($statut) {
	return in_array($statut, array('a_jour', 'attente', 'echu', 'ancien'), true) ? _T('association_dons:statut_' . $statut) : '';
}

<?php
if (!defined('_ECRIRE_INC_VERSION')) return;
function association_dons_upgrade($meta, $cible) {
	include_spip('base/upgrade');
	$tables = array('spip_asso_donateurs', 'spip_asso_donateurs_periodes', 'spip_asso_dons_suivi');
	maj_plugin($meta, $cible, array(
		'create' => array(array('maj_tables', $tables)),
		'1.1.0' => array(array('maj_tables', $tables)),
		'1.2.0' => array(array('maj_tables', $tables)),
		'1.3.0' => array(array('maj_tables', $tables)),
	));
}
function association_dons_vider_tables($meta) { effacer_meta($meta); }

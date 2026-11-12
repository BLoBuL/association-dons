<?php
/** Installation neuve isolée : uniquement si les quatre tables DEV sont vides. */
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('association_dons_administrations');
$tables = array('spip_asso_donateurs', 'spip_asso_donateurs_periodes', 'spip_asso_dons_suivi', 'spip_asso_dons_notifications');
$renommages = array(); $restaurations = array();
foreach ($tables as $table) {
	if (sql_countsel($table) !== 0) { throw new RuntimeException('Tables de recette non vides : aucune modification.'); }
	$copie = 'recette_dons_' . substr($table, 10) . '_20261112';
	if (sql_showtable($copie, true)) { throw new RuntimeException('Une sauvegarde temporaire existe déjà.'); }
	$renommages[] = $table . ' TO ' . $copie;
	$restaurations[] = $copie . ' TO ' . $table;
}
$meta_avant = sql_getfetsel('valeur', 'spip_meta', "nom='association_dons_base_version'");
$don_historique_avant = sql_countsel('spip_asso_dons');
if (!sql_query('RENAME TABLE ' . implode(',', $renommages))) { throw new RuntimeException('Isolation SQL refusée.'); }
$r = array(); $id = 0;
try {
	effacer_meta('association_dons_base_version');
	association_dons_upgrade('association_dons_base_version', '1.4.0');
	$r['installation_native_1_4'] = sql_getfetsel('valeur', 'spip_meta', "nom='association_dons_base_version'") === '1.4.0';
	foreach ($tables as $table) { $r[$table . '_creee'] = (bool)sql_showtable($table, true); }
	$id = (int)sql_insertq('spip_asso_donateurs', array('nom' => 'RECETTE INSTALLATION', 'email' => 'installation@example.invalid'));
	$r['insertion_neuve'] = $id > 0;
	association_dons_vider_tables('association_dons_base_version');
	$r['desinstallation_conserve_donnees'] = sql_countsel('spip_asso_donateurs', 'id_donateur=' . $id) === 1;
	ecrire_meta('association_dons_base_version', '1.0.0');
	association_dons_upgrade('association_dons_base_version', '1.4.0');
	$r['upgrade_popule_conserve_donateur'] = sql_getfetsel('nom', 'spip_asso_donateurs', 'id_donateur=' . $id) === 'RECETTE INSTALLATION';
	$r['dons_historiques_conserves'] = sql_countsel('spip_asso_dons') === $don_historique_avant;
} finally {
	if ($id) { sql_delete('spip_asso_donateurs', 'id_donateur=' . $id . " AND nom='RECETTE INSTALLATION'"); }
	foreach ($tables as $table) {
		if (sql_showtable($table, true)) {
			if (sql_countsel($table) !== 0) { throw new RuntimeException('Données inattendues dans les tables neuves : conservation pour contrôle.'); }
			sql_drop_table($table);
		}
	}
	if (!sql_query('RENAME TABLE ' . implode(',', $restaurations))) { throw new RuntimeException('Restauration des tables interrompue.'); }
	if ($meta_avant === false) { effacer_meta('association_dons_base_version'); } else { ecrire_meta('association_dons_base_version', $meta_avant); }
}
$r['schema_initial_retabli'] = sql_getfetsel('valeur', 'spip_meta', "nom='association_dons_base_version'") === $meta_avant;
echo json_encode($r, JSON_UNESCAPED_UNICODE);

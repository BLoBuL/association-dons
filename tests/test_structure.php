<?php
/** Recette locale, uniquement avec une base SQL synthétique dédiée. */
define('_ECRIRE_INC_VERSION', 1);
require dirname(__DIR__) . '/base/association_dons.php';
require dirname(__DIR__) . '/association_dons_administrations.php';
function verifier($condition, $message) {
	if (!$condition) { throw new RuntimeException($message); }
}
function include_spip($fichier) {}
function effacer_meta($meta) { $GLOBALS['meta_effacee'] = $meta; }
function maj_plugin($meta, $cible, $etapes) { $GLOBALS['etapes'] = $etapes; }
$historique = array('field' => array('id_don' => 'BIGINT NOT NULL'), 'key' => array('PRIMARY KEY' => 'id_don'));
$tables = association_dons_declarer_tables_principales(array('spip_asso_dons' => $historique));
verifier($tables['spip_asso_dons'] === $historique, 'Déclaration historique altérée.');
$neuves = association_dons_declarer_tables_principales(array());
verifier(!isset($neuves['spip_asso_dons']), 'Déclaration concurrente de la table historique.');
association_dons_upgrade('association_dons_base_version', '1.1.0');
foreach (array('create', '1.1.0', '1.2.0', '1.3.0', '1.4.0') as $etape) {
	verifier($GLOBALS['etapes'][$etape][0][0] === 'maj_tables', 'Migration additive attendue.');
	verifier(!in_array('spip_asso_dons', $GLOBALS['etapes'][$etape][0][1], true), 'Migration de la table historique interdite.');
}
association_dons_vider_tables('association_dons_base_version');
verifier($GLOBALS['meta_effacee'] === 'association_dons_base_version', 'Métadonnée incorrecte.');
$interfaces = association_dons_declarer_tables_interfaces(array('table_des_tables' => array('autre' => 'autre')));
verifier($interfaces['table_des_tables']['autre'] === 'autre', 'Alias voisin perdu.');
if (!getenv('ASSOCIATION_DONS_TEST_PORT')) {
	echo "OK : contrats de déclaration et de migration. Recette MariaDB non demandée.\n";
	exit(0);
}
$db = new mysqli('127.0.0.1', 'root', '', '', (int)getenv('ASSOCIATION_DONS_TEST_PORT'));
$base = 'recette_association_dons_' . getmypid();
$db->query("CREATE DATABASE `$base`");
$db->select_db($base);
try {
	$db->query('CREATE TABLE spip_asso_dons (id_don BIGINT PRIMARY KEY, bienfaiteur TEXT, argent TEXT)');
	$db->query("INSERT INTO spip_asso_dons VALUES (42, 'Donateur historique synthétique', '125.50')");
	$avant = $db->query('SELECT * FROM spip_asso_dons')->fetch_all(MYSQLI_ASSOC);
	// DDL réel sur MariaDB, sans prétendre exécuter le moteur de migration SPIP.
	foreach ($neuves as $nom => $definition) {
		$colonnes = array();
		foreach ($definition['field'] as $champ => $sql) {
			if ($champ === $definition['key']['PRIMARY KEY']) { $sql .= ' AUTO_INCREMENT'; }
			$colonnes[] = "`$champ` $sql";
		}
		foreach ($definition['key'] as $cle => $champs) { $colonnes[] = "$cle ($champs)"; }
		$sql = "CREATE TABLE IF NOT EXISTS `$nom` (" . implode(',', $colonnes) . ')';
		$db->query($sql);
		$db->query($sql);
	}
	verifier($db->query('SELECT * FROM spip_asso_dons')->fetch_all(MYSQLI_ASSOC) === $avant, 'Don historique modifié.');
	$db->query("INSERT INTO spip_asso_donateurs (nom,email) VALUES ('Ami synthétique','ami@example.invalid')");
	$id = $db->insert_id;
	$db->query("INSERT INTO spip_asso_donateurs_periodes (id_donateur,annee,date_debut,date_fin) VALUES ($id,2027,'2026-11-12','2027-12-31')");
	$periode = $db->insert_id;
	$db->query("INSERT INTO spip_asso_dons_suivi (id_don,id_donateur,id_donateur_periode) VALUES (42,$id,$periode)");
	foreach (array("INSERT INTO spip_asso_donateurs_periodes (id_donateur,annee) VALUES ($id,2027)", "INSERT INTO spip_asso_dons_suivi (id_don) VALUES (42)") as $sql) {
		$refuse = false;
		try { $db->query($sql); } catch (mysqli_sql_exception $e) { if ($e->getCode() !== 1062) { throw $e; } $refuse = true; }
		verifier($refuse, 'Doublon métier accepté.');
	}
	verifier($db->query('SELECT * FROM spip_asso_dons')->fetch_all(MYSQLI_ASSOC) === $avant, 'Rattachement destructif.');
	echo "OK : MariaDB, création, reprise, don historique intact, rattachement sans adhérent ni organisation, refus des doublons.\n";
} finally {
	$db->query("DROP DATABASE `$base`");
	$db->close();
}

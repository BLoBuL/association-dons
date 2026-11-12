<?php
if (!defined('_ECRIRE_INC_VERSION')) return;
function association_dons_declarer_tables_principales($tables) {
	// Association reste propriétaire du schéma et des écritures historiques.
	// Ne pas redéclarer sa table : l'ordre des plugins ne doit pas décider du schéma.
	$tables['spip_asso_donateurs'] = array('field' => array(
		'id_donateur' => 'BIGINT NOT NULL',
		'type_donateur' => "VARCHAR(20) NOT NULL DEFAULT 'particulier'",
		'nom' => "VARCHAR(255) NOT NULL DEFAULT ''",
		'prenom' => "VARCHAR(255) NOT NULL DEFAULT ''",
		'email' => "VARCHAR(255) NOT NULL DEFAULT ''",
		'id_organisation' => 'BIGINT NOT NULL DEFAULT 0',
		'publication_nom' => "VARCHAR(3) NOT NULL DEFAULT 'non'",
		'date_consentement' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'date_creation' => "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'archive' => "VARCHAR(3) NOT NULL DEFAULT 'non'",
		'maj' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
	), 'key' => array('PRIMARY KEY' => 'id_donateur', 'KEY email' => 'email', 'KEY id_organisation' => 'id_organisation'));
	$tables['spip_asso_donateurs_periodes'] = array('field' => array(
		'id_donateur_periode' => 'BIGINT NOT NULL',
		'id_donateur' => 'BIGINT NOT NULL DEFAULT 0',
		'annee' => 'SMALLINT NOT NULL DEFAULT 0',
		'date_debut' => "DATE NOT NULL DEFAULT '0000-00-00'",
		'date_fin' => "DATE NOT NULL DEFAULT '0000-00-00'",
		'statut' => "VARCHAR(20) NOT NULL DEFAULT 'attente'",
		'maj' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
	), 'key' => array('PRIMARY KEY' => 'id_donateur_periode', 'UNIQUE KEY donateur_annee' => 'id_donateur,annee'));
	$tables['spip_asso_dons_suivi'] = array('field' => array(
		'id_don_suivi' => 'BIGINT NOT NULL',
		'id_don' => 'BIGINT NOT NULL DEFAULT 0',
		'id_donateur' => 'BIGINT NOT NULL DEFAULT 0',
		'id_donateur_periode' => 'BIGINT NOT NULL DEFAULT 0',
		'id_transaction' => 'BIGINT NOT NULL DEFAULT 0',
		'mode_paiement' => "VARCHAR(30) NOT NULL DEFAULT ''",
		'maj' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
	), 'key' => array('PRIMARY KEY' => 'id_don_suivi', 'UNIQUE KEY id_don' => 'id_don', 'KEY id_donateur' => 'id_donateur', 'KEY id_donateur_periode' => 'id_donateur_periode', 'KEY id_transaction' => 'id_transaction'));
	return $tables;
}

function association_dons_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['asso_dons'] = 'asso_dons';
	$interfaces['table_des_tables']['asso_donateurs'] = 'asso_donateurs';
	$interfaces['table_des_tables']['asso_donateurs_periodes'] = 'asso_donateurs_periodes';
	$interfaces['table_des_tables']['asso_dons_suivi'] = 'asso_dons_suivi';
	return $interfaces;
}

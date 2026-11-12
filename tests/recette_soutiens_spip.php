<?php
/** Recette synthétique serveur ; ne contacte aucun prestataire de paiement. */
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('inc/association_dons_soutiens');
$configuration_avant = lire_config('association_dons', null);
$identite_avant = $GLOBALS['visiteur_session'] ?? array();
$comptes_avant = $GLOBALS['association_metas'];
$cles = array(); $r = array();
$total_comptes = sql_countsel('spip_asso_comptes');
try {
	$config = association_dons_configuration();
	$config['virement'] = $config['fondation'] = 'oui';
	$config['notifications'] = 'non'; $config['journal_dons'] = '101';
	ecrire_config('association_dons', $config);
	$GLOBALS['association_metas']['comptes'] = true;
	$GLOBALS['association_metas']['pc_dons'] = '758';
	$GLOBALS['visiteur_session'] = array();
	$donnees = array('nom' => 'RECETTE SOUTIEN', 'prenom' => 'Synthétique', 'email' => 'recette-soutien@example.invalid', 'type_donateur' => 'particulier', 'consentement' => 'oui');
	$cle = hash('sha256', random_bytes(32)); $cles[] = $cle;
	$r['minimum_refuse'] = !empty(association_dons_creer_soutien($donnees, '0.01', 'bank', $cle)['erreur']);
	$resultat = association_dons_creer_soutien($donnees, '40', 'bank', $cle);
	if (empty($resultat['suivi'])) { throw new RuntimeException('Création Bank refusée.'); }
	$suivi = $resultat['suivi'];
	$r['bank_cree_sans_adherent'] = $suivi['id_transaction'] > 0 && association_dons_donateur($suivi['id_donateur'])['id_organisation'] == 0;
	$r['attente_avant_paiement'] = association_dons_statut(association_dons_donateur($suivi['id_donateur']), association_dons_periodes($suivi['id_donateur']), date('Y-m-d')) === 'attente';
	$r['double_soumission'] = association_dons_creer_soutien($donnees, '40', 'bank', $cle)['suivi']['id_don_suivi'] === $suivi['id_don_suivi'];
	$r['rejeu_modifie_refuse'] = !empty(association_dons_creer_soutien($donnees, '100', 'bank', $cle)['erreur']);
	$r['jeton_invalide_refuse'] = association_dons_suivi_public($suivi['id_don_suivi'], 'invalide') === array();
	$r['jeton_valide'] = (bool)association_dons_suivi_public($suivi['id_don_suivi'], association_dons_jeton_suivi($suivi));
	$r['commande_non_payee_refusee'] = !association_dons_confirmer_bank($suivi['id_transaction']);
	sql_updateq('spip_transactions', array('statut' => 'ok', 'reglee' => 'oui', 'montant_regle' => 1), 'id_transaction=' . (int)$suivi['id_transaction']);
	$r['paiement_partiel_refuse'] = !association_dons_confirmer_bank($suivi['id_transaction']);
	sql_updateq('spip_transactions', array('montant_regle' => 40), 'id_transaction=' . (int)$suivi['id_transaction']);
	$flux = array('args' => array('id_transaction' => (int)$suivi['id_transaction']), 'data' => 'RECETTE');
	pipeline('bank_traiter_reglement', $flux);
	$r['pipeline_reglement'] = sql_getfetsel('statut', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$suivi['id_don_suivi']) === 'regle';
	$r['a_jour_apres_reglement'] = association_dons_statut(association_dons_donateur($suivi['id_donateur']), association_dons_periodes($suivi['id_donateur']), date('Y-m-d')) === 'a_jour';
	pipeline('bank_traiter_reglement', $flux);
	$r['comptabilite_unique'] = sql_countsel('spip_asso_comptes', "objet='don' AND id_objet=" . (int)$suivi['id_don']) === 1;
	foreach (array('virement', 'fondation') as $mode) {
		$cle = hash('sha256', random_bytes(32)); $cles[] = $cle;
		$externe = association_dons_creer_soutien($donnees, '40', $mode, $cle)['suivi'];
		$r[$mode . '_visiteur_refuse'] = !association_dons_confirmer_externe($externe['id_don_suivi'], 'RECETTE');
		$GLOBALS['visiteur_session'] = sql_fetsel('id_auteur,nom,statut,webmestre', 'spip_auteurs', "login='blobul_ai'");
		$r[$mode . '_preuve_vide_refusee'] = !association_dons_confirmer_externe($externe['id_don_suivi'], '');
		$r[$mode . '_confirme'] = association_dons_confirmer_externe($externe['id_don_suivi'], 'RECETTE confirmation synthétique');
		$GLOBALS['visiteur_session'] = array();
	}
} finally {
	$donateurs = array();
	foreach (sql_allfetsel('*', 'spip_asso_dons_suivi', sql_in('cle_creation', $cles)) as $s) {
		if ($s['id_compte']) { sql_delete('spip_asso_comptes', 'id_compte=' . (int)$s['id_compte'] . " AND objet='don' AND id_objet=" . (int)$s['id_don']); }
		if ($s['id_transaction']) { sql_delete('spip_transactions', 'id_transaction=' . (int)$s['id_transaction'] . ' AND auteur_id=' . sql_quote('association_dons:' . $s['id_don_suivi'])); }
		if ($s['id_don']) { sql_delete('spip_asso_dons', 'id_don=' . (int)$s['id_don'] . " AND bienfaiteur='Synthétique RECETTE SOUTIEN'"); }
		$donateurs[] = (int)$s['id_donateur'];
		sql_delete('spip_asso_dons_suivi', 'id_don_suivi=' . (int)$s['id_don_suivi']);
	}
	if ($donateurs) {
		sql_delete('spip_asso_donateurs_periodes', sql_in('id_donateur', $donateurs));
		sql_delete('spip_asso_donateurs', sql_in('id_donateur', $donateurs) . " AND nom='RECETTE SOUTIEN'");
	}
	if ($configuration_avant === null) { effacer_config('association_dons'); } else { ecrire_config('association_dons', $configuration_avant); }
	$GLOBALS['visiteur_session'] = $identite_avant;
	$GLOBALS['association_metas'] = $comptes_avant;
}
$r['comptes_restaures'] = sql_countsel('spip_asso_comptes') === $total_comptes;
echo json_encode($r, JSON_UNESCAPED_UNICODE);

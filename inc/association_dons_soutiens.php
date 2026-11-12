<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');
include_spip('inc/securiser_action');
include_spip('inc/flock');

/** Verrou local SPIP : nombre de fichiers borné, aucun verrou de site global. */
function association_dons_verrou($cle) {
	$famille = explode('-', $cle, 2)[0];
	if (!in_array($famille, array('creation', 'donateur', 'reglement', 'notification', 'envoi'), true)) { $famille = 'autre'; }
	return spip_fopen_lock(_DIR_TMP . 'association-dons-' . $famille . '-' . substr(hash('sha256', $cle), 0, 2) . '.lock', 'c+', LOCK_EX);
}

function association_dons_jeton_renouvellement($id_donateur, $annee) {
	$donateur = association_dons_donateur($id_donateur);
	return $donateur ? calculer_cle_action('association-dons-renouvellement-' . (int)$id_donateur . '-' . (int)$annee . '-' . $donateur['email']) : '';
}

function association_dons_renouvellement_valide($id_donateur, $annee, $jeton) {
	$annee_courante = (int)date('Y');
	$attendu = association_dons_jeton_renouvellement($id_donateur, $annee);
	return $annee >= $annee_courante && $annee <= $annee_courante + 1 && $jeton !== '' && $attendu !== '' && hash_equals($attendu, (string)$jeton);
}

function association_dons_jeton_suivi($suivi) {
	return calculer_cle_action('association-dons-suivi-' . (int)$suivi['id_don_suivi'] . '-' . $suivi['cle_creation']);
}

function association_dons_suivi_public($id_suivi, $jeton) {
	$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$id_suivi);
	return $suivi && $jeton !== '' && hash_equals(association_dons_jeton_suivi($suivi), (string)$jeton) ? $suivi : array();
}

function association_dons_url_suivi($suivi) {
	return generer_url_public('association_dons_paiement', 'id_suivi=' . (int)$suivi['id_don_suivi'] . '&jeton=' . rawurlencode(association_dons_jeton_suivi($suivi)), true, false);
}

/** Création d'une intention, sans compte adhérent ni règlement présumé. */
function association_dons_creer_soutien($donnees, $montant, $mode, $cle, $renouvellement = array()) {
	$config = association_dons_configuration();
	$centimes = association_dons_centimes($montant);
	$modes = array('bank');
	foreach (array('virement', 'fondation') as $autre) { if ($config[$autre] === 'oui') { $modes[] = $autre; } }
	if (!$centimes || $centimes < association_dons_centimes($config['minimum']) || !in_array($mode, $modes, true) || !preg_match('/^[a-f0-9]{64}$/D', (string)$cle)) { return array('erreur' => 'demande_invalide'); }
	$id_donateur = (int)($renouvellement['id_donateur'] ?? 0);
	if ($id_donateur) {
		if (!association_dons_renouvellement_valide($id_donateur, (int)($renouvellement['annee'] ?? 0), $renouvellement['jeton'] ?? '')) { return array('erreur' => 'interdit'); }
		$donnees = association_dons_donateur($id_donateur);
		if ($donnees['archive'] === 'oui') { return array('erreur' => 'interdit'); }
	} elseif (association_dons_identite_verifier($donnees) || ($donnees['consentement'] ?? '') !== 'oui') { return array('erreur' => 'identite_invalide'); }
	$empreinte = hash('sha256', json_encode(array($donnees, $centimes, $mode, $id_donateur, $renouvellement['annee'] ?? 0), JSON_UNESCAPED_UNICODE));
	$verrou = association_dons_verrou($id_donateur ? 'donateur-' . $id_donateur : 'creation-' . $cle);
	if (!$verrou) { return array('erreur' => 'indisponible'); }
	try {
		$existant = sql_fetsel('*', 'spip_asso_dons_suivi', 'cle_creation=' . sql_quote($cle));
		if ($existant) {
			if (!hash_equals($existant['empreinte_demande'], $empreinte)) { return array('erreur' => 'demande_modifiee'); }
			return $existant['statut'] === 'preparation' ? array('erreur' => 'preparation_incomplete') : array('suivi' => $existant);
		}
		// Persister l'identité de la demande avant les autres écritures et l'appel Bank.
		// Une interruption garde cette même demande ; elle n'autorise pas une nouvelle transaction.
		$id_suivi = (int)sql_insertq('spip_asso_dons_suivi', array('id_don' => null, 'statut' => 'preparation', 'mode_paiement' => $mode, 'montant' => $centimes / 100, 'devise' => $config['devise'], 'cle_creation' => $cle, 'empreinte_demande' => $empreinte, 'date_creation' => date('Y-m-d H:i:s')));
		if (!$id_suivi) { return array('erreur' => 'enregistrement'); }
		$nouveau = !$id_donateur;
		if ($nouveau) {
			$set = array();
			foreach (array('nom', 'prenom', 'raison_sociale', 'type_donateur') as $champ) { $set[$champ] = trim((string)($donnees[$champ] ?? '')); }
			$set['email'] = mb_strtolower(trim($donnees['email']));
			$set['publication_nom'] = $donnees['publication_nom'] ?? 'non';
			$set['langue'] = in_array($GLOBALS['spip_lang'] ?? 'fr', array('fr', 'en'), true) ? ($GLOBALS['spip_lang'] ?? 'fr') : 'fr';
			$set['date_creation'] = $set['date_consentement'] = date('Y-m-d H:i:s');
			$id_donateur = (int)sql_insertq('spip_asso_donateurs', $set);
			if (!$id_donateur) { return array('erreur' => 'enregistrement'); }
		}
		sql_updateq('spip_asso_dons_suivi', array('id_donateur' => $id_donateur), 'id_don_suivi=' . $id_suivi);
		$bornes = association_dons_bornes_periode($id_donateur && !empty($renouvellement['annee']) ? (int)$renouvellement['annee'] . '-01-01' : date('Y-m-d'));
		$id_periode = (int)sql_getfetsel('id_donateur_periode', 'spip_asso_donateurs_periodes', 'id_donateur=' . $id_donateur . ' AND annee=' . $bornes['annee']);
		if (!$id_periode) { $id_periode = (int)sql_insertq('spip_asso_donateurs_periodes', array_merge($bornes, array('id_donateur' => $id_donateur))); }
		if (!$id_periode) { return array('erreur' => 'enregistrement'); }
		sql_updateq('spip_asso_dons_suivi', array('id_donateur_periode' => $id_periode), 'id_don_suivi=' . $id_suivi);
		$id_don = (int)sql_insertq('spip_asso_dons', array('date_don' => date('Y-m-d'), 'bienfaiteur' => trim(($donnees['prenom'] ?? '') . ' ' . $donnees['nom']), 'id_adherent' => 0, 'argent' => $centimes / 100, 'valeur' => '0', 'commentaire' => 'Soutien annuel ' . $bornes['annee']));
		if (!$id_don) { return array('erreur' => 'enregistrement'); }
		if (sql_updateq('spip_asso_dons_suivi', array('id_don' => $id_don), 'id_don_suivi=' . $id_suivi) === false) { return array('erreur' => 'enregistrement'); }
		if ($mode === 'bank') {
			$inserer = charger_fonction('inserer_transaction', 'bank');
			$id_transaction = (int)$inserer($centimes / 100, array('devise' => $config['devise'], 'id_auteur' => 0, 'auteur_id' => 'association_dons:' . $id_suivi, 'auteur' => $donnees['email'], 'force' => false));
			if (!$id_transaction || sql_updateq('spip_asso_dons_suivi', array('id_transaction' => $id_transaction), 'id_don_suivi=' . $id_suivi) === false) { return array('erreur' => 'preparation_incomplete'); }
		}
		if (sql_updateq('spip_asso_dons_suivi', array('statut' => 'attente'), 'id_don_suivi=' . $id_suivi) === false) { return array('erreur' => 'preparation_incomplete'); }
		$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . $id_suivi);
		pipeline('association_dons_evenement', array('args' => array('evenement' => 'soutien_cree', 'id_donateur' => $id_donateur, 'id_don_suivi' => $id_suivi), 'data' => array()));
		return array('suivi' => $suivi);
	} finally { spip_fclose_unlock($verrou); }
}

/** Le callback ne fait confiance qu'à la transaction Bank relue, jamais au navigateur. */
function association_dons_confirmer_bank($id_transaction) {
	$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_transaction=' . (int)$id_transaction);
	$transaction = sql_fetsel('*', 'spip_transactions', 'id_transaction=' . (int)$id_transaction);
	if (!$suivi || !$transaction || $suivi['mode_paiement'] !== 'bank' || $transaction['statut'] !== 'ok' || $transaction['reglee'] !== 'oui' || $transaction['devise'] !== $suivi['devise'] || (float)$transaction['montant_regle'] < (float)$suivi['montant']) { return false; }
	return association_dons_reglement($suivi['id_don_suivi'], 'Bank #' . (int)$id_transaction);
}

/** Point d'entrée privé pour les règlements externes justifiés. */
function association_dons_confirmer_externe($id_suivi, $preuve) {
	if (!autoriser('gerer', 'associationdons')) { return false; }
	$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$id_suivi);
	if (!$suivi || !in_array($suivi['mode_paiement'], array('virement', 'fondation'), true) || trim($preuve) === '' || mb_strlen($preuve) > 255) { return false; }
	return association_dons_reglement($id_suivi, $preuve);
}

/** API interne ; appelée uniquement après preuve Bank ou autorisation privée. */
function association_dons_reglement($id_suivi, $preuve) {
	$verrou = association_dons_verrou('reglement-' . (int)$id_suivi);
	if (!$verrou) { return false; }
	try {
		$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$id_suivi);
		if (!$suivi) { return false; }
		if ($suivi['statut'] === 'regle') {
			return sql_updateq('spip_asso_donateurs_periodes', array('statut' => 'a_jour'), 'id_donateur_periode=' . (int)$suivi['id_donateur_periode']) !== false;
		}
		$id_compte = 0;
		// Un don à la Fondation n'est pas une recette sur le compte bancaire de l'association.
		if ($suivi['mode_paiement'] !== 'fondation' && !empty($GLOBALS['association_metas']['comptes'])) {
			$config = association_dons_configuration();
			if (empty($config['journal_dons']) || empty($GLOBALS['association_metas']['pc_dons'])) { return false; }
			$id_compte = (int)sql_getfetsel('id_compte', 'spip_asso_comptes', "objet='don' AND id_objet=" . (int)$suivi['id_don']);
			if (!$id_compte) {
				include_spip('inc/comptes');
				$id_compte = (int)inserer_compte(date('Y-m-d'), $suivi['montant'], 0, 'Don Ami #' . (int)$suivi['id_don'], $GLOBALS['association_metas']['pc_dons'], $config['journal_dons'], 0, $suivi['id_don'], 'don', '', 0, null, $suivi['id_transaction'] ?: null);
			}
			if (!$id_compte) { return false; }
		}
		if (sql_updateq('spip_asso_donateurs_periodes', array('statut' => 'a_jour'), 'id_donateur_periode=' . (int)$suivi['id_donateur_periode']) === false) { return false; }
		if (sql_updateq('spip_asso_dons_suivi', array('statut' => 'regle', 'id_compte' => $id_compte, 'preuve_reglement' => $preuve, 'date_reglement' => date('Y-m-d H:i:s')), 'id_don_suivi=' . (int)$id_suivi) === false) { return false; }
		pipeline('association_dons_evenement', array('args' => array('evenement' => 'soutien_regle', 'id_donateur' => (int)$suivi['id_donateur'], 'id_don_suivi' => (int)$id_suivi), 'data' => array()));
		return true;
	} finally { spip_fclose_unlock($verrou); }
}

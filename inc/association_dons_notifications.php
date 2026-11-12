<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons_soutiens');

function association_dons_programmer_notification($type, $id_donateur, $id_suivi = 0, $annee = 0) {
	if (association_dons_configuration()['notifications'] !== 'oui' || !in_array($type, array('soutien_cree', 'soutien_regle', 'soutien_regle_association', 'rappel_paiement', 'renouvellement'), true)) { return 0; }
	if ($type === 'soutien_regle_association' && !filter_var(association_dons_configuration()['email_secretariat'], FILTER_VALIDATE_EMAIL)) { return 0; }
	$donateur = association_dons_donateur($id_donateur);
	if (!$donateur || $donateur['archive'] === 'oui') { return 0; }
	$cle = $type . ':' . (int)$id_donateur . ':' . (int)$id_suivi . ':' . (int)$annee;
	$verrou = association_dons_verrou('notification-' . $cle);
	if (!$verrou) { return 0; }
	try {
		$id = (int)sql_getfetsel('id_don_notification', 'spip_asso_dons_notifications', 'cle_evenement=' . sql_quote($cle));
		if ($id) { return $id; }
		$id = (int)sql_insertq('spip_asso_dons_notifications', array('cle_evenement' => $cle, 'type_notification' => $type, 'id_donateur' => (int)$id_donateur, 'id_don_suivi' => (int)$id_suivi, 'annee' => (int)$annee, 'date_creation' => date('Y-m-d H:i:s')));
		if ($id && !(defined('_ASSOCIATION_DONS_CAPTURE_EMAILS') && _ASSOCIATION_DONS_CAPTURE_EMAILS)) {
			include_spip('inc/queue');
			queue_add_job('association_dons_envoyer_notification', 'Notification de soutien annuel', array($id), 'inc/association_dons_notifications', true);
		}
		return $id;
	} finally { spip_fclose_unlock($verrou); }
}

function association_dons_message_notification($notification) {
	$donateur = association_dons_donateur($notification['id_donateur']);
	if (!$donateur || $donateur['archive'] === 'oui' || !filter_var($donateur['email'], FILTER_VALIDATE_EMAIL)) { return array(); }
	$type = $notification['type_notification'];
	$suivi = $notification['id_don_suivi'] ? sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$notification['id_don_suivi']) : array();
	if ($type !== 'renouvellement' && (!$suivi || (int)$suivi['id_donateur'] !== (int)$donateur['id_donateur'])) { return array(); }
	if (($type === 'rappel_paiement' && $suivi['statut'] !== 'attente') || (in_array($type, array('soutien_regle', 'soutien_regle_association'), true) && $suivi['statut'] !== 'regle')) { return array(); }
	$annee = (int)$notification['annee'];
	if ($type === 'renouvellement') {
		$periode = sql_fetsel('*', 'spip_asso_donateurs_periodes', 'id_donateur=' . (int)$donateur['id_donateur'] . ' AND annee=' . $annee);
		if ($periode || $annee < (int)date('Y') || $annee > (int)date('Y') + 1) { return array(); }
		$url = generer_url_public('association_dons', 'id_donateur=' . (int)$donateur['id_donateur'] . '&annee=' . $annee . '&jeton=' . rawurlencode(association_dons_jeton_renouvellement($donateur['id_donateur'], $annee)), true, false);
	} else {
		$url = association_dons_url_suivi($suivi);
		$annee = (int)sql_getfetsel('annee', 'spip_asso_donateurs_periodes', 'id_donateur_periode=' . (int)$suivi['id_donateur_periode']);
	}
	$association = $type === 'soutien_regle_association';
	$destinataire = $association ? association_dons_configuration()['email_secretariat'] : $donateur['email'];
	if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) { return array(); }
	if ($association) { $url = url_absolue(generer_url_ecrire('association_donateur', 'id_donateur=' . (int)$donateur['id_donateur'])); }
	$langue = $association ? ($GLOBALS['meta']['langue_site'] ?? 'fr') : $donateur['langue'];
	$langue = in_array($langue, array('fr', 'en'), true) ? $langue : 'fr';
	/* Langue du contact : langue du site ; langue du payeur : celle de sa fiche. */
	lang_select($langue);
	try {
		$args = array('annee' => $annee, 'montant' => $suivi ? number_format((float)$suivi['montant'], 2, ',', ' ') . ' ' . $suivi['devise'] : '', 'url' => str_replace('&amp;', '&', $url), 'site' => textebrut($GLOBALS['meta']['nom_site'] ?? ''));
		$args += array('reference' => (int)($suivi['id_don_suivi'] ?? 0), 'nom' => trim($donateur['prenom'] . ' ' . $donateur['nom']), 'email' => $donateur['email'], 'organisation' => $donateur['raison_sociale'], 'mode' => _T('association_dons:paiement_' . ($suivi['mode_paiement'] ?? 'bank')));
		return array('destinataire' => $destinataire, 'sujet' => _T('association_dons:mail_' . $type . '_sujet', $args), 'texte' => _T('association_dons:mail_' . $type . '_texte', $args));
	} finally { lang_select(); }
}

/** Un résultat transport incertain n'est jamais rejoué automatiquement. */
function association_dons_envoyer_notification($id_notification) {
	if (association_dons_configuration()['notifications'] !== 'oui') { return false; }
	$verrou = association_dons_verrou('envoi-' . (int)$id_notification);
	if (!$verrou) { return false; }
	try {
		$n = sql_fetsel('*', 'spip_asso_dons_notifications', 'id_don_notification=' . (int)$id_notification);
		if (!$n || $n['etat'] !== 'prepare') { return false; }
		$message = association_dons_message_notification($n);
		if (!$message) { sql_updateq('spip_asso_dons_notifications', array('etat' => 'annule'), 'id_don_notification=' . (int)$id_notification); return false; }
		if (sql_updateq('spip_asso_dons_notifications', array('etat' => 'en_cours'), 'id_don_notification=' . (int)$id_notification) === false) { return false; }
		if (defined('_ASSOCIATION_DONS_CAPTURE_EMAILS') && _ASSOCIATION_DONS_CAPTURE_EMAILS) {
			$GLOBALS['association_dons_emails_captures'][] = $message;
			$accepte = true;
		} else {
			$envoyer = charger_fonction('envoyer_mail', 'inc');
			$accepte = $envoyer($message['destinataire'], $message['sujet'], $message['texte']);
		}
		$etat = $accepte ? ((defined('_ASSOCIATION_DONS_CAPTURE_EMAILS') && _ASSOCIATION_DONS_CAPTURE_EMAILS) ? 'capture' : 'accepte') : 'incertain';
		sql_updateq('spip_asso_dons_notifications', array('etat' => $etat, 'date_envoi' => $accepte ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00'), 'id_don_notification=' . (int)$id_notification);
		return (bool)$accepte;
	} finally { spip_fclose_unlock($verrou); }
}

function association_dons_preparer_revue($date = null) {
	$date = $date ?: date('Y-m-d');
	$config = association_dons_configuration();
	sql_updateq('spip_asso_donateurs_periodes', array('statut' => 'echu'), "statut='a_jour' AND date_fin<" . sql_quote($date));
	// Réconcilier un règlement déjà confirmé par Bank, par exemple après réparation
	// de la configuration comptable. Aucun nouvel appel au prestataire bancaire.
	foreach (sql_allfetsel('id_transaction', 'spip_asso_dons_suivi', "statut='attente' AND mode_paiement='bank' AND id_transaction>0") as $suivi) {
		association_dons_confirmer_bank($suivi['id_transaction']);
	}
	if ($config['notifications'] !== 'oui') { return; }
	$limite = date('Y-m-d H:i:s', strtotime($date . ' -' . (int)$config['delai_rappel'] . ' days'));
	foreach (sql_allfetsel('id_don_suivi,id_donateur', 'spip_asso_dons_suivi', "statut='attente' AND date_creation<" . sql_quote($limite)) as $suivi) {
		association_dons_programmer_notification('rappel_paiement', $suivi['id_donateur'], $suivi['id_don_suivi']);
	}
	foreach (sql_allfetsel('id_donateur', 'spip_asso_donateurs', "archive='non'") as $d) {
		$periode = sql_fetsel('*', 'spip_asso_donateurs_periodes', 'id_donateur=' . (int)$d['id_donateur'] . " AND statut IN ('a_jour','echu')", '', 'annee DESC');
		if (!$periode || date('Y-m-d', strtotime($periode['date_fin'] . ' -' . (int)$config['preavis_renouvellement'] . ' days')) > $date) { continue; }
		$annee = max((int)substr($date, 0, 4), (int)$periode['annee'] + 1);
		if (substr($date, 0, 4) === '2026' && $annee === 2026) { $annee = 2027; }
		if ($annee > (int)date('Y') + 1 || sql_countsel('spip_asso_donateurs_periodes', 'id_donateur=' . (int)$d['id_donateur'] . ' AND annee=' . $annee)) { continue; }
		association_dons_programmer_notification('renouvellement', $d['id_donateur'], 0, $annee);
	}
	if (!(defined('_ASSOCIATION_DONS_CAPTURE_EMAILS') && _ASSOCIATION_DONS_CAPTURE_EMAILS)) {
		include_spip('inc/queue');
		foreach (sql_allfetsel('id_don_notification', 'spip_asso_dons_notifications', "etat='prepare'") as $n) {
			queue_add_job('association_dons_envoyer_notification', 'Notification de soutien annuel', array((int)$n['id_don_notification']), 'inc/association_dons_notifications', true);
		}
	}
}

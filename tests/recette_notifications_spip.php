<?php
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
define('_ASSOCIATION_DONS_CAPTURE_EMAILS', true);
include_spip('inc/association_dons_notifications');
$config_avant = lire_config('association_dons', null);
$identite_avant = $GLOBALS['visiteur_session'] ?? array();
$cles = array(); $r = array();
try {
	$config = association_dons_configuration(); $config['notifications'] = 'oui'; $config['fondation'] = 'oui';
	ecrire_config('association_dons', $config);
	$donnees = array('nom' => 'RECETTE EMAILS', 'prenom' => 'Synthétique', 'email' => 'recette-emails@example.invalid', 'type_donateur' => 'particulier', 'consentement' => 'oui');
	$cle = hash('sha256', random_bytes(32)); $cles[] = $cle;
	$resultat = association_dons_creer_soutien($donnees, '40', 'fondation', $cle);
	if (empty($resultat['suivi'])) { throw new RuntimeException('Création de recette impossible.'); }
	$s = $resultat['suivi']; $id = (int)$s['id_donateur'];
	$n = (int)sql_getfetsel('id_don_notification', 'spip_asso_dons_notifications', 'id_donateur=' . $id . " AND type_notification='soutien_cree'");
	$r['notification_creation'] = $n > 0;
	$r['capture_creation'] = association_dons_envoyer_notification($n);
	$r['double_envoi_refuse'] = !association_dons_envoyer_notification($n) && count($GLOBALS['association_dons_emails_captures']) === 1;
	$r['etat_capture_distinct_envoi'] = sql_getfetsel('etat', 'spip_asso_dons_notifications', 'id_don_notification=' . $n) === 'capture';
	$r['destinataire_attendu'] = $GLOBALS['association_dons_emails_captures'][0]['destinataire'] === 'recette-emails@example.invalid';
	$r['contenu_creation'] = strpos($GLOBALS['association_dons_emails_captures'][0]['texte'], '40,00 EUR') !== false && strpos($GLOBALS['association_dons_emails_captures'][0]['texte'], '2027') !== false;
	$GLOBALS['visiteur_session'] = sql_fetsel('id_auteur,nom,statut,webmestre', 'spip_auteurs', "login='blobul_ai'");
	association_dons_confirmer_externe($s['id_don_suivi'], 'RECETTE confirmation Fondation');
	$n_regle = (int)sql_getfetsel('id_don_notification', 'spip_asso_dons_notifications', 'id_donateur=' . $id . " AND type_notification='soutien_regle'");
	$r['capture_confirmation'] = association_dons_envoyer_notification($n_regle);
	$n_rappel = association_dons_programmer_notification('rappel_paiement', $id, $s['id_don_suivi']);
	$r['rappel_annule_si_paye'] = !association_dons_envoyer_notification($n_rappel) && sql_getfetsel('etat', 'spip_asso_dons_notifications', 'id_don_notification=' . $n_rappel) === 'annule';
	sql_updateq('spip_asso_donateurs_periodes', array('annee' => 2025, 'date_debut' => '2025-01-01', 'date_fin' => '2025-12-31', 'statut' => 'a_jour'), 'id_donateur_periode=' . (int)$s['id_donateur_periode']);
	association_dons_preparer_revue(); association_dons_preparer_revue();
	$r['expiration'] = sql_getfetsel('statut', 'spip_asso_donateurs_periodes', 'id_donateur_periode=' . (int)$s['id_donateur_periode']) === 'echu';
	$r['invitation_unique'] = sql_countsel('spip_asso_dons_notifications', 'id_donateur=' . $id . " AND type_notification='renouvellement'") === 1;
	$invitation = sql_fetsel('*', 'spip_asso_dons_notifications', 'id_donateur=' . $id . " AND type_notification='renouvellement'");
	$r['capture_renouvellement'] = association_dons_envoyer_notification($invitation['id_don_notification']);
	$cle = hash('sha256', random_bytes(32)); $cles[] = $cle;
	$annee = (int)$invitation['annee'];
	$renouvellement = array('id_donateur' => $id, 'annee' => $annee, 'jeton' => 'invalide');
	$r['renouvellement_sans_jeton_refuse'] = !empty(association_dons_creer_soutien(array(), '100', 'fondation', $cle, $renouvellement)['erreur']);
	$renouvellement['jeton'] = association_dons_jeton_renouvellement($id, $annee);
	$nouveau = association_dons_creer_soutien(array(), '100', 'fondation', $cle, $renouvellement);
	$r['renouvellement_meme_donateur'] = ($nouveau['suivi']['id_donateur'] ?? 0) == $id;
	$r['deux_dons_meme_ami'] = sql_countsel('spip_asso_dons_suivi', 'id_donateur=' . $id) === 2;
	$messages = $GLOBALS['association_dons_emails_captures'];
	$r['emails_uniquement_captures'] = count($messages) === 3;
	foreach ($messages as $m) { if ($m['destinataire'] !== 'recette-emails@example.invalid') { $r['emails_uniquement_captures'] = false; } }
} finally {
	$donateurs = array();
	foreach (sql_allfetsel('*', 'spip_asso_dons_suivi', sql_in('cle_creation', $cles)) as $s) {
		$donateurs[] = (int)$s['id_donateur'];
		sql_delete('spip_asso_dons', 'id_don=' . (int)$s['id_don'] . " AND bienfaiteur='Synthétique RECETTE EMAILS'");
		sql_delete('spip_asso_dons_suivi', 'id_don_suivi=' . (int)$s['id_don_suivi']);
	}
	if ($donateurs) {
		sql_delete('spip_asso_dons_notifications', sql_in('id_donateur', $donateurs));
		sql_delete('spip_asso_donateurs_periodes', sql_in('id_donateur', $donateurs));
		sql_delete('spip_asso_donateurs', sql_in('id_donateur', $donateurs) . " AND nom='RECETTE EMAILS'");
	}
	if ($config_avant === null) { effacer_config('association_dons'); } else { ecrire_config('association_dons', $config_avant); }
	$GLOBALS['visiteur_session'] = $identite_avant;
}
echo json_encode($r, JSON_UNESCAPED_UNICODE);

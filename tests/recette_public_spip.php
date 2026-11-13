<?php
if (!defined('_ECRIRE_INC_VERSION') || strpos($GLOBALS['meta']['adresse_site'] ?? '', 'dev.blobul.com') === false) { throw new RuntimeException('Recette réservée à DEV.'); }
include_spip('formulaires/devenir_ami'); include_spip('public/assembler'); include_spip('inc/filtres');
$config_avant = lire_config('association_dons', null);
$identite_avant = $GLOBALS['visiteur_session'] ?? array();
$GLOBALS['visiteur_session'] = array();
$cle = ''; $r = array();
try {
	$config = association_dons_configuration(); $config['url_confidentialite'] = 'https://example.invalid/confidentialite'; $config['virement'] = 'oui'; $config['notifications'] = 'non'; $config['journal_dons'] = '101'; $config['imputation_dons'] = '758';
	ecrire_config('association_dons', $config);
	$charger = formulaires_devenir_ami_charger_dist();
	$cle = $charger['cle_demande'];
	foreach (array('cle_demande', 'horodatage', 'signature_demande') as $champ) { set_request($champ, $charger[$champ]); }
	foreach (array('nom' => 'RECETTE FORMULAIRE', 'prenom' => 'Synthétique', 'email' => 'recette-formulaire@example.invalid', 'type_donateur' => 'particulier', 'publication_nom' => 'non', 'consentement' => 'oui', 'montant' => '40', 'mode_paiement' => 'virement') as $champ => $valeur) { set_request($champ, $valeur); }
	$r['validation_anonyme'] = !formulaires_devenir_ami_verifier_dist();
	set_request('consentement', ''); $r['consentement_obligatoire'] = isset(formulaires_devenir_ami_verifier_dist()['consentement']); set_request('consentement', 'oui');
	set_request('signature_demande', 'invalide'); $r['signature_invalide_refusee'] = isset(formulaires_devenir_ami_verifier_dist()['message_erreur']); set_request('signature_demande', $charger['signature_demande']);
	set_request('montant', '1'); $r['montant_insuffisant_refuse'] = isset(formulaires_devenir_ami_verifier_dist()['montant']); set_request('montant', '40');
	set_request('montant', 'libre'); set_request('montant_libre', '1');
	$r['montant_libre_insuffisant_refuse'] = isset(formulaires_devenir_ami_verifier_dist()['montant']);
	set_request('montant', '40');
	$r['montant_libre_inactif_ignore'] = !formulaires_devenir_ami_verifier_dist();
	$traitement = formulaires_devenir_ami_traiter_dist();
	$r['traitement_anonyme'] = !empty($traitement['redirect']);
	$r['double_traitement'] = formulaires_devenir_ami_traiter_dist()['redirect'] === ($traitement['redirect'] ?? '');
	$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'cle_creation=' . sql_quote($cle));
	if (!$suivi) { throw new RuntimeException('Suivi de recette absent.'); }
	$jeton = association_dons_jeton_suivi($suivi);
	$html = recuperer_fond('content/association_dons_paiement', array('id_suivi' => $suivi['id_don_suivi'], 'jeton' => $jeton));
	$r['page_paiement'] = strpos($html, '40,00') !== false;
	$r['aucune_identite_dans_paiement'] = strpos($html, 'recette-formulaire@example.invalid') === false && strpos($html, 'RECETTE FORMULAIRE') === false;
	$interdit = recuperer_fond('content/association_dons_paiement', array('id_suivi' => $suivi['id_don_suivi'], 'jeton' => 'invalide'));
	$r['acces_sans_jeton_refuse'] = strpos($interdit, '40,00') === false;
	$formulaire = recuperer_fond('formulaires/devenir_ami', $charger);
	$r['formulaire_rendu'] = strpos($formulaire, 'name="montant"') !== false && strpos($formulaire, 'name="consentement"') !== false;
	$r['aucune_validation_par_url'] = sql_getfetsel('statut', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$suivi['id_don_suivi']) === 'attente';
	$r['erreurs_squelettes'] = count($GLOBALS['tableau_des_erreurs'] ?? array());
	lang_select('en');
	try { $r['formulaire_anglais'] = strpos(recuperer_fond('content/association_dons', array('lang' => 'en')), 'Become a Friend') !== false; }
	finally { lang_select(); }
} finally {
	$s = $cle ? sql_fetsel('*', 'spip_asso_dons_suivi', 'cle_creation=' . sql_quote($cle)) : array();
	if ($s) {
		sql_delete('spip_asso_dons_suivi', 'id_don_suivi=' . (int)$s['id_don_suivi']);
		sql_delete('spip_asso_dons', 'id_don=' . (int)$s['id_don'] . " AND bienfaiteur='Synthétique RECETTE FORMULAIRE'");
		sql_delete('spip_asso_donateurs_periodes', 'id_donateur=' . (int)$s['id_donateur']);
		sql_delete('spip_asso_donateurs', 'id_donateur=' . (int)$s['id_donateur'] . " AND nom='RECETTE FORMULAIRE'");
	}
	if ($config_avant === null) { effacer_config('association_dons'); } else { ecrire_config('association_dons', $config_avant); }
	$GLOBALS['visiteur_session'] = $identite_avant;
}
echo json_encode($r, JSON_UNESCAPED_UNICODE);

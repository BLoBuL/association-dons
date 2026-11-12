<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons_soutiens');

function formulaires_devenir_ami_charger_dist($id_donateur = 0, $annee = 0, $jeton = '') {
	$config = association_dons_configuration();
	if (!$config['url_confidentialite'] || (!empty($GLOBALS['association_metas']['comptes']) && (empty($config['journal_dons']) || empty($config['imputation_dons'])))) { return '<p class="notice">' . _T('association_dons:configuration_incomplete') . '</p>'; }
	if ((int)$id_donateur && !association_dons_renouvellement_valide($id_donateur, (int)$annee, $jeton)) { return '<p class="error">' . _T('association_dons:lien_invalide') . '</p>'; }
	$cle = _request('cle_demande') ?: hash('sha256', random_bytes(32));
	$horodatage = _request('horodatage') ?: time();
	return array('nom' => '', 'prenom' => '', 'raison_sociale' => '', 'type_donateur' => 'particulier', 'email' => '', 'publication_nom' => 'non', 'consentement' => '', 'montant' => '', 'montant_libre' => '', 'mode_paiement' => 'bank', 'cle_demande' => $cle, 'horodatage' => $horodatage, 'signature_demande' => calculer_cle_action('association-dons-formulaire-' . $cle . '-' . $horodatage), '_config' => $config, '_montants' => explode(';', $config['montants']), '_renouvellement' => (int)$id_donateur > 0);
}

function association_dons_saisie_publique() {
	$donnees = array();
	foreach (array('nom', 'prenom', 'raison_sociale', 'type_donateur', 'email', 'publication_nom', 'consentement') as $champ) { $donnees[$champ] = trim((string)_request($champ)); }
	return $donnees;
}

function formulaires_devenir_ami_verifier_dist($id_donateur = 0, $annee = 0, $jeton = '') {
	$config = association_dons_configuration();
	$erreurs = array();
	$cle = (string)_request('cle_demande'); $horodatage = (string)_request('horodatage');
	$signature = calculer_cle_action('association-dons-formulaire-' . $cle . '-' . $horodatage);
	if (!preg_match('/^[a-f0-9]{64}$/D', $cle) || !ctype_digit($horodatage) || (int)$horodatage < time() - 86400 || (int)$horodatage > time() + 60 || !hash_equals($signature, (string)_request('signature_demande')) || _request('site_web')) { return array('message_erreur' => _T('association_dons:demande_expiree')); }
	if (!$config['url_confidentialite']) { return array('message_erreur' => _T('association_dons:configuration_incomplete')); }
	if ((int)$id_donateur) {
		if (!association_dons_renouvellement_valide($id_donateur, (int)$annee, $jeton)) { return array('message_erreur' => _T('association_dons:lien_invalide')); }
	} else {
		$erreurs = association_dons_identite_verifier(association_dons_saisie_publique());
		foreach ($erreurs as &$erreur) { $erreur = _T('association_dons:erreur_champ'); }
	}
	if (_request('consentement') !== 'oui') { $erreurs['consentement'] = _T('association_dons:erreur_champ'); }
	$montant = _request('montant') === 'libre' ? _request('montant_libre') : _request('montant');
	$centimes = association_dons_centimes($montant);
	if (!$centimes || $centimes < association_dons_centimes($config['minimum'])) { $erreurs['montant'] = _T('association_dons:erreur_montant'); }
	$modes = array('bank'); foreach (array('virement', 'fondation') as $mode) { if ($config[$mode] === 'oui') { $modes[] = $mode; } }
	if (!in_array(_request('mode_paiement'), $modes, true)) { $erreurs['mode_paiement'] = _T('association_dons:erreur_champ'); }
	return $erreurs;
}

function formulaires_devenir_ami_traiter_dist($id_donateur = 0, $annee = 0, $jeton = '') {
	if (formulaires_devenir_ami_verifier_dist($id_donateur, $annee, $jeton)) { return array('message_erreur' => _T('association_dons:erreur_champ')); }
	$montant = _request('montant') === 'libre' ? _request('montant_libre') : _request('montant');
	$renouvellement = $id_donateur ? array('id_donateur' => (int)$id_donateur, 'annee' => (int)$annee, 'jeton' => $jeton) : array();
	$resultat = association_dons_creer_soutien(association_dons_saisie_publique(), $montant, _request('mode_paiement'), _request('cle_demande'), $renouvellement);
	if (empty($resultat['suivi'])) { return array('message_erreur' => _T('association_dons:erreur_demande')); }
	return array('message_ok' => _T('association_dons:demande_ok'), 'redirect' => association_dons_url_suivi($resultat['suivi']));
}

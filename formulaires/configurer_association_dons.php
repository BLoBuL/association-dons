<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons');
function formulaires_configurer_association_dons_charger_dist() {
	return autoriser('gerer', 'associationdons') ? association_dons_configuration() : false;
}
function formulaires_configurer_association_dons_verifier_dist() {
	if (!autoriser('gerer', 'associationdons')) { return array('message_erreur' => _T('info_acces_interdit')); }
	$erreurs = array();
	foreach (explode(';', (string)_request('montants')) as $montant) {
		$centimes = association_dons_centimes($montant);
		if (!$centimes) { $erreurs['montants'] = _T('association_dons:erreur_champ'); }
	}
	if (!association_dons_centimes(_request('minimum'))) { $erreurs['minimum'] = _T('association_dons:erreur_champ'); }
	if (!preg_match('/^[A-Z]{3}$/D', (string)_request('devise'))) { $erreurs['devise'] = _T('association_dons:erreur_champ'); }
	foreach (array('url_fondation', 'url_confidentialite') as $champ) {
		$url = trim((string)_request($champ));
		if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https')) { $erreurs[$champ] = _T('association_dons:erreur_champ'); }
	}
	if (_request('fondation') === 'oui' && !_request('url_fondation')) { $erreurs['url_fondation'] = _T('association_dons:erreur_champ'); }
	if (_request('virement') === 'oui' && !trim((string)_request('instructions_virement'))) { $erreurs['instructions_virement'] = _T('association_dons:erreur_champ'); }
	if (_request('email_secretariat') && !filter_var(_request('email_secretariat'), FILTER_VALIDATE_EMAIL)) { $erreurs['email_secretariat'] = _T('association_dons:erreur_champ'); }
	foreach (array('preavis_renouvellement', 'delai_rappel') as $champ) {
		if (!ctype_digit((string)_request($champ)) || (int)_request($champ) < 1 || (int)_request($champ) > 365) { $erreurs[$champ] = _T('association_dons:erreur_champ'); }
	}
	return $erreurs;
}
function formulaires_configurer_association_dons_traiter_dist() {
	$erreurs = formulaires_configurer_association_dons_verifier_dist();
	if ($erreurs) { return array('message_erreur' => _T('association_dons:erreur_champ')); }
	$config = array();
	foreach (array_keys(association_dons_configuration()) as $champ) {
		if (in_array($champ, array('virement', 'fondation', 'notifications'), true)) { $config[$champ] = _request($champ) === 'oui' ? 'oui' : 'non'; }
		else { $config[$champ] = trim((string)_request($champ)); }
	}
	ecrire_config('association_dons', $config);
	return array('message_ok' => _T('association_dons:enregistrement_ok'), 'editable' => true);
}

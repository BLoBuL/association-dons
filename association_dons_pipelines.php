<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

function association_dons_bank_traiter_reglement($flux) {
	include_spip('inc/association_dons_soutiens');
	association_dons_confirmer_bank((int)($flux['args']['id_transaction'] ?? 0));
	return $flux;
}

function association_dons_bank_redirige_apres_retour_transaction($flux) {
	include_spip('inc/association_dons_soutiens');
	$id_transaction = (int)($flux['args']['id_transaction'] ?? 0);
	$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_transaction=' . $id_transaction . ' AND id_transaction>0');
	$mode = $flux['args']['row']['mode'] ?? '';
	if ($suivi && !(strpos($mode,'fondation_roibaudouin/')===0 && ($flux['args']['succes']??null)==='wait')) { $flux['data'] = association_dons_url_suivi($suivi); }
	return $flux;
}

function association_dons_taches_generales_cron($taches) {
	$taches['association_dons_quotidien'] = 86400;
	return $taches;
}

function association_dons_association_dons_evenement($flux) {
	include_spip('inc/association_dons_notifications');
	$type = $flux['args']['evenement'] ?? '';
	if (in_array($type, array('soutien_cree', 'soutien_regle'), true)) {
		association_dons_programmer_notification($type, (int)$flux['args']['id_donateur'], (int)$flux['args']['id_don_suivi']);
		if ($type === 'soutien_regle') {
			association_dons_programmer_notification('soutien_regle_association', (int)$flux['args']['id_donateur'], (int)$flux['args']['id_don_suivi']);
		}
	}
	return $flux;
}

function association_dons_ajouter_menus($menus) {
	if (isset($menus['association']) && autoriser('gerer', 'associationdons')) {
		$classe = class_exists('Spip\\Admin\\Bouton') ? 'Spip\\Admin\\Bouton' : 'Bouton';
		$menus['association']->sousmenu['association_donateurs'] = new $classe(find_in_theme('images/auteur-xx.svg'), _T('association_dons:titre_donateurs'), generer_url_ecrire('association_donateurs'));
	}
	return $menus;
}

function association_dons_afficher_contenu_objet($flux) {
	if (($flux['args']['type'] ?? '') === 'organisation' && test_plugin_actif('contacts') && autoriser('gerer', 'associationdons')) {
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/association_dons_organisation_fiche', array('id_organisation' => (int)$flux['args']['id_objet']));
	}
	return $flux;
}

/** Libellé métier natif Bank : un don autonome ne doit pas devenir une adhésion. */
function association_dons_bank_description_transaction($flux) {
 $id = (int)($flux['args']['id_transaction'] ?? 0);
 if (!$id) { return $flux; }
 $s = sql_fetsel('id_don_suivi,id_donateur_periode', 'spip_asso_dons_suivi', 'id_transaction=' . $id . " AND mode_paiement='bank'");
 $auteur = $s ? sql_getfetsel('auteur_id', 'spip_transactions', 'id_transaction=' . $id) : '';
 if (!$s || $auteur !== 'association_dons:' . $s['id_don_suivi']) { return $flux; }
 $annee = sql_getfetsel('annee', 'spip_asso_donateurs_periodes', 'id_donateur_periode=' . (int)$s['id_donateur_periode']);
 $flux['data']['libelle'] = _T('association_dons:bank_don_libelle', array('reference' => $s['id_don_suivi']));
 $flux['data']['description'] = _T('association_dons:bank_don_description', array('annee' => $annee));
 return $flux;
}

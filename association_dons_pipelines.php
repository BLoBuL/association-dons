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
	if ($suivi) { $flux['data'] = association_dons_url_suivi($suivi); }
	return $flux;
}

<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

function association_dons_bank_traiter_reglement($flux) {
	include_spip('inc/association_dons_soutiens');
	association_dons_confirmer_bank((int)($flux['args']['id_transaction'] ?? 0));
	return $flux;
}

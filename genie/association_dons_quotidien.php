<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
function genie_association_dons_quotidien_dist($date) {
	include_spip('inc/association_dons_notifications');
	association_dons_preparer_revue();
	return 1;
}

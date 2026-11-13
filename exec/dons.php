<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }

/** Déléguer la page historique au contrôleur natif des squelettes privés. */
function exec_dons_dist() {
 include_spip('exec/fond');
}

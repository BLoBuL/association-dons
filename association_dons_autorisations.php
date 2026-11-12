<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
function association_dons_autoriser() {}

function autoriser_associationdons_gerer_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] ?? '') === '0minirezo' && empty($qui['restreint']);
}
function autoriser_associationdonateurs_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('gerer', 'associationdons', 0, $qui, $opt);
}
function autoriser_associationdonateurs_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('gerer', 'associationdons', 0, $qui, $opt);
}
function autoriser_associationdonateur_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('gerer', 'associationdons', 0, $qui, $opt);
}
function autoriser_configurer_associationdons_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('gerer', 'associationdons', 0, $qui, $opt);
}

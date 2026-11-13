<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons_soutiens');
function formulaires_confirmer_association_don_charger_dist($id_suivi) {
	if (!autoriser('gerer', 'associationdons')) { return false; }
	$suivi = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$id_suivi);
	return association_dons_confirmation_manuelle_possible($id_suivi) ? array('preuve_reglement' => '', 'editable'=>true, '_saisies'=>formulaires_confirmer_association_don_saisies_dist($id_suivi)) : false;
}
function formulaires_confirmer_association_don_verifier_dist($id_suivi) {
	if (!autoriser('gerer', 'associationdons')) { return array('message_erreur' => _T('info_acces_interdit')); }
	$preuve = trim((string)_request('preuve_reglement'));
	return $preuve !== '' && mb_strlen($preuve) <= 255 ? array() : array('preuve_reglement' => _T('association_dons:erreur_champ'));
}
function formulaires_confirmer_association_don_traiter_dist($id_suivi) {
	return association_dons_confirmer_externe($id_suivi, trim((string)_request('preuve_reglement'))) ? array('message_ok' => _T('association_dons:reglement_ok')) : array('message_erreur' => _T('association_dons:erreur_reglement'));
}

function formulaires_confirmer_association_don_saisies_dist($id_suivi) {
 return array(array('saisie'=>'input','options'=>array('nom'=>'preuve_reglement','id'=>'preuve_reglement_'.(int)$id_suivi,'label'=>_T('association_dons:preuve_reglement'),'maxlength'=>255,'obligatoire'=>'oui')));
}

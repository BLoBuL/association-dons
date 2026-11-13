<?php
if (!defined('_ECRIRE_INC_VERSION')) { return; }
include_spip('inc/association_dons_notifications');
function formulaires_relancer_association_don_charger_dist($id_suivi) {
	if (!autoriser('gerer', 'associationdons')) { return false; }
	$s = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$id_suivi);
	return $s && $s['statut'] === 'attente' ? array('confirmer' => '', 'editable'=>true, '_saisies'=>formulaires_relancer_association_don_saisies_dist($id_suivi)) : false;
}
function formulaires_relancer_association_don_verifier_dist($id_suivi) {
	if (!autoriser('gerer', 'associationdons')) { return array('message_erreur' => _T('info_acces_interdit')); }
	return _request('confirmer') === 'oui' ? array() : array('confirmer' => _T('association_dons:erreur_champ'));
}
function formulaires_relancer_association_don_traiter_dist($id_suivi) {
	if (formulaires_relancer_association_don_verifier_dist($id_suivi)) { return array('message_erreur' => _T('association_dons:erreur_champ')); }
	$s = sql_fetsel('*', 'spip_asso_dons_suivi', 'id_don_suivi=' . (int)$id_suivi);
	if (!$s || $s['statut'] !== 'attente') { return array('message_erreur' => _T('association_dons:erreur_champ')); }
	$id = association_dons_programmer_notification('rappel_paiement', $s['id_donateur'], $id_suivi);
	return $id ? array('message_ok' => _T('association_dons:relance_presente')) : array('message_erreur' => _T('association_dons:notifications_desactivees'));
}

function formulaires_relancer_association_don_saisies_dist($id_suivi) {
 return array(array('saisie'=>'case','options'=>array('nom'=>'confirmer','label_case'=>_T('association_dons:confirmer_relance'),'valeur_oui'=>'oui','valeur_non'=>'non','obligatoire'=>'oui')));
}

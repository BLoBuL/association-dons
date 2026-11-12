# Guide d’utilisation — Dons et Amis

## Préparer le module

Un administrateur non restreint accède à **Association → Donateurs**, puis à la configuration. Accès direct : `ecrire/?exec=association_donateurs` et `ecrire/?exec=configurer_association_dons`.

1. Renseigner les montants proposés, séparés par des points-virgules, et le minimum du montant libre. Valeurs initiales : 40 et 100 EUR, minimum 40 EUR. Les montants proposés doivent respecter le minimum. Deux décimales maximum ; la devise doit être reconnue par Bank et utiliser deux décimales.
2. Fournir l’URL HTTPS réelle de la politique de confidentialité. Le formulaire reste indisponible si ce préalable manque.
3. Si la comptabilité d’Association est activée, renseigner le journal et l’imputation des dons propres au module. L’imputation native est proposée lorsqu’elle existe. Ces réglages ne modifient pas la configuration des anciens dons d’Association. Faire valider les codes par le responsable comptable.
4. Configurer les moyens de paiement dans Bank. Pour le virement, activer l’option du module et fournir les instructions. Pour la Fondation, activer l’option et fournir son URL HTTPS. Ces deux circuits attendent une confirmation administrative avec preuve.
5. Vérifier les délais : rappel d’un soutien en attente après 14 jours et invitation au renouvellement 60 jours avant échéance par défaut. Les délais sont réglables entre 1 et 365 jours.
6. Activer les notifications seulement après validation de la configuration d’envoi du site et du contenu des messages. Elles sont désactivées par défaut. Le cron et la file de travaux SPIP doivent fonctionner.

Sur DEV, les valeurs juridiques et comptables réelles n’ont pas été inventées. Les réglages synthétiques utilisés pour la recette sont retirés après les tests. Il faut donc terminer cette configuration avant l’ouverture du formulaire aux donateurs.

## Parcours du donateur

Publier un lien vers `spip.php?page=association_dons&lang=fr` ou intégrer le modèle `<devenir_ami>` dans un contenu SPIP. `lang=en` donne accès au formulaire anglais ; les notifications suivent la langue enregistrée du donateur.

Le donateur choisit particulier ou organisation, renseigne son identité et son email, sélectionne un montant proposé ou libre, accepte le traitement de ses données et choisit séparément s’il autorise la publication de son nom. Aucun compte ni connexion n’est créé. Le module mémorise le choix de publication ; il ne publie pas automatiquement un annuaire de noms.

Après validation, une intention de soutien est créée et une page de paiement protégée par un lien signé est proposée. Le parcours Bank présente les moyens disponibles sur le site. Un virement reste en attente de preuve ; le lien Fondation ouvre son site, puis le secrétariat confirme le règlement. Ne pas partager les liens signés : ils donnent accès au paiement ou au renouvellement correspondant.

Deux inscriptions libres utilisant le même email ne sont pas fusionnées automatiquement. Le lien personnel de renouvellement permet de conserver la même fiche, sans connexion et sans transmettre un identifiant de donateur modifiable.

## Gérer les donateurs

La liste permet de rechercher par nom, prénom, email ou organisation et de filtrer les statuts : en attente, à jour, échu ou ancien. La fiche donne accès à l’identité, aux périodes, aux soutiens et au journal des notifications.

- Modifier les coordonnées depuis la fiche. Archiver classe la personne comme ancienne et suspend ses rappels futurs.
- Avec Contacts & Organisations actif, rattacher une organisation existante. Ce lien n’est pas obligatoire et ne crée pas d’organisation automatiquement.
- Pour un virement ou un règlement Fondation, ouvrir le soutien en attente et saisir une référence ou preuve vérifiée avant confirmation. La confirmation crée le don ; ne pas l’utiliser comme simple accusé de réception d’une promesse.
- Un règlement Bank est confirmé automatiquement après contrôle de la transaction. Revenir sur la page de succès ne suffit pas à modifier son statut.
- Le rappel manuel disponible sur la fiche utilise le même journal de déduplication que la revue automatique. Il ne force pas la répétition d’un envoi déjà pris en charge.

Le statut « à jour » dépend d’une période réglée couvrant la date du jour. Le cas de lancement 2026 couvre 2026 et 2027 ; l’invitation suivante concerne 2028. Ensuite, chaque renouvellement couvre son année. Aucun prélèvement récurrent automatique n’est créé.

## Suivre les notifications

Les événements couvrent la création du soutien, sa confirmation, le rappel de paiement et l’invitation au renouvellement. La revue quotidienne met à jour les échéances, prépare les rappels et évite les doubles événements. Un rappel devenu inutile après paiement est annulé.

Le journal distingue la préparation, le traitement en cours, l’acceptation par le transport, la capture de test, l’annulation et un résultat incertain. « Accepté » signifie pris en charge par le transport, pas réception dans la boîte du destinataire. Une capture est un test sans envoi. Un état incertain doit être rapproché des journaux d’envoi avant toute intervention ; le module ne le rejoue pas automatiquement.

## Avant ouverture au client

Vérifier la politique de confidentialité, les montants, la devise, les imputations comptables, les instructions de virement/Fondation, le prestataire Bank et l’envoi réel sur l’environnement autorisé. La recette DEV utilise des identités synthétiques, des emails `.invalid` capturés et aucun paiement réel. Elle ne valide ni un encaissement réel, ni la réception d’un email, ni un régime fiscal ou l’émission d’un reçu fiscal.

## Recette sans paiement ni email

Activer **Mode recette** dans la configuration. Les notifications du module sont alors forcées à « non », même si leur case est cochée. Les intentions Bank ne créent aucune transaction et la page de suivi remplace les paiements et liens externes par un avertissement. Les réglages Bank des autres modules restent inchangés. Utiliser des identités préfixées RECETTE et des emails `.invalid`.

Pour tester le passage à « réglé », choisir le virement dans le formulaire, puis confirmer depuis l’administration avec une preuve « RECETTE — aucun encaissement ». Cela crée des données comptables synthétiques sur DEV. Les intentions Bank créées dans ce mode restent des essais sans transaction ; elles ne doivent pas être réutilisées après désactivation du mode recette.

## Notifications après règlement

Renseigner **Contact de l’association pour les règlements (email)**, puis activer les notifications. Cette adresse est obligatoire à l’activation ; aucun destinataire implicite ni BCC n’est ajouté. Le donateur reçoit la confirmation sur l’email de sa fiche, dans sa langue. Le contact reçoit un message distinct dans la langue du site, avec montant, référence, identité, mode et lien vers la fiche privée (sans lien public signé). Il s’agit d’une notification de règlement confirmé, pas d’un reçu fiscal.

Le déclenchement suit un règlement Bank confirmé ou une confirmation administrative virement/Fondation. Une intention ou un simple retour navigateur ne déclenche aucune confirmation de règlement. Les deux messages sont journalisés séparément et dédupliqués ; un résultat de transport incertain n’est pas rejoué automatiquement. Le mode recette continue de désactiver tous les emails réels du module.

## Configuration par rubriques

La page reprend la configuration d’Association : navigation latérale, formulaires Saisies, fieldsets et aides. Les six rubriques sont Mode recette et ouverture, Montants et devise, Moyens de paiement, Comptabilité, Protection des données, Notifications et rappels. Chaque rubrique enregistre uniquement ses champs et conserve les autres réglages. Le lien vers Bank ouvre sa configuration sans recopier ni modifier ses secrets.

Le choix de notifications enregistré reste visible pendant la recette ; les envois sont toutefois neutralisés jusqu’à sa désactivation. La sortie du mode recette exige une politique de confidentialité et, si la comptabilité est active, les deux codes comptables. Un récapitulatif nomme les champs manquants et permet de les compléter dans leur rubrique avant de réessayer.

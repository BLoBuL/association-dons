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

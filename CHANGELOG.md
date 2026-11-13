# Journal des modifications

## [À venir]

- Recette externe Stripe sandbox et réceptions réelles des notifications ; correction du montant libre inactif, de la mise en forme HTML des emails et du libellé Bank des dons autonomes.

- Notification de règlement confirmé au contact configuré de l’association, distincte de la confirmation au donateur : journaux et déduplication séparés, lien administratif sans jeton public, textes FR/EN. Adresse de contact obligatoire pour activer les notifications.

- Formulaire public découpé en inclusions, classes SPIP/Saisies des champs et choix ; suppression de la feuille CSS spécifique et de son chargement global. Surcharge de composition possible dans le Core sans dupliquer les champs.

- Mode recette du module : aucun bouton prestataire ni lien de paiement externe, aucune transaction Bank créée et notifications forcées à non, sans modifier Bank pour les autres modules.

- Compatibilité avec Association 6.x et Bank, intégration Contacts & Organisations facultative, sans redéclaration des anciens dons.
- Schéma additif 1.4.0 : donateurs indépendants des adhérents, périodes, suivi des intentions/règlements et journal des notifications. Conservation des données à la désinstallation du module.
- Formulaire public FR/EN sans compte, consentement et choix de publication, montants paramétrables et montant libre, liens signés de paiement et renouvellement.
- Liste et fiches privées, recherche, statuts calculés, archivage, organisation facultative et configuration comptable propre au module.
- Intégration Bank avec confirmation vérifiée et idempotente ; don historique créé seulement après règlement. Virement et Fondation confirmés par administrateur avec preuve ; aucune recette bancaire locale pour la Fondation.
- Soutien 2026 valable jusqu’à fin 2027, puis renouvellements annuels ; notifications journalisées, rappels et revue quotidienne sans doublons ni prélèvement automatique.
- Recettes PHP, MariaDB et SPIP, callbacks concurrents et contrôles publics ordinateur/mobile sur DEV. Guide d’utilisation et rapport de recette précisant les limites des tests.

## Historique antérieur

L’historique antérieur au 5 octobre 2026 n’est pas reconstitué ici. Il reste consultable dans les commits, tags et releases Git du dépôt.

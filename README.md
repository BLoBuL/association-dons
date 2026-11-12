# Association — Dons et Amis

Module autonome pour SPIP 4 / PHP 8, complémentaire d’Association 6.x (à partir de 6.9.11) et de Bank (à partir de 6.4). Contacts & Organisations (`contacts` 5.3.2 ou supérieur) est facultatif. Développement sur la branche `codex/donateurs-schema-association`, sans release ni installation client.

Le module gère les donateurs sans compte adhérent, les intentions de soutien, leur règlement et leur renouvellement annuel. Il fournit un formulaire public FR/EN, une liste et des fiches privées, des montants paramétrables, des confirmations manuelles et des notifications journalisées.

## Utilisation

Voir le [guide d’utilisation](GUIDE-UTILISATION.md) pour la configuration, le parcours public et les opérations administratives. La [recette DEV](RECETTE-DEV.md) distingue les tests serveur, les contrôles visuels et les limites de validation.

## Données et conservation

Association conserve la propriété de `spip_asso_dons` et de sa comptabilité. Le module ne redéclare pas cette table et ne déduit aucune identité du champ historique `bienfaiteur`.

| Table complémentaire | Responsabilité |
| --- | --- |
| `spip_asso_donateurs` | Identité, langue, consentement, publication du nom, archivage et organisation facultative ; aucun adhérent requis. |
| `spip_asso_donateurs_periodes` | Période de soutien, unique par donateur et année. |
| `spip_asso_dons_suivi` | Intention, montant et devise, transaction Bank éventuelle, preuve de règlement et liens vers don/période/comptabilité. `id_don` reste NULL avant règlement. |
| `spip_asso_dons_notifications` | Journal des événements et de leur prise en charge, avec clé de déduplication. |

Le schéma 1.4.0 utilise les mises à niveau natives `maj_plugin`. Installation et migration sont additives ; les dons historiques restent inchangés. La désinstallation de ce module conserve ses données et retire seulement sa métadonnée de schéma. La désinstallation destructive d’Association possède ses propres effets et ne relève pas de cette protection.

Une intention en attente ne crée aucun ancien don ni écriture comptable. Après règlement confirmé, le module crée le don historique et, pour Bank/virement, l’écriture via l’API native d’Association. La confirmation Fondation crée le don mais aucune recette bancaire locale, les fonds étant reçus par la Fondation. Un soutien réglé en 2026 couvre jusqu’au 31 décembre 2027 ; les périodes suivantes sont annuelles.

## Intégrations

- Déclaration des seules tables complémentaires par les pipelines SPIP ; menu et affichages privés par les points d’extension natifs.
- Règlement et retour Bank : relecture de la transaction, validation du rattachement, du statut réglé, du montant et de la devise. Un simple retour navigateur ne confirme aucun règlement.
- Événements métier via `association_dons_evenement`, traitement quotidien via le cron SPIP, courriels via la file native.
- Contacts actif : choix d’une organisation existante et affichage des donateurs sur sa fiche. Sans Contacts, aucune requête sur ses tables n’est nécessaire.
- CSS public limité au formulaire ; route Zcore lorsque disponible, page autonome de repli sinon.

Les clés d’intention et d’événement ainsi que les verrous locaux SPIP empêchent les doubles soumissions, règlements et envois. Une préparation incomplète ou un envoi incertain reste traçable et n’est pas automatiquement rejoué. La revue quotidienne reprend les transactions Bank confirmées dont la comptabilisation attendait une configuration valide, sans appeler le prestataire de paiement.

## Vérification et livraison

`php tests/test_regles.php` et `php tests/test_structure.php` contrôlent règles et contrats de schéma. Les recettes `tests/recette_*_spip.php` exigent un SPIP isolé, avec sauvegarde préalable ; certaines modifient temporairement les tables/configurations et ne doivent pas être exécutées sur un site client. Les tests sont exclus des archives par `.gitattributes`.

Avant installation : sauvegarder tables, métadonnées et code ; vérifier les dépendances et le chemin réellement chargé. Le retour arrière du code ne rétrograde pas une base : restaurer ensemble les données/configurations compatibles si nécessaire. La livraison DEV utilise un export Git exact, des sauvegardes privées et la vérification SHA-256 de chaque fichier. Aucune release, archive publique ou livraison client n’est impliquée.

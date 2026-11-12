# Association - Dons

Plugin SPIP 4/PHP 8 complémentaire d’Association 6.x (à partir de 6.9.11) et de Bank 6.4 ou supérieur. Contacts & Organisations (`contacts` 5.3.2 ou supérieur) reste facultatif.

## Responsabilités et compatibilité

Association conserve la déclaration, les écrans, les écritures comptables et les dons de `spip_asso_dons`. Le module ne redéclare plus cette table : sa structure ne dépend donc pas de l’ordre des pipelines. Les pipelines natifs `declarer_tables_principales` et `declarer_tables_interfaces` déclarent les nouvelles tables et leurs alias sans remplacer les déclarations voisines.

Cette première étape fournit le schéma, pas encore les écrans des donateurs, les traitements Bank, les emails ou le calcul des statuts. Aucun pipeline de règlement n’est ajouté tant que le traitement métier correspondant n’est pas implémenté.

## Nouvelles tables — schéma 1.1.0

| Table | Responsabilité |
| --- | --- |
| `spip_asso_donateurs` | Identité et email du donateur, type particulier/organisation, rattachement optionnel, autorisation de publication du nom et date du consentement relatif aux données. Aucun compte adhérent n’est requis. |
| `spip_asso_donateurs_periodes` | Année de soutien, bornes de validité et statut, avec unicité donateur/année. Les bornes permettent notamment de couvrir 2026–2027 dans une seule période de renouvellement 2027. |
| `spip_asso_dons_suivi` | Rattachement d’un don existant au donateur, à sa période et éventuellement à une transaction Bank. Un seul suivi par don ; plusieurs dons possibles par donateur et période. |

Les identifiants optionnels valent zéro en l’absence de liaison. Un suivi exige un véritable `id_don` : il ne sert pas à stocker plusieurs intentions avec `id_don=0`. Le montant reste celui du don et de la transaction, sans copie concurrente dans le suivi. L’email n’est pas une clé unique : les règles d’identification restent à traiter dans l’API métier. Les statuts de période utiliseront `attente`, `a_jour`, `echu` ; l’archivage du donateur porte le classement Ancien.

## Installation et conservation

L’installation neuve et la montée depuis 1.0.0 appellent `maj_tables` sur les seules trois nouvelles tables. Elles n’importent, ne réécrivent et ne suppriment aucun don historique ; aucun donateur n’est inventé à partir du texte libre `bienfaiteur`. Les tables complémentaires restent conservées à la désinstallation du module.

Attention : la désinstallation destructive d’Association possède sa propre suppression historique de `spip_asso_dons`. La conservation assurée ici concerne l’installation, la montée de schéma et la désinstallation d’**association-dons**, pas celle de son plugin requis Association. Avant recette SPIP ou livraison, sauvegarder les tables et la version de schéma ; restaurer le code seul ne rétrograde pas une base.

## Vérification

`php tests/test_structure.php` vérifie les contrats de déclaration et le plan de migration avec un harnais local. Avec `ASSOCIATION_DONS_TEST_PORT` défini, le test crée puis supprime une base synthétique sur un serveur MariaDB local dédié (`127.0.0.1`, utilisateur de recette root sans mot de passe). Ne jamais pointer ce test sur un serveur de données réelles.

Le test MariaDB exécute le DDL déclaré, contrôle la conservation d’un don, son rattachement sans adhérent ni organisation, et les contraintes d’unicité. Il ne remplace pas une recette du moteur `maj_plugin` dans SPIP avec Association et Bank actifs. Cette recette DEV et la compatibilité sur les différentes versions PHP/SPIP annoncées restent à effectuer avant livraison client.

# Première recette DEV — 12 novembre 2026

Code installé : `0acfa06533800edab5a2cd126f5f6fd5f4a232fd`, branche `codex/donateurs-schema-association`.

Cible : `https://dev.blobul.com`, SPIP 4.4.30 / PHP 8.4.27, Association 6.10.27 et Bank 7.2.0. Contacts & Organisations absent : le module s’active sans cette option.

Installation directe dans `plugins/association-dons/`. Les huit fichiers exportés par `git archive` ont une empreinte identique à l’archive transférée (SHA-256 `05a57a858eb03d402be10ae48ed0752b8fa5d9aa9974c1e4e05ed53f3d8de165`). Les tests ne sont pas livrés, conformément à `.gitattributes`. La comparaison porte sur les octets de l’archive Git, qui diffèrent ici des blobs bruts par leurs fins de ligne.

## Résultats serveur

- Activation de `association_dons` avec les dépendances présentes.
- Installation native par `association_dons_upgrade` et `maj_plugin` : schéma 1.1.0, trois tables créées.
- Simulation du passage de la métadonnée 1.0.0 vers 1.1.0, tables déjà présentes : reprise sans perte.
- La table historique était vide avant recette. Un don synthétique inséré avant installation a été comparé intégralement après installation et après reprise : identique.
- Création par l’API SQL SPIP d’un donateur sans adhérent ni organisation, d’une période 2026–2027 et du rattachement au don synthétique : réussite.
- Doublons donateur/année et suivi d’un même don : refus par les contraintes SQL.
- Nombre d’écritures comptables inchangé : 104. Aucun paiement ni email déclenché.
- Nettoyage des quatre enregistrements synthétiques vérifié : table des dons revenue à zéro, trois nouvelles tables vides.
- Cache vidé ; accueil HTTP 200, contrôle `Blobul-Recette-Dons/1.0`, CF-RAY `a5944fe5093bd87b-VIE`.
- Authentification du compte technique dans un profil isolé confirmée par le helper, puis profil supprimé. Le navigateur interactif disponible utilisait un autre profil et affichait la connexion : aucune recette visuelle authentifiée ni capture de nouvel écran revendiquée.

## Limites

Ce lot porte sur le schéma. Les écrans donateurs, notifications et traitements de paiement ne sont pas encore développés. Le test de reprise ne remplace pas une migration depuis une ancienne installation client peuplée. La variante avec Contacts & Organisations actif reste à recetter.

## Retour arrière

État initial et preuves sur l’hébergement, hors webroot : `~/tmp/recette-dons-0acfa06-20261112/` (`avant.json`, `fixture-avant.json`, `recette.json`, `code.tar`).

Le plugin était absent avant installation. Pour revenir à cet état, le désactiver puis retirer seulement son répertoire vérifié. Conserver les tables nouvelles par défaut ; ne restaurer les métadonnées ou supprimer ces tables que dans une restauration ciblée contrôlée, après vérification d’absence de nouvelles données. La désinstallation d’Association n’est pas une procédure de rollback.

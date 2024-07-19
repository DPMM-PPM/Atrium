ILIAS ATRIUM plugin
================================

Tout droits réservés - Marine Nationale - 2023

**Maintenance réalisée par le Pôle Pédagogie de la Marine (Direction du personnel de la Marine)**

Installation
------------
1. Télécharger le code de la branche principale
2. décompresser l'archive
3. Renommer le dossier qui contient l'arborescence Atrium
4. Copier le dossier dans l'installation ILIAS (créer les dossiers si nécessaire):
5. Customizing/global/plugins/Services/Repository/RepositoryObject/
6. Executer la commande 'composer du' à la racine de votre installation ILIAS
7. Aller dans Administration > Extending ILIAS > Plugins
8. Cliquer sur installer
9. Cliquer sur Activer

Il n'y a pas d'écran de configuration pour ce plugin

Usage
-----
Ce plugin permet de créer un objet de type CBT dans ILIAS.
Cet Objet permet de collecter les données inscrites dans un fichier de type *.mpj contenant les données de suivi d'un élève réalisant une formation à distance sur une plateforme de formation ATRIUM, développée par la Marine.
Le transfert des données peut être effectué par l'élève lui même ou bien par le tuteur de formation (à condition qu'il soit propriétaire de l'objet CBT créé).
Il est nécessaire que le champs 'Matricule' soit rempli dans les fiches d'identification des élèves et que cette indication soit identique sur la plateforme ILIAS et sur la plateforme ATRIUM.

Le fichier de données de suivi est totalement crypté.

Une fois les données transmises, le suivi est disponible sur l'onglet progression avec les vues habituelles (utilisateurs, matrice et sommaire).
Une feuille de notes récapitulative peut être imprimée en format Excel.

L'arborescence de la formation suivie est du type matière -> module. Il n'y a aucune limite en nombre de modules et de matières.
Une table de correspondantce sous la forme d'un fichier csv peut être transmise pour remplacer les nom génériques des matières et des modules en noms comprehensibles.


Historique des versions
===============

Les versions de ce plugin compatibles avec les différentes versions d'ILIAS sont présentes dans les différentes branches. La dernière version est dans la branche principale (main).

Version 0.42 (2013-06-03)
* Compatibilité ILIAS 4.4 et 5
  
Version 1.0 (2016-03-14)
* Corrections pour mise en conformité publication Excel
  
Version 2.0 (2023-07-24)
* Corrections pour mise en conformité passage en php7.3 et abandon de l'extention mcrypt
  
* Compatibilité ILIAS 7
  
Version 3.0 (2024-07-19)
* Compatibility with ILIAS 8

<?php
 
// alphanumerical ID of the plugin; never change this
$id = "xatr";
 
// code version; must be changed for all code changes
$version = "3.0";
/*
version 3.0 => mise en conformitÃ© ILIAS Version 8.12 et php version 8.3.8
version 2.0.2 => correction de bug dans l'affichage de la progression en vue Matrice.
version 2.0.1 => modification du composer.json pour prise en compte de l'autoload des classes Ã  partir de la version 7.26 d'ILIAS. Correction de bugs sur un affichage d'alerte en cas de tri sur la colonne 'moyenne'
version 2.0.0 => changement de la mÃ©thode de cryptage suite Ã  l'abandon de mcrypt par php. 
version 1.0.0 => correctif classe excel sur V5.3 - correction sur le calcul de la moyenne - présentation de la progression
version 0.0.45=> mise en conformite 5.3 et php 7.1 - ticket mantis nmr 0000180
version 0.0.44=> mise en conformité 5.2 - Adaptation des class class.ilAtriumLPMatrixTableGUI, class.ilAtriumLPSummaryTableGUI et class.ilAtriumLPUsersTableGUI avec phpexcel
version 0.0.43=> mise en conformité 5.1 (ajout de la fonction uninstallCustom dans ilAtriumPlugin.php)
version 0.0.42=> 
suppression de la page de configuration du plug-in qui est inutile
Mise en commentaire de la balise echo ligne 439 du fichier classe.ilAtriumTrackingData.php
*/
 
// ilias min and max version; must always reflect the versions that should
// run with the plugin
$ilias_min_version = "8.12.0";
$ilias_max_version = "8.99.999";
 
// optional, but useful: Add one or more responsible persons and a contact email
$responsible = "Jean-Paul MANIGAND";
$responsible_mail = "jean-paul.manigand@intradef.defense.gouv.fr";
$learning_progress = true;
?>

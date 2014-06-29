<?php
/**
 * Internationalisation for myextension
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();
$wgMainCacheType = CACHE_ANYTHING;
$wgCacheDirectory = false;
 
/** English
 * @author Stefan Jaeschke
 */
 
 // In dieser Datei werden die Bezeichnungen der SpecialPages definiert und es wird festgelegt, unter welchem Namen sie auf der Seite Special:SpecialPages im Wiki aufgelistet werden.
 // Weiter wird für jede SpecialPage eine Beschreibung ihrer Funktion hinterlegt.
 // Die einzelnen SpecialPages werden weiterhin der Gruppe Collaboration zugeordnet. Unter dieser Gruppe werden sie auf der Seite Special:SpecialPages aufgeführt.
 // Dies erfolgt für alle vorgesehenen Sprachen.
 
 //Darstellung, wenn englische Sprache installiert ist.
 
$messages[ 'en' ] = array(
        'collaboration' => "Collaboration", // Dieser Name erscheint auf der Seite Special:SpecialPages
        'collaboration-desc' => "This Extension is about to measure the collaboration of groups inside the wiki.", //Beschreibung der Funktion
		'comparison' => "Comparison", 
        'comparison-desc' => "Comparison Page where you can compare the collaboration status of a group at different times.",
		'configuration' => "Configuration", 
        'configuration-desc' => "Configuration Page where an admin can assign groups to each user.",
		'installation' => "Installation", 
        'installation-desc' => "Installation page to check, if tables for storing information about collaboration status exists. If not it gives the user the opportunity to create the tables and other requirements to run the extension.",
		'collaboration_-_frequently_asked_questions' => "Collaboration - Frequently Asked Questions", 
        'collaboration_-_frequently_asked_questions-desc' => "Displays the Frequently Asked Questions about the wiki-extension Collaboration.",
		'discussions' => "Discussions",
		'discussions-desc' => "The SpecialPage gives the User the possibility to view all discussion-posts of the group on one site.",
		'specialpages-group-Collaboration' => 'Collaboration'
);
 
/** Message documentation
 * @author Stefan Jaeschke
 */
 
 //Darstellung, wenn deutsche Sprache installiert ist.
 
$messages[ 'de' ] = array(
        'collaboration' => "Collaboration", 
        'collaboration-desc' => "Diese Erweiterung soll die Kollaboration zwischen Mitgliedern einer Gruppe innerhalb des Wikis messen.",
		'comparison' => "Comparison", 
        'comparison-desc' => "Diese Seite dient dem Vergleich des Kollaborationsstatusses einer Gruppe zu unterschiedlichen Zeitpunkten.",
		'configuration' => "Configuration", 
        'configuration-desc' => "Konfigurationsseite, auf welcher den einzelnen Gruppenmitgliedern eine Gruppe zugewiesen werden kann.",
		'installation' => "Installation", 
        'installation-desc' => "Diese Seite überprüft, ob die Tabellen zum abspeichern der Kollaborationsdaten in der Datenbank existiert. Ist dies nicht der Fall, so gibt sie dem User eine Möglichkeit, diese automatisch anlegen zu lassen. Weiterhin wird ein Ordner zum Ablegen der grafischen Visualisierungen angelegt.",
		'collaboration_-_frequently_asked_questions' => "Collaboration - Frequently Asked Questions", 
        'collaboration_-_frequently_asked_questions-desc' => "Auf dieser SpecialPage werden die am häufigsten gestellten Fragen zur Wiki-Extension Collaboration beantwortet.",
		'discussions' => "Discussions",
		'discussions-desc' => "Diese SpecialPage ermöglicht dem Nutzer alle Diskussions-Posts einer Gruppe übersicht auf einer Seite anzuzeigen.",
		'specialpages-group-Collaboration' => 'Collaboration'
);
<?php
# Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/Collaboration/Collaboration.php" );
EOT;
        exit( 1 );
}
 
// Festlegung der Credentials der Wiki-Extension.
 
$wgExtensionCredits[ 'specialpage' ][] = array(
        'path' => __FILE__,
        'name' => 'Collaboration',
        'author' => 'Stefan Jaeschke',
        'url' => 'https://www.mediawiki.org/wiki/Extension:Collaboration', // Unter dieser URL wird die Extension später verfügbar gemacht werden.
        'descriptionmsg' => 'collaboration-desc',
        'version' => '0.1.0',
);
 
$wgAutoloadClasses[ 'SpecialCollaboration' ] = __DIR__ . '/specials/SpecialCollaboration.php'; // Location der SpecialCollaboration Klasse
$wgAutoloadClasses[ 'SpecialComparison' ]    = __DIR__ . '/specials/SpecialComparison.php'; // Location der SpecialComparison Klasse
$wgAutoloadClasses[ 'SpecialConfiguration' ] = __DIR__ . '/specials/SpecialConfiguration.php'; // Location der SpecialConfiguration Klasse
$wgAutoloadClasses[ 'SpecialInstallation' ] = __DIR__ . '/specials/SpecialInstallation.php'; // Location der SpecialInstallation Klasse
$wgAutoloadClasses[ 'SpecialCollaborationFAQ' ] = __DIR__ . '/specials/SpecialCollaborationFAQ.php'; // Location der SpecialCollaborationFAQ Klasse
$wgAutoloadClasses[ 'SpecialDiscussions' ] = __DIR__ . '/specials/SpecialDiscussions.php'; // Location der SpecialDiscussions Klasse

$wgExtensionMessagesFiles[ 'Collaboration' ] = __DIR__ . '/Collaboration.i18n.php'; // Location der Internalisierungsdatei
$wgExtensionMessagesFiles[ 'CollaborationAlias' ] = __DIR__ . '/Collaboration.alias.php'; // Location der Aliasesdatei

//Zuordnung der SpecialPages zur Gruppe Collaboration. Unter dieser werden sie auf der Übersichtsseite über alle SpecialPages gelistet.
$wgSpecialPageGroups[ 'Collaboration' ] = 'Collaboration';
$wgSpecialPageGroups[ 'Comparison' ] = 'Collaboration';
$wgSpecialPageGroups[ 'Configuration' ] = 'Collaboration';
$wgSpecialPageGroups[ 'Installation' ] = 'Collaboration';
$wgSpecialPageGroups[ 'Collaboration - Frequently Asked Questions' ] = 'Collaboration';
$wgSpecialPageGroups[ 'Discussions' ] = 'Collaboration';

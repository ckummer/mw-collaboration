<?php
// Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
        echo <<<EOT
To install Collaboration, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/Collaboration/Collaboration.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits[ 'specialpage' ][] = array(
        'path' => __FILE__,
        'name' => 'Collaboration',
        'author' => array( 'Stefan Jaeschke', 'Christian Kummer' ),
        'url' => '',
        'descriptionmsg' => 'collaboration-desc',
        'version' => '0.2.0',
);

// Register database hook
$wgAutoloadClasses['CollaborationHooks']	= __DIR__ . '/includes/CollaborationHooks.php';
// Register classes
$wgAutoloadClasses['SpecialCollaboration'] 	= __DIR__ . '/specialpages/SpecialCollaboration.php';
$wgAutoloadClasses['SpecialComparison']    	= __DIR__ . '/specialpages/SpecialComparison.php';
$wgAutoloadClasses['SpecialConfiguration'] 	= __DIR__ . '/specialpages/SpecialConfiguration.php';
$wgAutoloadClasses['SpecialDiscussions'] 	= __DIR__ . '/specialpages/SpecialDiscussions.php'; 

// Internationalsation and aliases
$wgExtensionMessagesFiles['Collaboration'] 		= __DIR__ . '/Collaboration.i18n.php';
$wgExtensionMessagesFiles['CollaborationAlias'] = __DIR__ . '/Collaboration.alias.php';

// Add SpecialPages
$wgSpecialPages['Collaboration']	= 'SpecialCollaboration';
$wgSpecialPages['Comparison'] 		= 'SpecialComparison';
$wgSpecialPages['Configuration']    = 'SpecialConfiguration';
$wgSpecialPages['Discussions']     	= 'SpecialDiscussions';

// Add SpecialPages to group
$wgSpecialPageGroups['Collaboration']	= 'collaboration';
$wgSpecialPageGroups['Comparison']		= 'collaboration';
$wgSpecialPageGroups['Configuration']	= 'collaboration';
$wgSpecialPageGroups['Discussions'] 	= 'collaboration';

// Register SQL File
$wgHooks['LoadExtensionSchemaUpdates'][] = 'CollaborationHooks::onLoadExtensionSchemaUpdates';

// Basic permissions
$wgGroupPermissions['sysop']['ttcollaboration'] = true;
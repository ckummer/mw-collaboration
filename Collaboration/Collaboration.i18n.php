<?php
/**
 * Internationalisation for Collaboration
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();
$wgMainCacheType = CACHE_ANYTHING;
$wgCacheDirectory = false;
 
/** English
 * @author Stefan Jaeschke, Christian Kummer
 */ 
$messages[ 'en' ] = array(
        'collaboration' => 'Collaboration',
        'collaboration-desc' => 'This extension helps in evaluating group collaboration.', 
		'comparison' => 'Comparison', 
        'comparison-desc' => 'Compare the group collaboration status of one group at different times.',
		'configuration' => 'Configuration', 
        'configuration-desc' => 'Assign wiki users to groups.',
		'discussions' => 'Discussions',
		'discussions-desc' => 'Display all discussion posts of a group.',
		'specialpages-group-collaboration' => 'Collaboration'
);
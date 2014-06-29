<?php

class CollaborationHooks {
	/**
	 * Default MW-Importer (for MW <=1.16 and MW >= 1.17)
	 * @param Updater $updater
	 * @return boolean true if alright
	 */
	public static function onLoadExtensionSchemaUpdates ( $updater = null ) {
		$updater->addExtensionUpdate(
			array(
			'addField',
			'user',
			'user_wiki_group',
			dirname( dirname( __FILE__ ) ) . '/sql/user_group.sql',
			true
			)
		);
		$updater->addExtensionUpdate(
			array(
			'addField',
			'user',
			'user_inactivity',
			dirname( dirname( __FILE__ ) ) . '/sql/user_inactivity.sql',
			true
			)
		);
		$updater->addExtensionUpdate(
			array (
			'addTable',
			'collaboration',
			dirname( dirname( __FILE__ ) ) . '/sql/Collaboration.sql',
			true
			)
		);
		return true;
	}
}
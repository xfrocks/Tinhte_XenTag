<?php
class Tinhte_XenTag_Installer {
	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected static $_tables = array(
		'tag' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_tinhte_xentag_tag` (
				`tag_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`tag_text` VARCHAR(100) NOT NULL
				,`created_date` INT(10) UNSIGNED NOT NULL
				,`created_user_id` INT(10) UNSIGNED NOT NULL
				,`content_count` INT(10) UNSIGNED DEFAULT \'0\'
				, PRIMARY KEY (`tag_id`)
				, INDEX `tag_text` (`tag_text`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_tinhte_xentag_tag`'
		),
		'tagged_content' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_tinhte_xentag_tagged_content` (
				`tag_id` INT(10) UNSIGNED NOT NULL
				,`content_type` VARCHAR(25) NOT NULL
				,`content_id` INT(10) UNSIGNED NOT NULL
				,`tagged_user_id` INT(10) UNSIGNED NOT NULL
				,`tagged_date` INT(10) UNSIGNED NOT NULL
				, PRIMARY KEY (`tag_id`,`content_type`,`content_id`)
				
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_tinhte_xentag_tagged_content`'
		)
	);
	protected static $_patches = array(
		array(
			'table' => 'xf_thread',
			'field' => 'tinhte_xentag_tags',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_thread\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_thread` LIKE \'tinhte_xentag_tags\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_thread` ADD COLUMN `tinhte_xentag_tags` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_thread` DROP COLUMN `tinhte_xentag_tags`'
		),
		array(
			'table' => 'xf_tinhte_xentag_tag',
			'field' => 'latest_tagged_contents',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_tinhte_xentag_tag\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_tinhte_xentag_tag` LIKE \'latest_tagged_contents\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_tinhte_xentag_tag` ADD COLUMN `latest_tagged_contents` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_tinhte_xentag_tag` DROP COLUMN `latest_tagged_contents`'
		),
		array(
			'table' => 'xf_tinhte_xentag_tag',
			'field' => 'tag_description',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_tinhte_xentag_tag\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_tinhte_xentag_tag` LIKE \'tag_description\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_tinhte_xentag_tag` ADD COLUMN `tag_description` TEXT',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_tinhte_xentag_tag` DROP COLUMN `tag_description`'
		),
		array(
			'table' => 'xf_forum',
			'field' => 'tinhte_xentag_options',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_forum\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_forum` LIKE \'tinhte_xentag_options\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_forum` ADD COLUMN `tinhte_xentag_options` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_forum` DROP COLUMN `tinhte_xentag_options`'
		),
		array(
			'table' => 'xf_forum',
			'field' => 'tinhte_xentag_tags',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_forum\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_forum` LIKE \'tinhte_xentag_tags\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_forum` ADD COLUMN `tinhte_xentag_tags` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_forum` DROP COLUMN `tinhte_xentag_tags`'
		),
		array(
			'table' => 'xf_page',
			'field' => 'tinhte_xentag_tags',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_page\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_page` LIKE \'tinhte_xentag_tags\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_page` ADD COLUMN `tinhte_xentag_tags` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_page` DROP COLUMN `tinhte_xentag_tags`'
		),
		array(
			'table' => 'xf_resource',
			'field' => 'tinhte_xentag_tags',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_resource\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_resource` LIKE \'tinhte_xentag_tags\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_resource` ADD COLUMN `tinhte_xentag_tags` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_resource` DROP COLUMN `tinhte_xentag_tags`'
		)
	);

	public static function install() {
		$db = XenForo_Application::get('db');

		foreach (self::$_tables as $table) {
			$db->query($table['createQuery']);
		}
		
		foreach (self::$_patches as $patch) {
			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (empty($existed)) {
				$db->query($patch['alterTableAddColumnQuery']);
			}
		}
		
		self::installCustomized();
	}
	
	public static function uninstall() {
		$db = XenForo_Application::get('db');
		
		foreach (self::$_patches as $patch) {
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted)) {
				continue;
			}
			
			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (!empty($existed)) {
				$db->query($patch['alterTableDropColumnQuery']);
			}
		}
		
		foreach (self::$_tables as $table) {
			$db->query($table['dropQuery']);
		}
		
		self::uninstallCustomized();
	}

	/* End auto-generated lines of code. Feel free to make changes below */
	
	private static function installCustomized() {
		$db = XenForo_Application::get('db');
		
		$db->query('INSERT IGNORE INTO xf_content_type (content_type, addon_id) VALUES (\'tinhte_xentag_page\', \'Tinhte_XenTag\')');
		$db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'tinhte_xentag_page\', \'search_handler_class\', \'Tinhte_XenTag_Search_DataHandler_Page\')');
		$db->query('INSERT IGNORE INTO xf_content_type (content_type, addon_id) VALUES (\'tinhte_xentag_forum\', \'Tinhte_XenTag\')');
		$db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'tinhte_xentag_forum\', \'search_handler_class\', \'Tinhte_XenTag_Search_DataHandler_Forum\')');
		$db->query('INSERT IGNORE INTO xf_content_type (content_type, addon_id) VALUES (\'tinhte_xentag_resource\', \'Tinhte_XenTag\')');
		$db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'tinhte_xentag_resource\', \'search_handler_class\', \'Tinhte_XenTag_Search_DataHandler_Resource\')');
		
		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}
	
	private static function uninstallCustomized() {
		$db = XenForo_Application::get('db');
		
		$db->query('DELETE FROM xf_content_type WHERE addon_id = ?', array('Tinhte_XenTag'));
		$db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', array('tinhte_xentag_page'));
		$db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', array('tinhte_xentag_forum'));
		$db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', array('tinhte_xentag_resource'));
		
		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}
	
}
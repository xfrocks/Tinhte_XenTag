<?php

class Tinhte_XenTag_Installer
{
    /* Start auto-generated lines of code. Change made will be overwriten... */

    protected static $_tables = array(
        'tag_watch' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_tinhte_xentag_tag_watch` (
                `user_id` INT(10) UNSIGNED NOT NULL
                ,`tag_id` INT(10) UNSIGNED NOT NULL
                ,`send_alert` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
                ,`send_email` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
                , PRIMARY KEY (`tag_id`,`user_id`)
                , INDEX `user_id` (`user_id`)
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_tinhte_xentag_tag_watch`',
        ),
    );
    protected static $_patches = array(
        array(
            'table' => 'xf_forum',
            'field' => 'tinhte_xentag_tags',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_forum\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_forum` LIKE \'tinhte_xentag_tags\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_forum` ADD COLUMN `tinhte_xentag_tags` MEDIUMBLOB',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_forum` DROP COLUMN `tinhte_xentag_tags`',
        ),
        array(
            'table' => 'xf_page',
            'field' => 'tinhte_xentag_tags',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_page\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_page` LIKE \'tinhte_xentag_tags\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_page` ADD COLUMN `tinhte_xentag_tags` MEDIUMBLOB',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_page` DROP COLUMN `tinhte_xentag_tags`',
        ),
        array(
            'table' => 'xf_tag',
            'field' => 'tinhte_xentag_staff',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_tag\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_tag` LIKE \'tinhte_xentag_staff\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_tag` ADD COLUMN `tinhte_xentag_staff` TINYINT(4) UNSIGNED DEFAULT \'0\'',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_tag` DROP COLUMN `tinhte_xentag_staff`',
        ),
        array(
            'table' => 'xf_tag',
            'field' => 'tinhte_xentag_title',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_tag\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_tag` LIKE \'tinhte_xentag_title\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_tag` ADD COLUMN `tinhte_xentag_title` VARCHAR(255) DEFAULT \'\'',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_tag` DROP COLUMN `tinhte_xentag_title`',
        ),
        array(
            'table' => 'xf_tag',
            'field' => 'tinhte_xentag_description',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_tag\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_tag` LIKE \'tinhte_xentag_description\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_tag` ADD COLUMN `tinhte_xentag_description` TEXT',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_tag` DROP COLUMN `tinhte_xentag_description`',
        ),
    );

    public static function install($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_tables as $table) {
            $db->query($table['createQuery']);
        }

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (empty($existed)) {
                $db->query($patch['alterTableAddColumnQuery']);
            }
        }

        self::installCustomized($existingAddOn, $addOnData);
    }

    public static function uninstall()
    {
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

    protected static function installCustomized($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::getDb();

        $db->query('INSERT IGNORE INTO xf_content_type (content_type, addon_id) VALUES (\'tinhte_xentag_forum\', \'Tinhte_XenTag\')');
        $db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'tinhte_xentag_forum\', \'search_handler_class\', \'Tinhte_XenTag_Search_DataHandler_Forum\')');
        $db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'tinhte_xentag_forum\', \'tag_handler_class\', \'Tinhte_XenTag_TagHandler_Forum\')');
        $db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'page\', \'tag_handler_class\', \'Tinhte_XenTag_TagHandler_Page\')');
        $db->query('INSERT IGNORE INTO xf_content_type_field (content_type, field_name, field_value) VALUES (\'post\', \'tag_handler_class\', \'Tinhte_XenTag_TagHandler_Post\')');
        /** @var XenForo_Model_ContentType $contentTypeModel */
        $contentTypeModel = XenForo_Model::create('XenForo_Model_ContentType');
        $contentTypeModel->rebuildContentTypeCache();

        $effectiveVersionId = 0;
        if (!empty($existingAddOn)) {
            $effectiveVersionId = intval($existingAddOn['version_id']);
        }

        if ($effectiveVersionId < 135) {
            self::upgradeFrom134();
            self::uninstallVersion134();
        }

        if ($effectiveVersionId < 135) {
            $db->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT user_group_id, user_id, 'general', ?, permission_value, 0
				FROM xf_permission_entry
				WHERE permission_group_id = 'general' AND permission_id = 'search'
			", Tinhte_XenTag_Constants::PERM_USER_WATCH);
        }

        if ($effectiveVersionId < 90
            || in_array($effectiveVersionId, array(135, 136), true)
        ) {
            $db->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT user_group_id, user_id, 'general', ?, permission_value, 0
				FROM xf_permission_entry
				WHERE permission_group_id = 'general' AND permission_id = 'cleanSpam'
			", Tinhte_XenTag_Constants::PERM_USER_IS_STAFF);
        }

        if ($effectiveVersionId < 1
            || in_array($effectiveVersionId, array(135, 136, 137), true)
        ) {
            $db->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT user_group_id, user_id, 'general', ?, permission_value, 0
				FROM xf_permission_entry
				WHERE permission_group_id = 'general' AND permission_id = 'cleanSpam'
			", Tinhte_XenTag_Constants::PERM_USER_EDIT);
        }
    }

    protected static function uninstallCustomized()
    {
        self::uninstallVersion134();

        $db = XenForo_Application::getDb();

        $db->query('DELETE FROM xf_permission_entry WHERE permission_id IN (' . $db->quote(array(
                Tinhte_XenTag_Constants::PERM_USER_WATCH,
                Tinhte_XenTag_Constants::PERM_USER_IS_STAFF,
                Tinhte_XenTag_Constants::PERM_USER_EDIT,
            )) . ')');

        $db->query('DELETE FROM xf_content_type WHERE addon_id = ?', 'Tinhte_XenTag');
        $db->query('DELETE FROM xf_content_type_field WHERE field_value = ?', 'Tinhte_XenTag_Search_DataHandler_Forum');
        $db->query('DELETE FROM xf_content_type_field WHERE field_value = ?', 'Tinhte_XenTag_TagHandler_Forum');
        $db->query('DELETE FROM xf_content_type_field WHERE field_value = ?', 'Tinhte_XenTag_TagHandler_Page');

        // it's safe to drop our tables completely now
        $db->query('DROP TABLE IF EXISTS `xf_tinhte_xentag_tag`;');
        $db->query('DROP TABLE IF EXISTS `xf_tinhte_xentag_tagged_content`;');

        /** @var XenForo_Model_DataRegistry $dataRegistryModel */
        $dataRegistryModel = XenForo_Model::create('XenForo_Model_DataRegistry');
        $dataRegistryModel->delete(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TAGS);
    }

    private static function upgradeFrom134()
    {
        if (XenForo_Application::$versionId > 1020000) {
            XenForo_Application::defer('Tinhte_XenTag_Deferred_UpgradeFrom134', array());
        }
    }

    private static function uninstallVersion134()
    {
        $db = XenForo_Application::getDb();

        $db->query('DELETE FROM xf_permission_entry WHERE permission_id IN (' . $db->quote(array(
                'Tinhte_XenTag_maximumHts',
                'Tinhte_XenTag_maximumTags',
                'Tinhte_XenTag_tag',
                'Tinhte_XenTag_tagAll',
                'Tinhte_XenTag_createNew',
                'TXT_resourceMaximumTags',
                'Tinhte_XenTag_resourceAll',
                'Tinhte_XenTag_resourceTag',
            )) . ')');

        // do not drop `tag` and `tagged_content` tables for now to avoid losing important data
        // they will be dropped when the add-on is uninstalled
        $db->query('DROP TABLE IF EXISTS `xf_tinhte_xentag_tag_view`;');
        $db->query('DROP TABLE IF EXISTS `xf_tinhte_xentag_tag_day_view`;');

        foreach (array(
                     'xf_thread' => array('tinhte_xentag_tags', 'tinhte_xentag_is_tagged'),
                     'xf_forum' => array('tinhte_xentag_options'),
                     'xf_resource' => array('tinhte_xentag_tags'),
                     'xf_search' => array('tinhte_xentag_tags'),
                 ) as $table => $fields) {
            if ($db->fetchOne('SHOW TABLES LIKE ' . $db->quote($table))) {
                foreach ($fields as $field) {
                    if ($db->fetchOne('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $db->quote($field))) {
                        $db->query('ALTER TABLE `' . $table . '` DROP COLUMN `' . $field . '`');
                    }
                }
            }
        }

        $db->query('DELETE FROM xf_content_type WHERE content_type = ?', 'tinhte_xentag_page');
        $db->query('DELETE FROM xf_content_type WHERE content_type = ?', 'tinhte_xentag_forum');
        $db->query('DELETE FROM xf_content_type WHERE content_type = ?', 'tinhte_xentag_resource');
        $db->query('DELETE FROM xf_content_type WHERE content_type = ?', 'tinhte_xentag_tag');
        $db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', 'tinhte_xentag_page');
        $db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', 'tinhte_xentag_forum');
        $db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', 'tinhte_xentag_resource');
        $db->query('DELETE FROM xf_content_type_field WHERE content_type = ?', 'tinhte_xentag_tag');

        /** @var XenForo_Model_DataRegistry $dataRegistryModel */
        $dataRegistryModel = XenForo_Model::create('XenForo_Model_DataRegistry');
        $dataRegistryModel->delete('Tinhte_XenTag_trending');
    }

}

<?php

class Tinhte_XenTag_Option
{

	const AUTO_TAG_MODE_THREAD_TAGS = 'thread_tags';
	const AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY = 'thread_tags_first_post_only';
	const AUTO_TAG_RESOURCE_TAGS = 'resource_tags';
	const AUTO_TAG_MODE_ALL_TAGS = 'all_tags';
	const AUTO_TAG_MODE_DISALBED = 'disabled';

	const LINK_FORMAT_BEAUTIFUL = 'beautiful';

	public static function get($key)
	{
		$options = XenForo_Application::get('options');

		static $keyPrefix = 'Tinhte_XenTag_';

		static $availablePositions = array(
			'post_below',
			'post_message_below',
			'post_message_above',
			'post_date_after',
			'post_permalink_after',
			'thread_pagenav_above',
			'thread_messages_above',
			'thread_qr_above',
			'thread_qr_below',
		);

		static $availableAutoTagModes = array(
			self::AUTO_TAG_MODE_THREAD_TAGS,
			self::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY,
			self::AUTO_TAG_MODE_ALL_TAGS,
			self::AUTO_TAG_MODE_DISALBED
		);

		switch ($key)
		{
			case 'perPage':
				return 20;
			case 'cloudMax':
				return 100;
			case 'cloudLevelCount':
				return 5;
			case 'majorSection':
				return 'forums';
			case 'searchForceUseCache':
				return !XenForo_Application::debugMode();

			case 'displayPosition':
				$position = $options->get($keyPrefix . $key);
				if (!in_array($position, $availablePositions))
				{
					$position = $availablePositions[0];
				}
				return $position;

			case 'autoTagMode':
				$mode = $options->get($keyPrefix . $key);
				if (!in_array($mode, $availableAutoTagModes))
				{
					$mode = self::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY;
				}
				return $mode;
			// case 'autoTagOnceOnly': return true;
			// case 'autoTagGlobalMax': return 1000;

			case 'latestTaggedContentsLimit':
				return 10;
		}

		return $options->get($keyPrefix . $key);
	}

	public static function verifyTagMaxLength(&$value, XenForo_DataWriter $dw, $fieldName)
	{
		if ($value > 100)
		{
			$value = 100;
			// TODO: throw error?
		}

		return true;
	}

	public static function xfrmFound()
	{
		$moderatorModel = XenForo_Model::create('XenForo_Model_Moderator');
		$gmigi = $moderatorModel->getGeneralModeratorInterfaceGroupIds();
		return in_array('resourceModeratorPermissions', $gmigi);
	}

}

<?php

class Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post
{
	const DATA_SKIP_UPDATE_THREAD_TAGS = 'Tinhte_XenTag_forceUpdateThreadTags';

	protected $_Tinhte_XenTag_tagTexts = false;

	protected function _Tinhte_XenTag_updateTagsInDatabase()
	{
		if ($this->_Tinhte_XenTag_tagTexts !== false)
		{
			$updated = Tinhte_XenTag_Integration::updateTags('post', $this->get('post_id'), $this->get('user_id'), $this->_Tinhte_XenTag_tagTexts, $this, array('throwException' => false));

			if (is_array($updated))
			{
				$tagsCount = count($updated);
			}
			else
			{
				$tagsCount = intval($updated);
			}

			if ($tagsCount != count($this->_Tinhte_XenTag_tagTexts))
			{
				// there are something wrong with the hashtag...
				// probably a new hashtag without tag creating permission
				// we have to remove the problematic hashtags
				$message = $this->get('message');

				$message = $this->_filterHashtagsFromMessage($message);

				parent::_setInternal('xf_post', 'message', $message);
				$this->_db->update('xf_post', array('message' => $message), array('post_id = ?' => $this->get('post_id')));
			}

			$forum = $this->_Tinhte_XenTag_getForumInfo();
			$maximumTags = intval($this->getModelFromCache('XenForo_Model_Forum')->Tinhte_XenTag_getMaximumHashtags($forum));

			if ($maximumTags !== -1 AND $tagsCount > $maximumTags)
			{
				if ($maximumTags === 0)
				{
					throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_no_hashtags_allowed'), true);
				}

				throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_too_many_hashtags_x_of_y_list_z', array(
					'maximum' => $maximumTags,
					'count' => $tagsCount,
					'tagTexts' => '#' . implode(', #', $this->_Tinhte_XenTag_tagTexts),
				)), true);
			}

			$this->_Tinhte_XenTag_tagTexts = false;
		}
	}

	protected function _Tinhte_XenTag_getForumInfo()
	{
		if (XenForo_Application::$versionId > 1020000)
		{
			return $this->_getForumInfo();
		}
		else
		{
			if (!$forum = $this->getExtraData(self::DATA_FORUM))
			{
				$forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumByThreadId($this->get('thread_id'));

				$this->setExtraData(self::DATA_FORUM, $forum ? $forum : array());
			}

			return $this->getExtraData(self::DATA_FORUM);
		}
	}

	protected function _messagePreSave()
	{
		// checks for our controller and call it first
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE]->Tinhte_XenTag_actionSave($this);
		}

		return parent::_messagePreSave();
	}

	protected function _updateDeletionLog()
	{
		// we have to use _updateDeletionLog here because _messagePostSave is triggered
		// too late and we can't update the search index from there...

		$tagTexts = $this->_Tinhte_XenTag_tagTexts;

		$this->_Tinhte_XenTag_updateTagsInDatabase();

		if (!$this->getExtraData(self::DATA_SKIP_UPDATE_THREAD_TAGS) AND !empty($tagTexts) AND $this->get('position') == 0)
		{
			$threadDw = $this->getDiscussionDataWriter();
			$isChanged = false;

			if (self::updateThreadDwFromPostDw($threadDw, $this))
			{
				$threadDw->save();
			}
		}

		return parent::_updateDeletionLog();
	}

	protected function _postSaveAfterTransaction()
	{
		$response = parent::_postSaveAfterTransaction();

		if ($this->get('message_state') == 'visible')
		{
			$contentData = array_merge(array(
				'content_type' => 'post',
				'content_id' => $this->get('post_id'),
			), $this->getDiscussionData(), $this->getMergedData());

			$forumInfo = $this->_Tinhte_XenTag_getForumInfo();
			$contentPermissionConfig = array(
				'content_type' => 'node',
				'content_id' => $forumInfo['node_id'],
				'permissions' => array(
					'view',
					'viewOthers',
					'viewContent'
				),
			);
			Tinhte_XenTag_Integration::sendNotificationToWatchUsersOnTagged('post', $this->get('post_id'), $contentData, $this, $contentPermissionConfig);
		}

		return $response;
	}

	protected function _setInternal($table, $field, $newValue, $forceSet = false)
	{
		if ($table === 'xf_post' AND $field === 'message')
		{
			$forum = $this->_Tinhte_XenTag_getForumInfo();
			$maximumTags = intval($this->getModelFromCache('XenForo_Model_Forum')->Tinhte_XenTag_getMaximumHashtags($forum));

			if ($maximumTags === -1 OR $maximumTags > 0)
			{
				$this->_Tinhte_XenTag_tagTexts = Tinhte_XenTag_Integration::parseHashtags($newValue, true);
			}
			else
			{
				// always pickup [HAHSTAG]s in post, we will show error message later if found
				$this->_Tinhte_XenTag_tagTexts = Tinhte_XenTag_Integration::parseHashtags($newValue);
			}
		}

		return parent::_setInternal($table, $field, $newValue, $forceSet);
	}

	protected function _filterHashtagsFromMessage($message)
	{
		$bbCodeOpen = '[HASHTAG]';
		$bbCodeClose = '[/HASHTAG]';

		$dbTags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->getTagsOfContent('post', $this->get('post_id'));
		$dbTagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($dbTags);
		$dbSafeTags = Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($dbTagTexts);

		$offset = 0;
		while (true)
		{
			$posOpen = Tinhte_XenTag_Helper::utf8_stripos($message, $bbCodeOpen, $offset);
			if ($posOpen === false)
			{
				break;
			}

			$posClose = Tinhte_XenTag_Helper::utf8_stripos($message, $bbCodeClose, $posOpen);
			if ($posClose === false)
			{
				break;
			}

			$offset = $posOpen + 1;
			$posTagTextOffset = $posOpen + utf8_strlen($bbCodeOpen) + 1;
			$posTagTextLength = $posClose - $posTagTextOffset;
			$posTagText = utf8_substr($message, $posTagTextOffset, $posTagTextLength);
			$posSafeTag = Tinhte_XenTag_Helper::getSafeTagTextForSearch($posTagText);

			if (!in_array($posSafeTag, $dbSafeTags))
			{
				$message = utf8_substr_replace($message, '#' . $posTagText, $posOpen, $posClose + utf8_strlen($bbCodeClose) - $posOpen);
			}
		}

		return $message;
	}

	public static function updateThreadDwFromPostDw(XenForo_DataWriter_Discussion_Thread $threadDw, XenForo_DataWriter_DiscussionMessage_Post $postDw)
	{
		if (!Tinhte_XenTag_Option::get('tagThreadWithHashtags'))
		{
			return false;
		}

		$message = $postDw->get('message');
		$tagTexts = Tinhte_XenTag_Integration::parseHashtags($message);

		$threadTags = $threadDw->Tinhte_XenTag_getTags();
		$threadTagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($threadTags);
		$threadSafes = Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($threadTagTexts);
		$isChanged = false;

		foreach ($tagTexts as $tagText)
		{
			$safe = Tinhte_XenTag_Helper::getSafeTagTextForSearch($tagText);
			if (!in_array($safe, $threadSafes))
			{
				$threadTags[] = $tagText;
				$threadSafes[] = $safe;
				$isChanged = true;
			}
		}

		if ($isChanged)
		{
			$threadDw->Tinhte_XenTag_setTags($threadTags);
			return true;
		}

		return false;
	}

}

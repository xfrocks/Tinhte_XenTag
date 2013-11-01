<?php

class Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post
{
	const DATA_SKIP_UPDATE_THREAD_TAGS = 'Tinhte_XenTag_forceUpdateThreadTags';

	protected $_Tinhte_XenTag_tagTexts = false;

	protected function _Tinhte_XenTag_updateTagsInDatabase()
	{
		if ($this->_Tinhte_XenTag_tagTexts !== false)
		{
			$updated = Tinhte_XenTag_Integration::updateTags('post', $this->get('post_id'), $this->get('user_id'), $this->_Tinhte_XenTag_tagTexts, $this);

			if (is_array($updated))
			{
				$tagsCount = count($updated);
			}
			else
			{
				$tagsCount = intval($updated);
			}

			$this->_Tinhte_XenTag_tagTexts = false;

			$forum = $this->_getForumInfo();
			$maximumTags = intval($this->getModelFromCache('XenForo_Model_Forum')->Tinhte_XenTag_getMaximumHashtags($forum));

			if ($maximumTags !== -1 AND $tagsCount > $maximumTags)
			{
				throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_too_many_tags_x_of_y', array(
					'maximum' => $maximumTags,
					'count' => $tagsCount
				)), true);
			}
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

	protected function _messagePostSave()
	{
		$tagTexts = $this->_Tinhte_XenTag_tagTexts;

		$this->_Tinhte_XenTag_updateTagsInDatabase();

		if (!$this->getExtraData(self::DATA_SKIP_UPDATE_THREAD_TAGS) AND !empty($tagTexts) AND $this->get('position') == 0)
		{
			$threadDw = $this->getDiscussionDataWriter();
			$isChanged = false;

			if (self::updateThreadDwFromPostDw($threadDw, $this, $tagTexts))
			{
				$isChanged = true;
			}

			if ($isChanged)
			{
				$threadDw->save();
			}
		}

		return parent::_messagePostSave();
	}

	protected function _setInternal($table, $field, $newValue, $forceSet = false)
	{
		if ($table === 'xf_post' AND $field === 'message')
		{
			$this->_Tinhte_XenTag_tagTexts = Tinhte_XenTag_Integration::parseHashtags($newValue, true);
		}

		return parent::_setInternal($table, $field, $newValue, $forceSet);
	}

	public static function updateThreadDwFromPostDw(XenForo_DataWriter_Discussion_Thread $threadDw, XenForo_DataWriter_DiscussionMessage_Post $postDw, $tagTexts = null)
	{
		if (!Tinhte_XenTag_Option::get('tagThreadWithHashtags'))
		{
			return false;
		}

		if ($tagTexts === null)
		{
			$message = $postDw->get('message');
			$tagTexts = Tinhte_XenTag_Integration::parseHashtags($message);
		}

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

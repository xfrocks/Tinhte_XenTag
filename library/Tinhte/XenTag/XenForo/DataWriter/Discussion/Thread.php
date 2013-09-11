<?php

class Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread_Base extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread
{

	const DATA_FORCE_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_forceUpdateTagsInDatabase';
	const DATA_SKIP_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_skipUpdateTagsInDatabase';

	// TODO: drop this property as it's not necessary
	protected $_tagsNeedUpdated = false;

	public function Tinhte_XenTag_setTags(array $tags)
	{
		// sondh@2012-08-11
		// this method has been greatly simplified to make it easier +
		// more consistent when you need to integrate more content type
		// with the system. Originally, the tag is verified here first
		// to make sure it's not too long, all characters are in correct cases, etc.
		// Doing so will make the saved tags in content table look just like
		// they are saved internally (because the tags are saved in content table
		// before they are saved in tag table).
		// In special case when user give some invalid tag text, an exception will
		// be thrown. It's done post save but because it's still in the same db
		// transaction, the incorrect date will not be saved.
		$this->set(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS, $tags);
		$this->_tagsNeedUpdated = true;
	}

	public function Tinhte_XenTag_getForumData()
	{
		return $this->_getForumData();
	}

	public function Tinhte_XenTag_updateTagsInDatabase()
	{
		// this function needs to be made public because the importer
		// will have to call it directly (_postSave() is not being called
		// in import mode)
		$force = $this->getExtraData(self::DATA_FORCE_UPDATE_TAGS_IN_DATABASE);
		$skip = $this->getExtraData(self::DATA_SKIP_UPDATE_TAGS_IN_DATABASE);

		if ($force OR ($this->_tagsNeedUpdated AND empty($skip)))
		{
			$tagsOrTexts = Tinhte_XenTag_Helper::unserialize($this->get(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS));
			$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);

			$updated = Tinhte_XenTag_Integration::updateTags('thread', $this->get('thread_id'), $this->get('user_id'), $tagTexts, $this);

			if (is_array($updated))
			{
				$tagsCount = count($updated);

				$this->set(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS, $updated, '', array('setAfterPreSave' => true));
				$this->_db->update('xf_thread', array(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS => serialize($updated)), array('thread_id = ?' => $this->get('thread_id')));
			}
			else
			{
				$tagsCount = intval($updated);
			}

			$this->_tagsNeedUpdated = false;

			$forum = $this->Tinhte_XenTag_getForumData();
			$options = Tinhte_XenTag_Helper::unserialize($forum[Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS]);
			$requiresTag = Tinhte_XenTag_Option::get('requiresTag');
			if (isset($options['requiresTag']) AND $options['requiresTag'] !== '')
			{
				$requiresTag = $options['requiresTag'];
			}
			$maximumTags = intval($this->getModelFromCache('XenForo_Model_Forum')->Tinhte_XenTag_getMaximumTags($forum));
			
			if ($requiresTag AND $maximumTags !== 0 AND $tagsCount == 0)
			{
				throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_thread_requires_tag'), true);
			}

			if ($maximumTags !== -1 AND $tagsCount > $maximumTags)
			{
				throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_too_many_tags_x_of_y', array(
					'maximum' => $maximumTags,
					'count' => $tagsCount
				)), true);
			}
		}
	}

	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_thread'][Tinhte_XenTag_Constants::FIELD_THREAD_TAGS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}'
		);

		return $fields;
	}

	protected function _discussionPreSave()
	{
		// checks for our controller and call it first
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD]->Tinhte_XenTag_actionAddThread($this);
		}
		elseif (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_SAVE]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_SAVE]->Tinhte_XenTag_actionSave($this);
		}

		return parent::_discussionPreSave();
	}

	protected function _needsSearchIndexUpdate()
	{
		return (parent::_needsSearchIndexUpdate() || $this->isChanged(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS));
	}

}

if (XenForo_Application::$versionId < 1020000)
{
	// old versions
	class Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread extends Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread_Base
	{
		protected function _discussionPostSave(array $messages)
		{
			$this->Tinhte_XenTag_updateTagsInDatabase();

			return parent::_discussionPostSave($messages);
		}

		protected function _discussionPostDelete(array $messages)
		{
			Tinhte_XenTag_Integration::deleteTags('thread', $this->get('thread_id'), $this);

			return parent::_discussionPostDelete($messages);
		}

	}

}
else
{
	// v1.2+
	class Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread extends Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread_Base
	{
		protected function _discussionPostSave()
		{
			$this->Tinhte_XenTag_updateTagsInDatabase();

			return parent::_discussionPostSave();
		}

		protected function _discussionPostDelete()
		{
			Tinhte_XenTag_Integration::deleteTags('thread', $this->get('thread_id'), $this);

			return parent::_discussionPostDelete();
		}

	}

}

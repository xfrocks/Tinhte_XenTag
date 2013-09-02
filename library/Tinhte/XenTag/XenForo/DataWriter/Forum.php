<?php

class Tinhte_XenTag_XenForo_DataWriter_Forum extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Forum
{

	const DATA_FORCE_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_forceUpdateTagsInDatabase';
	const DATA_SKIP_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_skipUpdateTagsInDatabase';

	public function Tinhte_XenTag_setTags(array $tags)
	{
		$this->set(Tinhte_XenTag_Constants::FIELD_FORUM_TAGS, $tags);
	}

	protected function _Tinhte_XenTag_updateTagsInDatabase()
	{
		$force = $this->getExtraData(self::DATA_FORCE_UPDATE_TAGS_IN_DATABASE);
		$skip = $this->getExtraData(self::DATA_SKIP_UPDATE_TAGS_IN_DATABASE);

		if ($force OR ($this->isChanged(Tinhte_XenTag_Constants::FIELD_FORUM_TAGS) AND empty($skip)))
		{
			$tagsOrTexts = Tinhte_XenTag_Helper::unserialize($this->get(Tinhte_XenTag_Constants::FIELD_FORUM_TAGS));
			$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);

			$updated = Tinhte_XenTag_Integration::updateTags(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM, $this->get('node_id'), XenForo_Visitor::getUserId(), $tagTexts, $this);

			if (is_array($updated))
			{
				$this->set(Tinhte_XenTag_Constants::FIELD_FORUM_TAGS, $updated, '', array('setAfterPreSave' => true));
				$this->_db->update('xf_forum', array(Tinhte_XenTag_Constants::FIELD_FORUM_TAGS => serialize($updated)), array('node_id = ?' => $this->get('node_id')));
			}
		}
	}

	protected function _Tinhte_XenTag_indexForSearch()
	{
		$indexer = new XenForo_Search_Indexer();

		$data = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Forum');

		$dataHandler->insertIntoIndex($indexer, $data);
	}

	protected function _Tinhte_XenTag_unindexFromSearch()
	{
		$indexer = new XenForo_Search_Indexer();

		$data = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Forum');

		$dataHandler->deleteFromIndex($indexer, $data);
	}

	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_forum'][Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}'
		);

		$fields['xf_forum'][Tinhte_XenTag_Constants::FIELD_FORUM_TAGS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}'
		);

		return $fields;
	}

	protected function _preSave()
	{
		// checks for our controller and call it first
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE]->Tinhte_XenTag_actionSave($this);
		}

		return parent::_preSave();
	}

	protected function _postSave()
	{
		$this->_Tinhte_XenTag_updateTagsInDatabase();
		$this->_Tinhte_XenTag_indexForSearch();

		return parent::_postSave();
	}

	protected function _postDelete()
	{
		Tinhte_XenTag_Integration::deleteTags(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM, $this->get('node_id'), $this);
		$this->_Tinhte_XenTag_unindexFromSearch();

		return parent::_postDelete();
	}

}

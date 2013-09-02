<?php

class Tinhte_XenTag_XenForo_DataWriter_Page extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Page
{

	const DATA_FORCE_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_forceUpdateTagsInDatabase';
	const DATA_SKIP_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_skipUpdateTagsInDatabase';

	public function Tinhte_XenTag_setTags(array $tags)
	{
		$this->set(Tinhte_XenTag_Constants::FIELD_PAGE_TAGS, $tags);
	}

	protected function _Tinhte_XenTag_updateTagsInDatabase()
	{
		$force = $this->getExtraData(self::DATA_FORCE_UPDATE_TAGS_IN_DATABASE);
		$skip = $this->getExtraData(self::DATA_SKIP_UPDATE_TAGS_IN_DATABASE);

		if ($force OR ($this->isChanged(Tinhte_XenTag_Constants::FIELD_PAGE_TAGS) AND empty($skip)))
		{
			$tagsOrTexts = Tinhte_XenTag_Helper::unserialize($this->get(Tinhte_XenTag_Constants::FIELD_PAGE_TAGS));
			$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);

			$updated = Tinhte_XenTag_Integration::updateTags(Tinhte_XenTag_Constants::CONTENT_TYPE_PAGE, $this->get('node_id'), XenForo_Visitor::getUserId(), $tagTexts, $this);

			if (is_array($updated))
			{
				$this->set(Tinhte_XenTag_Constants::FIELD_PAGE_TAGS, $updated, '', array('setAfterPreSave' => true));
				$this->_db->update('xf_page', array(Tinhte_XenTag_Constants::FIELD_PAGE_TAGS => serialize($updated)), array('node_id = ?' => $this->get('node_id')));
			}
		}
	}

	protected function _Tinhte_XenTag_indexForSearch()
	{
		$indexer = new XenForo_Search_Indexer();

		$data = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Page');

		$dataHandler->insertIntoIndex($indexer, $data);
	}

	protected function _Tinhte_XenTag_unindexFromSearch()
	{
		$indexer = new XenForo_Search_Indexer();

		$data = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Page');

		$dataHandler->deleteFromIndex($indexer, $data);
	}

	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_page'][Tinhte_XenTag_Constants::FIELD_PAGE_TAGS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}'
		);

		return $fields;
	}

	protected function _preSave()
	{
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE]->Tinhte_XenTag_actionSave($this);
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
		Tinhte_XenTag_Integration::deleteTags(Tinhte_XenTag_Constants::CONTENT_TYPE_PAGE, $this->get('node_id'), $this);
		$this->_Tinhte_XenTag_unindexFromSearch();

		return parent::_postDelete();
	}

}

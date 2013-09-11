<?php

class Tinhte_XenTag_XenResource_DataWriter_Resource extends XFCP_Tinhte_XenTag_XenResource_DataWriter_Resource
{

	const DATA_FORCE_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_forceUpdateTagsInDatabase';
	const DATA_SKIP_UPDATE_TAGS_IN_DATABASE = 'Tinhte_XenTag_skipUpdateTagsInDatabase';

	public function Tinhte_XenTag_setTags(array $tags)
	{
		$this->set(Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS, $tags);
	}

	protected function _Tinhte_XenTag_updateTagsInDatabase()
	{
		$force = $this->getExtraData(self::DATA_FORCE_UPDATE_TAGS_IN_DATABASE);
		$skip = $this->getExtraData(self::DATA_SKIP_UPDATE_TAGS_IN_DATABASE);

		if ($force OR ($this->isChanged(Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS) AND empty($skip)))
		{
			$tagsOrTexts = Tinhte_XenTag_Helper::unserialize($this->get(Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS));
			$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);

			$updated = Tinhte_XenTag_Integration::updateTags(Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE, $this->get('resource_id'), XenForo_Visitor::getUserId(), $tagTexts, $this);

			if (is_array($updated))
			{
				$tagsCount = count($updated);

				$this->set(Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS, $updated, '', array('setAfterPreSave' => true));
				$this->_db->update('xf_resource', array(Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS => serialize($updated)), array('resource_id = ?' => $this->get('resource_id')));
			}
			else
			{
				$tagsCount = intval($updated);
			}

			$requiresTag = Tinhte_XenTag_Option::get('resourceRequiresTag');
			$maximumTags = intval($this->getModelFromCache('XenResource_Model_Resource')->Tinhte_XenTag_getMaximumTags());
			
			if ($requiresTag AND $maximumTags !== 0 AND $tagsCount == 0)
			{
				throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_resource_requires_tag'), true);
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

	protected function _Tinhte_XenTag_indexForSearch()
	{
		$indexer = new XenForo_Search_Indexer();

		$data = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Resource');

		$dataHandler->insertIntoIndex($indexer, $data);
	}

	protected function _Tinhte_XenTag_unindexFromSearch()
	{
		$indexer = new XenForo_Search_Indexer();

		$data = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Resource');

		$dataHandler->deleteFromIndex($indexer, $data);
	}

	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_resource'][Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}'
		);

		return $fields;
	}

	protected function _preSave()
	{
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_RESOURCE_SAVE]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_RESOURCE_SAVE]->Tinhte_XenTag_actionSave($this);
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
		Tinhte_XenTag_Integration::deleteTags(Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE, $this->get('resource_id'), $this);
		$this->_Tinhte_XenTag_unindexFromSearch();

		return parent::_postDelete();
	}

}

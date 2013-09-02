<?php

class Tinhte_XenTag_Search_DataHandler_Resource extends XenForo_Search_DataHandler_Abstract
{

	protected $_resourceModel = null;

	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		$metadata = array();

		if (isset($data[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]))
		{
			// sondh@2012-11-05
			// added isset check before trying to unserialize the tags
			// or this may raise an exception (it happens because
			// XenForo_DataWriter::getMergedData doesn't return an array with all the fields
			// the array only includes new or existing data...
			// similar to Tinhte_XenTag_Search_DataHandler_Page
			$tagsOrTexts = Tinhte_XenTag_Helper::unserialize($data[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]);
		}
		else
		{
			$tagsOrTexts = array();
		}

		$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);
		$metadata[Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS] = Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($tagTexts);

		$indexer->insertIntoIndex(Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE, $data['resource_id'], $data['title'], $data['tag_line'], XenForo_Application::$time, XenForo_Visitor::getUserId(), 0, $metadata);
	}

	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		$indexer->updateIndex(Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE, $data['resource_id'], $fieldUpdates);
	}

	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		$ids = array();

		foreach ($dataList AS $data)
		{
			$ids[] = $data['resource_id'];
		}

		$indexer->deleteFromIndex(Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE, $ids);
	}

	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		if (!Tinhte_XenTag_Option::xfrmFound())
		{
			// XFRM is not installed/enabled
			// do not rebuild index
			return false;
		}

		$ids = $this->_getResourceModel()->getResourceIdsInRange($lastId, $batchSize);

		if (!$ids)
		{
			return false;
		}

		$this->quickIndex($indexer, $ids);

		return max($ids);
	}

	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		$resources = $this->_getResourceModel()->getResourcesByIds($contentIds);

		foreach ($resources AS $resource)
		{
			$this->insertIntoIndex($indexer, $resource);
		}

		return true;
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		$resources = $this->_getResourceModel()->getResourcesByIds($ids, array('join' => XenResource_Model_Resource::FETCH_CATEGORY + XenResource_Model_Resource::FETCH_USER));

		return $resources;
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		$errorPhraseKey = '';
		$null = null;

		return $this->_getResourceModel()->canViewResource($result, $result, $errorPhraseKey, $null, $viewingUser);
	}

	public function getResultDate(array $result)
	{
		return $result['resource_date'];
	}

	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		return $view->createTemplateObject('tinhte_xentag_search_result_resource', array(
			'resource' => $result,
			'search' => $search
		));
	}

	public function getSearchContentTypes()
	{
		return array(Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE);
	}

	public function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo, array $constraints)
	{
		$result = Tinhte_XenTag_Integration::processConstraint($sourceHandler, $constraint, $constraintInfo, $constraints);
		if ($result !== false)
		{
			return $result;
		}

		return parent::processConstraint($sourceHandler, $constraint, $constraintInfo, $constraints);
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		if (!$this->_resourceModel)
		{
			$this->_resourceModel = XenForo_Model::create('XenResource_Model_Resource');
		}

		return $this->_resourceModel;
	}

}

<?php

class Tinhte_XenTag_Search_DataHandler_General extends XenForo_Search_DataHandler_Abstract
{
	protected $_searchContentTypes = array();

	public function setSearchContentTypes(array $searchContentTypes)
	{
		$this->_searchContentTypes = $searchContentTypes;
	}

	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		throw new XenForo_Exception('not implemented');
	}

	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		throw new XenForo_Exception('not implemented');
	}

	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function getResultDate(array $result)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		throw new XenForo_Exception('not implemented');
	}

	public function getSearchContentTypes()
	{
		return $this->_searchContentTypes;
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

}

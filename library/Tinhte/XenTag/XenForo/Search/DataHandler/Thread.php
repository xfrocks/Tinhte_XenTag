<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Thread extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Thread
{
	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		if (isset($data[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]))
		{
			// sondh@2012-11-05
			// added isset check before trying to unserialize the tags
			// read more about this in
			// Tinhte_XenTag_Search_DataHandler_Page::_insertIntoIndex
			$tagsOrTexts = Tinhte_XenTag_Helper::unserialize($data[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		}
		else
		{
			$tagsOrTexts = array();
		}

		if (!empty($tagsOrTexts))
		{
			$extraMetadata = array();
			$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);
			$extraMetadata[Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS] = Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($tagTexts);
			$indexer = new Tinhte_XenTag_XenForo_Search_Indexer($indexer, $extraMetadata);
		}

		$result = parent::_insertIntoIndex($indexer, $data, $parentData);
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

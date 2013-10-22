<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Post extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Post
{
	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		$message = $data['message'];
		$message = trim(XenForo_Helper_String::stripQuotes($message, 0));
		$tagTexts = Tinhte_XenTag_Integration::parseHashtags($message);

		if (!empty($tagTexts))
		{
			$extraMetadata = array();
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

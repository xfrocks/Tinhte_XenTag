<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Post extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Post {
	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null) {
		$tags = Tinhte_XenTag_Helper::unserialize($parentData[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		
		if (!empty($tags)) {
			// call this to make sure Tinhte_XenTag_XenForo_Search_SourceHandler is available
			XenForo_Search_SourceHandler_Abstract::getDefaultSourceHandler();
			
			Tinhte_XenTag_XenForo_Search_SourceHandler::setExtraMetaData(array(
				Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS => Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($tags), 
			));
		}
		
		$result = parent::_insertIntoIndex($indexer, $data, $parentData);
	}
}
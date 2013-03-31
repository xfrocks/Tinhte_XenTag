<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Thread extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Thread {
	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null) {
		if (isset($data[Tinhte_XenTag_Constants::FIELD_PAGE_TAGS])) {
			// sondh@2012-11-05
			// added isset check before trying to unserialize the tags
			// read more about this in Tinhte_XenTag_Search_DataHandler_Page::_insertIntoIndex
			$tags = Tinhte_XenTag_Helper::unserialize($data[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		} else {
			$tags = array();
		}
		
		Tinhte_XenTag_Integration::insertIntoIndex($tags, $this);
		
		$result = parent::_insertIntoIndex($indexer, $data, $parentData);
	}
}
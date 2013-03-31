<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Thread extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Thread {
	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null) {
		$tags = Tinhte_XenTag_Helper::unserialize($data[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		Tinhte_XenTag_Integration::insertIntoIndex($tags, $this);
		
		$result = parent::_insertIntoIndex($indexer, $data, $parentData);
	}
}
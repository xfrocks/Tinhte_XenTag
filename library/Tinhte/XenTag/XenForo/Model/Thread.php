<?php

class Tinhte_XenTag_XenForo_Model_Thread extends XFCP_Tinhte_XenTag_XenForo_Model_Thread {
	
	public function prepareApiDataForThread(array $thread)
	{
		$data = parent::prepareApiDataForThread($thread);
		
		$tags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		$data[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS] = Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearchMapping($tags);
		
		return $data;
	}
	
}
<?php

class Tinhte_XenTag_XenForo_Model_Thread extends XFCP_Tinhte_XenTag_XenForo_Model_Thread {
	
	const CONDITIONS_THREAD_ID = 'Tinhte_XenTag_thread_id';
	
	public function prepareThreadConditions(array $conditions, array &$fetchOptions) {
		$result = parent::prepareThreadConditions($conditions, $fetchOptions);
	
		$sqlConditions = array($result);
		$db = $this->_getDb();
		
		if (isset($conditions[self::CONDITIONS_THREAD_ID])) {
			if (is_array($conditions[self::CONDITIONS_THREAD_ID])) {
				$sqlConditions[] = "thread.thread_id IN (" . $this->_getDb()->quote($conditions[self::CONDITIONS_THREAD_ID]) . ")";
			} else {
				$sqlConditions[] = "thread.thread_id = " . $this->_getDb()->quote($conditions[self::CONDITIONS_THREAD_ID]);
			}
		}
		
		if (count($sqlConditions) > 1) {
			// some of our conditions have been found
			return $this->getConditionsForClause($sqlConditions);
		} else {
			return $result;
		}
	}
	
	public function prepareApiDataForThread(array $thread) {
		$data = parent::prepareApiDataForThread($thread);
		
		$tags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		$data[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS] = Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearchMapping($tags);
		
		return $data;
	}
	
}
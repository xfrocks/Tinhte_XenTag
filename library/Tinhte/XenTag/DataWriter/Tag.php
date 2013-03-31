<?php

class Tinhte_XenTag_DataWriter_Tag extends _Tinhte_XenTag_DataWriter_Tag {
	
	protected function _getFields() {
		$fields = parent::_getFields();
		
		$fields['xf_tinhte_xentag_tag']['tag_text']['verification'] = array('$this', '_verifyText');
		$fields['xf_tinhte_xentag_tag']['created_date']['default'] = XenForo_Application::$time;
		
		return $fields;
	}
	
	protected function _verifyText(&$text) {
		if (strpos($text, ',') !== false) {
			$this->error(new XenForo_Phrase('tinhte_xentag_tag_can_not_contain_comma'), 'tag_text');
			return false;
		}
		
		return true;
	}
	
}

class _Tinhte_XenTag_DataWriter_Tag extends XenForo_DataWriter {

	/* Start auto-generated lines of code. Change made will be overwriten... */
	
	protected function _getFields() {
		return array(
			'xf_tinhte_xentag_tag' => array(
				'tag_id' => array('type' => 'uint', 'autoIncrement' => true),
				'tag_text' => array('type' => 'string', 'required' => true, 'maxLength' => 100),
				'created_date' => array('type' => 'uint', 'required' => true),
				'created_user_id' => array('type' => 'uint', 'required' => true),
				'content_count' => array('type' => 'uint', 'default' => 0)
			)
		);
	}

	protected function _getExistingData($data) {
		if (!$id = $this->_getExistingPrimaryKey($data, 'tag_id')) {
			return false;
		}

		return array('xf_tinhte_xentag_tag' => $this->_getTagModel()->getTagById($id));
	}

	protected function _getUpdateCondition($tableName) {
		$conditions = array();
		
		foreach (array('tag_id') as $field) {
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}
		
		return implode(' AND ', $conditions);
	}
	
	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel() {
		return $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
	}
	

	
	/* End auto-generated lines of code. Feel free to make changes below */
}
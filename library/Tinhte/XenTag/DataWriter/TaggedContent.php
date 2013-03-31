<?php

class Tinhte_XenTag_DataWriter_TaggedContent extends _Tinhte_XenTag_DataWriter_TaggedContent {
	
	protected function _getFields() {
		$fields = parent::_getFields();
		
		$fields['xf_tinhte_xentag_tagged_content']['tagged_date']['default'] = XenForo_Application::$time;
		
		return $fields;
	}
	
	protected function _postSave() {
		if ($this->isInsert()) {
			$this->_db->query('
				UPDATE `xf_tinhte_xentag_tag`
				SET content_count = content_count + 1
				WHERE tag_id = ?',
				$this->get('tag_id')
			);
		}
	}
	
	protected function _postDelete() {
		$this->_db->query('
			UPDATE `xf_tinhte_xentag_tag`
			SET content_count = IF(content_count > 0, content_count - 1, 0)
			WHERE tag_id = ?',
			$this->get('tag_id')
		);
	}
	
}

class _Tinhte_XenTag_DataWriter_TaggedContent extends XenForo_DataWriter {

	/* Start auto-generated lines of code. Change made will be overwriten... */
	
	protected function _getFields() {
		return array(
			'xf_tinhte_xentag_tagged_content' => array(
				'tag_id' => array('type' => 'uint', 'required' => true),
				'content_type' => array('type' => 'string', 'required' => true, 'maxLength' => 25),
				'content_id' => array('type' => 'uint', 'required' => true),
				'tagged_user_id' => array('type' => 'uint', 'required' => true),
				'tagged_date' => array('type' => 'uint', 'required' => true)
			)
		);
	}

	protected function _getExistingData($data) {
		if (!$id = $this->_getExistingPrimaryKey($data, 'n/a')) {
			return false;
		}

		return array('xf_tinhte_xentag_tagged_content' => $this->_getTaggedContentModel()->getTaggedContentById($id));
	}

	protected function _getUpdateCondition($tableName) {
		$conditions = array();
		
		foreach (array('tag_id', 'content_type', 'content_id') as $field) {
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}
		
		return implode(' AND ', $conditions);
	}
	
	/**
	 * @return Tinhte_XenTag_Model_TaggedContent
	 */
	protected function _getTaggedContentModel() {
		return $this->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
	}
	

	
	/* End auto-generated lines of code. Feel free to make changes below */
}
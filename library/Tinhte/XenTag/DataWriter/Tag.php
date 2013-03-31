<?php

class Tinhte_XenTag_DataWriter_Tag extends XenForo_DataWriter {
	
	protected function _postSave() {
		if ($this->isInsert() OR $this->isChanged('tag_text')) {
			$this->_rebuildCache();
		}
		
		return parent::_postSave();
	}
	
	protected function _postDelete() {
		$taggedContentModel = $this->_getTaggedContentModel();
		
		/* @var $threadModel XenForo_Model_Thread */
		$threadModel = $this->getModelFromCache('XenForo_Model_Thread');
		
		$taggeds = $taggedContentModel->getAllTaggedContent(array('tag_id' => $this->get('tag_id')));
		
		$threadIds = array();
		foreach ($taggeds as $tagged) {
			if ($tagged['content_type'] == 'thread') {
				$threadIds[] = $tagged['content_id'];
			}
		}
		
		$threads = $threadModel->getThreadsByIds($threadIds);
		foreach ($threads as $thread) {
			$tags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
			
			$tagKey = array_search($this->get('tag_text'), $tags);
			if (empty($tagKey)) continue; // the tag is not found in the thread
			unset($tags[$tagKey]);
			
			/* @var $dw XenForo_DataWriter_Discussion_Thread*/
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			
			$dw->setExistingData($thread, true); // save queries
			$dw->Tinhte_XenTag_setTags($tags);
			$dw->save();
		}
		
		$this->_rebuildCache();
		
		return parent::_postDelete();
	}
	
	protected function _rebuildCache() {
		$this->_getTagModel()->rebuildCache();
	}
	
	protected function _verifyText(&$text) {
		if (strpos($text, ',') !== false) {
			$this->error(new XenForo_Phrase('tinhte_xentag_tag_can_not_contain_comma'), 'tag_text');
			return false;
		}
		
		if (strlen($text) > Tinhte_XenTag_Option::get('tagMaxLength')) {
			$this->error(new XenForo_Phrase('tinhte_xentag_tag_can_not_longer_than_x', array('maxLength' => Tinhte_XenTag_Option::get('tagMaxLength'))), 'tag_text');
			return false;
		}
		
		return true;
	}

	protected function _getFields() {
		return array(
			'xf_tinhte_xentag_tag' => array(
				'tag_id' => array('type' => 'uint', 'autoIncrement' => true),
				'tag_text' => array(
					'type' => 'string', 'required' => true, 'maxLength' => 100,
					'verification' => array('$this', '_verifyText'),
				),
				'created_date' => array('type' => 'uint', 'required' => true, 'default' => XenForo_Application::$time),
				'created_user_id' => array('type' => 'uint', 'required' => true),
				'content_count' => array('type' => 'uint', 'default' => 0),
				
				// since 0.10
				'latest_tagged_contents' => array('type' => 'serialzied'),
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

	/**
	 * @return Tinhte_XenTag_Model_TaggedContent
	 */
	protected function _getTaggedContentModel() {
		return $this->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
	}
	
}
<?php

class Tinhte_XenTag_DataWriter_TaggedContent extends XenForo_DataWriter
{

	protected function _postSave()
	{
		$this->_updateTag($this->isInsert() ? 1 : 0);

		return parent::_postSave();
	}

	protected function _postDelete()
	{
		$this->_updateTag(-1);

		return parent::_postDelete();
	}

	protected function _updateTag($contentCountDelta)
	{
		$this->getModelFromCache('Tinhte_XenTag_Model_Tag')->updateTag($this->get('tag_id'), $contentCountDelta);
	}

	protected function _getFields()
	{
		return array('xf_tinhte_xentag_tagged_content' => array(
				'tag_id' => array(
					'type' => self::TYPE_UINT,
					'required' => true
				),
				'content_type' => array(
					'type' => self::TYPE_STRING,
					'required' => true,
					'maxLength' => 25
				),
				'content_id' => array(
					'type' => self::TYPE_UINT,
					'required' => true
				),
				'tagged_user_id' => array(
					'type' => self::TYPE_UINT,
					'required' => true
				),
				'tagged_date' => array(
					'type' => self::TYPE_UINT,
					'required' => true,
					'default' => XenForo_Application::$time
				)
			));
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'n/a'))
		{
			return false;
		}

		return array('xf_tinhte_xentag_tagged_content' => $this->_getTaggedContentModel()->getTaggedContentById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('tag_id', 'content_type', 'content_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	/**
	 * @return Tinhte_XenTag_Model_TaggedContent
	 */
	protected function _getTaggedContentModel()
	{
		return $this->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
	}

}

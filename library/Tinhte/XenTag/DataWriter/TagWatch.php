<?php

class Tinhte_XenTag_DataWriter_TagWatch extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array('xf_tinhte_xentag_tag_watch' => array(
				'user_id' => array(
					'type' => self::TYPE_UINT,
					'required' => true
				),
				'tag_id' => array(
					'type' => self::TYPE_UINT,
					'required' => true
				),
				'send_alert' => array(
					'type' => self::TYPE_BOOLEAN,
					'default' => 0
				),
				'send_email' => array(
					'type' => self::TYPE_BOOLEAN,
					'default' => 0
				)
			));
	}

	protected function _getExistingData($data)
	{
		if (!is_array($data))
		{
			return false;
		}
		elseif (isset($data['user_id'], $data['tag_id']))
		{
			$userId = $data['user_id'];
			$tagId = $data['tag_id'];
		}
		elseif (isset($data[0], $data[1]))
		{
			$userId = $data[0];
			$tagId = $data[1];
		}
		else
		{
			return false;
		}

		return array('xf_tinhte_xentag_tag_watch' => $this->_getTagWatchModel()->getUserTagWatchByIds($userId, $tagId));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'user_id = ' . $this->_db->quote($this->getExisting('user_id')) . ' AND tag_id = ' . $this->_db->quote($this->getExisting('tag_id'));
	}

	/**
	 * @return Tinhte_XenTag_Model_TagWatch
	 */
	protected function _getTagWatchModel()
	{
		return $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
	}

}

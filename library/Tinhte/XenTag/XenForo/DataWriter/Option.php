<?php

class Tinhte_XenTag_XenForo_DataWriter_Option extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Option
{
	protected function _postSave()
	{
		if ($this->isChanged('option_value') AND strpos($this->get('option_id'), 'Tinhte_XenTag_trending') === 0)
		{
			$this->getModelFromCache('XenForo_Model_DataRegistry')->delete(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TRENDING);
		}

		return parent::_postSave();
	}

}

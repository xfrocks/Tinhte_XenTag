<?php

class Tinhte_XenTag_XenForo_DataWriter_AddOn extends XFCP_Tinhte_XenTag_XenForo_DataWriter_AddOn
{
	protected function _postSaveAfterTransaction()
	{
		if ($this->isInsert() AND $this->get('addon_id') == 'XenResource')
		{
			$addOn = $this->_getAddOnModel()->getAddOnById('Tinhte_XenTag');

			if (!empty($addOn))
			{
				Tinhte_XenTag_Installer::install($addOn, $addOn);
			}
		}

		return parent::_postSaveAfterTransaction();
	}

}

<?php

class Tinhte_XenTag_XenResource_Model_Resource extends XFCP_Tinhte_XenTag_XenResource_Model_Resource
{
	public function Tinhte_XenTag_getMaximumTags(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'resource', 'TXT_resourceMaximumTags');
	}
}

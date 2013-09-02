<?php

class Tinhte_XenTag_XenResource_ViewPublic_Resource_Description extends XFCP_Tinhte_XenTag_XenResource_ViewPublic_Resource_Description
{
	public function renderHtml()
	{
		parent::renderHtml();

		Tinhte_XenTag_ContentWrapper_Resource::wrap($this->_params['update'], $this->_params['resource']);
	}

}

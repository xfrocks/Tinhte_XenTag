<?php

class Tinhte_XenTag_XenForo_ViewPublic_Thread_View extends XFCP_Tinhte_XenTag_XenForo_ViewPublic_Thread_View
{
	public function renderHtml()
	{
		parent::renderHtml();

		Tinhte_XenTag_ContentWrapper_Post::wrap($this->_params);
	}

}

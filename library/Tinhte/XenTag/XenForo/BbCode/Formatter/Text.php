<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_Text extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_Text
{
	public function getTags()
	{
		$tags = parent::getTags();
		
		$tags['hashtag'] = array('replace' => array('', ''),);
		
		return $tags;
	}
}

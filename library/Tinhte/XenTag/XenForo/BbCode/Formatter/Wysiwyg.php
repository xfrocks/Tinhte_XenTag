<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_Wysiwyg extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_Wysiwyg
{
	public function getTags()
	{
		$tags = parent::getTags();
		
		$tags['hashtag'] = array('replace' => array('<span class="Tinhte_XenTag_HashTag" style="color: blue; text-decoration: underline">', '</span>'),);
		
		return $tags;
	}
}

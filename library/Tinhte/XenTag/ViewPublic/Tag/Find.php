<?php

class Tinhte_XenTag_ViewPublic_Tag_Find extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['tags'] AS $tag)
		{
			$results[$tag['tag_text']] = array('username' => htmlspecialchars($tag['tag_text']));
		}

		return array('results' => $results);
	}

}

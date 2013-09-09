<?php

class Tinhte_XenTag_ViewPublic_Tag_Find extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['tags'] AS $tag)
		{
			$results[$tag['tag_text']] = $this->_getPresentationForResult($tag);
		}

		return array('results' => $results);
	}

	protected function _getPresentationForResult($tag)
	{
		if (XenForo_Application::$versionId < 1020000)
		{
			return array('username' => htmlspecialchars($tag['tag_text']));
		}
		else
		{
			return htmlspecialchars($tag['tag_text']);
		}
	}

}

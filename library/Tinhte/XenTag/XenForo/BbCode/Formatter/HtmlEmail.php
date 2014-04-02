<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_HtmlEmail extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_HtmlEmail
{
	public function getTags()
	{
		$tags = parent::getTags();

		$tags['hashtag'] = array(
			'plainChildren' => true,
			'callback' => array(
				$this,
				'renderTagHashtag'
			)
		);

		return $tags;
	}

	public function renderTagHashtag(array $tag, array $rendererStates)
	{
		$tagText = $this->stringifyTree($tag['children']);
		if (substr($tagText, 0, 1) === '#')
		{
			$tagText = substr($tagText, 1);
		}

		$tag = array('tag_text' => $tagText);
		$displayText = $tagText;

		return '#<a href="' . XenForo_Link::buildPublicLink('tags', $tag) . '" style="text-decoration: none">' . htmlentities($displayText) . '</a>';
	}

}

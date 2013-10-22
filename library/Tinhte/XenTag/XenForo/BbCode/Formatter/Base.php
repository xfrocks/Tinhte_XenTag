<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_Base extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_Base
{

	public function getTags()
	{
		// intentionally not check $this->_tags
		$tags = parent::getTags();

		$tags['tag'] = array(
			'plainChildren' => true,
			'callback' => array(
				$this,
				'renderTagTag'
			)
		);

		$tags['hashtag'] = array(
			'plainChildren' => true,
			'callback' => array(
				$this,
				'renderTagHashtag'
			)
		);

		return $tags;
	}

	public function preLoadTemplates(XenForo_View $view)
	{
		$view->preLoadTemplate('tinhte_xentag_bb_code_tag_tag');
		$view->preLoadTemplate('tinhte_xentag_bb_code_tag_hashtag');

		return parent::preLoadTemplates($view);
	}

	public function renderTagTag(array $tag, array $rendererStates)
	{
		$tagText = $this->stringifyTree($tag['children']);
		$displayText = $tagText;

		// support option version of this tag
		// the tag text can be put as option (must be base64 encoded)
		if (!empty($tag['option']))
		{
			$option = $tag['option'];
			$optionDecoded = @base64_decode($option);
			if (!empty($optionDecoded))
			{
				$tagText = $optionDecoded;
			}
		}

		$tag = array('tag_text' => $tagText);

		if ($this->_view)
		{
			$template = $this->_view->createTemplateObject('tinhte_xentag_bb_code_tag_tag', array(
				'tag' => $tag,
				'displayText' => $displayText,
			));
			return $template->render();
		}
		else
		{
			// sometime we don't have a view
			// so just render everything ourself...
			return '<a href="' . XenForo_Link::buildPublicLink('tags', $tag) . '" class="Tinhte_XenTag_TagLink">' . htmlentities($displayText) . '</a>';
		}
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

		if ($this->_view)
		{
			$template = $this->_view->createTemplateObject('tinhte_xentag_bb_code_tag_hashtag', array(
				'tag' => $tag,
				'displayText' => $displayText,
			));
			return $template->render();
		}
		else
		{
			// sometime we don't have a view
			// so just render everything ourself...
			return '#<a href="' . XenForo_Link::buildPublicLink('tags', $tag) . '" class="Tinhte_XenTag_HashTag">' . htmlentities($displayText) . '</a>';
		}
	}

}

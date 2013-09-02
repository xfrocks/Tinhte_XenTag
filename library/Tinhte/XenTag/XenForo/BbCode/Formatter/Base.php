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

		return $tags;
	}

	public function preLoadTemplates(XenForo_View $view)
	{
		$view->preLoadTemplate('tinhte_xentag_bb_code_tag_tag');

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

		$tag = array('tag_text' => $tagText, );

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
			return '<a href="' . XenForo_Link::buildPublicLink('tags', $tag) . '">' . htmlentities($displayText) . '</a>';
		}
	}

}

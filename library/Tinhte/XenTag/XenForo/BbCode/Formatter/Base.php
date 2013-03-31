<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_Base extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_Base {
	
	public function getTags() {
		// intentionally not check $this->_tags
		$tags = parent::getTags();
		
		$tags['tag'] = array(
			'hasOption' => false,
			'plainChildren' => true,
			'callback' => array($this, 'renderTagTag')
		);
		
		return $tags;
	}
	
	public function preLoadTemplates(XenForo_View $view) {
		$view->preLoadTemplate('tinhte_xentag_bb_code_tag_tag');
		
		return parent::preLoadTemplates($view);
	}
	
	public function renderTagTag(array $tag, array $rendererStates) {
		$tagText = $this->stringifyTree($tag['children']);

		$tag = array(
			'tag_text' => $tagText,
		);

		$template = $this->_view->createTemplateObject('tinhte_xentag_bb_code_tag_tag', array(
			'tag' => $tag
		));
		return $template->render();
	}
	
}
<?php

class Tinhte_XenTag_ViewAdmin_Tag_List extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$this->_prepareParams();
	}

	public function renderJson()
	{
		$this->_prepareParams();

		if (!empty($this->_params['filterView']))
		{
			$this->_templateName = 'tinhte_xentag_tag_list_item';
		}

		return null;
	}

	protected function _prepareParams()
	{
		$tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');
		
		$tags =& $this->_params['tags'];
		foreach ($tags as &$tag)
		{
			$tag['_tagLink'] = $tagModel->getTagLink($tag);
		}
	}
}

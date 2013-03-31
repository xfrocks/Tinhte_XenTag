<?php

class Tinhte_XenTag_ViewAdmin_Tag_List extends XenForo_ViewAdmin_Base {
	public function renderJson() {
		if (!empty($this->_params['filterView'])) {
			$this->_templateName = 'tinhte_xentag_tag_list_item';
		}

		return null;
	}
}
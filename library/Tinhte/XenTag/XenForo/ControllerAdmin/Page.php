<?php

class Tinhte_XenTag_XenForo_ControllerAdmin_Page extends XFCP_Tinhte_XenTag_XenForo_ControllerAdmin_Page
{
	public function actionSave()
	{
		// register this controller and let's the parent work its job
		// we will get called again from
		// Tinhte_XenTag_XenForo_DataWriter_Page::_preSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE] = $this;

		return parent::actionSave();
	}

	public function Tinhte_XenTag_actionSave(XenForo_DataWriter_Page $dw)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

		$tags = $tagModel->processInput($this->_input);

		if ($tags !== false)
		{
			$dw->Tinhte_XenTag_setTags($tags);
		}

		// just to be safe...
		unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE]);
	}

}

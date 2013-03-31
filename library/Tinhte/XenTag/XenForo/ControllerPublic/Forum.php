<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Forum extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Forum {
	
	public function actionAddThread() {
		// register this controller and let's the parent work its job
		// we will get called again from Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread::_discussionPreSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD] = $this;
		
		return parent::actionAddThread();
	}
	
	public function Tinhte_XenTag_actionAddThread(XenForo_DataWriter_Discussion_Thread $dw) {
		$tags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->processInput($this->_input);
		
		if ($tags !== false) {
			$dw->Tinhte_XenTag_setTags($tags);
		}
	}
	
}
<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Post extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Post {
	
	public function actionSave() {
		// register this controller and let's the parent work its job
		// we will get called again from Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post::_messagePreSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE] = $this;
		
		return parent::actionSave();
	}
	
	public function Tinhte_XenTag_actionSave(XenForo_DataWriter_DiscussionMessage_Post $dw) {
		$tags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->processInput($this->_input);
		
		if ($tags !== false) {
			/* @var $threadDw XenForo_DataWriter_Discussion_Thread */
			$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$threadDw->setExistingData($dw->get('thread_id'));
			$threadDw->Tinhte_XenTag_setTags($tags);
			$threadDw->save();
		}
	}
	
}
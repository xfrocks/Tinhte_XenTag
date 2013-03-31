<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Thread extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Thread {
	
	protected function _getDefaultViewParams(array $forum, array $thread, array $posts, $page = 1, array $viewParams = array()) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		$viewParams['Tinhte_XenTag_canEdit'] = $tagModel->canTagThread($thread, $forum);
		
		return parent::_getDefaultViewParams($forum, $thread, $posts, $page, $viewParams);
	}
	
	public function actionEdit() {
		$response = parent::actionEdit();
		
		if ($response instanceof XenForo_ControllerResponse_View) {
			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
			
			$response->params['Tinhte_XenTag_canEdit'] = $tagModel->canTagThread($response->params['thread'], $response->params['forum']);
			
			if ($this->_input->filterSingle('_Tinhte_XenTag_TagsInlineEditor', XenForo_Input::UINT)) {
				$response->viewName = 'Tinhte_XenTag_ViewPublic_Thread_EditTags';
				$response->templateName = 'tinhte_xentag_thread_edit_tags';
			}
		}
		
		return $response;
	}
	
	public function actionSave() {
		// register this controller and let's the parent work its job
		// we will get called again from Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread::_discussionPreSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_SAVE] = $this;
		
		return parent::actionSave();
	}
	
	public function Tinhte_XenTag_actionSave(XenForo_DataWriter_Discussion_Thread $dw) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		$forum = $dw->getExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM);
		$thread = $dw->getMergedData();
		
		if ($tagModel->canTagThread($thread, $forum)) {
			$tags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->processInput($this->_input);
			
			if ($tags !== false) {
				$dw->Tinhte_XenTag_setTags($tags);
			}
		}
	}
	
	public function actionEditTags() {
		if ($this->isConfirmedPost()) {
			$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

			$ftpHelper = $this->getHelper('ForumThreadPost');
			list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);
	
			$this->_assertCanEditThread($thread, $forum);
	
			/* @var $threadModel XenForo_Model_Thread */
			$threadModel = $this->_getThreadModel();
			
			$tags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->processInput($this->_input);
		
			if ($tags !== false) {
				/* @var $dw XenForo_DataWriter_Discussion_Thread */
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
				$dw->setExistingData($thread, true);
				$dw->Tinhte_XenTag_setTags($tags);
				$dw->save();
				
				$thread = $dw->getMergedData();
				
				$viewParams = array(
					'thread' => $thread,
				);
				
				return $this->responseView(
					'Tinhte_XenTag_ViewPublic_Thread_EditTagsSave',
					'tinhte_xentag_thread_edit_tags_save',
					$viewParams
				);
			} else {
				return $this->responseNoPermission();
			}
		} else {
			$response = parent::actionEdit(); // it's more safe this way
			
			if ($response instanceof XenForo_ControllerResponse_View) {
				$response->viewName = 'Tinhte_XenTag_ViewPublic_Thread_EditTags';
				$response->templateName = 'tinhte_xentag_thread_edit_tags';
			}
			
			return $response;
		}
	}
}
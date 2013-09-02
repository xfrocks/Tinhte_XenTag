<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Forum extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Forum
{

	public function actionCreateThread()
	{
		$response = parent::actionCreateThread();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

			$response->params['Tinhte_XenTag_canTag'] = $tagModel->canTagThread(false, $response->params['forum']);
		}

		return $response;
	}

	public function actionAddThread()
	{
		// register this controller and let's the parent work its job
		// we will get called again from
		// Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread::_discussionPreSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD] = $this;

		return parent::actionAddThread();
	}

	public function Tinhte_XenTag_actionAddThread(XenForo_DataWriter_Discussion_Thread $dw)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

		$forum = $dw->Tinhte_XenTag_getForumData();
		if ($tagModel->canTagThread(false, $forum))
		{
			// only save tags if this user has the permission
			$tags = $tagModel->processInput($this->_input);

			if ($tags !== false)
			{
				$dw->Tinhte_XenTag_setTags($tags);
			}
		}

		// sondh@2012-08-11
		// just to be safe...
		unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD]);
	}

}

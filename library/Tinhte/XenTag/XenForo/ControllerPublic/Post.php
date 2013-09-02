<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Post extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Post
{

	public function actionEdit()
	{
		$response = parent::actionEdit();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

			$response->params['Tinhte_XenTag_canEdit'] = $tagModel->canTagThread($response->params['thread'], $response->params['forum']);
		}

		return $response;
	}

	public function actionSave()
	{
		// register this controller and let's the parent work its job
		// we will get called again from
		// Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post::_messagePreSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE] = $this;

		return parent::actionSave();
	}

	public function Tinhte_XenTag_actionSave(XenForo_DataWriter_DiscussionMessage_Post $dw)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

		$forum = $dw->getExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM);
		$thread = $this->_getPostModel()->Tinhte_XenTag_getThread($dw->get('post_id'));

		if ($tagModel->canTagThread($thread, $forum))
		{
			$tags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->processInput($this->_input);

			if ($tags !== false)
			{
				/* @var $threadDw XenForo_DataWriter_Discussion_Thread */
				$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
				$threadDw->setExistingData($dw->get('thread_id'));
				$threadDw->Tinhte_XenTag_setTags($tags);
				$threadDw->save();
			}
		}

		// sondh@2012-08-11
		// just to be safe...
		unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE]);
	}

}

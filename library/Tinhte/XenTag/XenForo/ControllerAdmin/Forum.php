<?php

class Tinhte_XenTag_XenForo_ControllerAdmin_Forum extends XFCP_Tinhte_XenTag_XenForo_ControllerAdmin_Forum
{

	public function actionEdit()
	{
		$response = parent::actionEdit();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			if (isset($response->params['forum']))
			{
				$forum = &$response->params['forum'];

				if (isset($forum[Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS]))
				{
					$forum[Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS] = Tinhte_XenTag_Helper::unserialize($forum[Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS]);
				}
				else
				{
					$forum[Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS] = array();
				}
			}
		}

		return $response;
	}

	public function actionSave()
	{
		// register this controller and let's the parent work its job
		// we will get called again from
		// Tinhte_XenTag_XenForo_DataWriter_Forum::_preSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE] = $this;

		return parent::actionSave();
	}

	public function Tinhte_XenTag_actionSave(XenForo_DataWriter_Forum $dw)
	{
		$options = $this->_input->filterSingle(Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS, XenForo_Input::ARRAY_SIMPLE);
		$dw->set(Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS, $options);

		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		$tags = $tagModel->processInput($this->_input);
		if ($tags !== false)
		{
			$dw->Tinhte_XenTag_setTags($tags);
		}

		// just to be safe...
		unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE]);
	}

}

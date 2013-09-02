<?php

class Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post
{

	protected function _messagePreSave()
	{
		// checks for our controller and call it first
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_POST_SAVE]->Tinhte_XenTag_actionSave($this);
		}

		return parent::_messagePreSave();
	}

}

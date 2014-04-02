<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Watched extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Watched
{
	protected function _takeEmailAction(array $user, $action, $type, $id)
	{
		if ($type == '' || $type == 'tag')
		{
			if ($id)
			{
				if (empty($action))
				{
					// oops, delete it
					$this->getModelFromCache('Tinhte_XenTag_Model_TagWatch')->setTagWatchState($user['user_id'], $id);
				}
				else
				{
					$this->getModelFromCache('Tinhte_XenTag_Model_TagWatch')->setTagWatchState($user['user_id'], $id, null, 0);
				}

			}
			else
			{
				$this->getModelFromCache('Tinhte_XenTag_Model_TagWatch')->setTagWatchStateForAll($user['user_id'], $action);
			}
		}
	}

	protected function _getEmailActionConfirmPhrase(array $user, $action, $type, $id)
	{
		if ($type == 'tag')
		{
			if ($id)
			{
				return new XenForo_Phrase('tinhte_xentag_you_sure_you_want_to_update_notification_settings_for_one_tag');
			}
			else
			{
				return new XenForo_Phrase('tinhte_xentag_you_sure_you_want_to_update_notification_settings_for_all_tag');
			}
		}

		return parent::_getEmailActionConfirmPhrase($user, $action, $type, $id);
	}

}

<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Watched extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Watched
{
	public function actionTags()
	{
		$tagsWatched = $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch')->getUserTagWatchByUserId(XenForo_Visitor::getUserId());

		$viewParams = array('tags' => $tagsWatched);

		return $this->responseView('Tinhte_XenTag_ViewPublic_Watched_Tags', 'tinhte_xentag_watch_tags', $viewParams);
	}

	public function actionTagsUpdate()
	{
		$this->_assertPostOnly();

		$input = $this->_input->filter(array(
			'tag_ids' => array(
				XenForo_Input::UINT,
				'array' => true
			),
			'do' => XenForo_Input::STRING
		));

		if (!empty($input['tag_ids']))
		{
			$tagWatches = $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch')->getUserTagWatchByUserIdAndTagIds(XenForo_Visitor::getUserId(), $input['tag_ids']);
		}
		else
		{
			$tagWatches = array();
		}

		foreach ($tagWatches AS $tagWatch)
		{
			$dw = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TagWatch');
			$dw->setExistingData($tagWatch, true);

			switch ($input['do'])
			{
				case 'stop':
					$dw->delete();
					break;

				case 'email':
					$dw->set('send_email', 1);
					$dw->save();
					break;

				case 'no_email':
					$dw->set('send_email', 0);
					$dw->save();
					break;

				case 'alert':
					$dw->set('send_alert', 1);
					$dw->save();
					break;

				case 'no_alert':
					$dw->set('send_alert', 0);
					$dw->save();
					break;
			}
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $this->getDynamicRedirect(XenForo_Link::buildPublicLink('watched/forums')));
	}

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

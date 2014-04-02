<?php

class Tinhte_XenTag_Model_TagWatch extends XenForo_Model
{
	public function getUserTagWatchByIds($userId, $tagId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_tinhte_xentag_tag_watch
			WHERE user_id = ?
				AND tag_id = ?
		', array(
			$userId,
			$tagId
		));
	}

	public function getUserTagWatchByUserId($userId)
	{
		return $this->fetchAllKeyed('
			SELECT tag_watch.*, tag.*, user.*
			FROM xf_tinhte_xentag_tag_watch AS tag_watch
			INNER JOIN xf_tinhte_xentag_tag AS tag
				ON (tag.tag_id = tag_watch.tag_id)
			LEFT JOIN xf_user AS user
				ON (user.user_id = tag.created_user_id)
			WHERE tag_watch.user_id = ?
		', 'tag_id', $userId);
	}

	public function getUserTagWatchByUserIdAndTagIds($userId, array $tagIds)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_tinhte_xentag_tag_watch
			WHERE user_id = ?
				AND tag_id IN (' . $this->_getDb()->quote($tagIds) . ')
		', array($userId));
	}

	public function getUsersWatchingTag($tagId, $contentPermissionType = '', $contentPermissionId = 0)
	{
		$contentPermissionSelect = '';
		$contentPermissionJoin = '';

		if (!empty($contentPermissionType) AND !empty($contentPermissionId))
		{
			$contentPermissionSelect = 'content_permission.cache_value AS content_permission_cache,';
			$contentPermissionJoin = '
				LEFT JOIN xf_permission_cache_content AS content_permission
					ON (content_permission.permission_combination_id = user.permission_combination_id
						AND content_permission.content_type = ' . $this->_getDb()->quote($contentPermissionType) . '
						AND content_permission.content_id = ' . $this->_getDb()->quote($contentPermissionId) . ')';
		}

		return $this->fetchAllKeyed('
			SELECT user.*,
				user_option.*,
				user_profile.*,
				tag_watch.send_alert,
				tag_watch.send_email,
				' . $contentPermissionSelect . '
				permission_combination.cache_value AS global_permission_cache
			FROM xf_tinhte_xentag_tag_watch AS tag_watch
			INNER JOIN xf_user AS user ON
				(user.user_id = tag_watch.user_id AND user.user_state = \'valid\' AND user.is_banned = 0)
			INNER JOIN xf_user_option AS user_option ON
				(user_option.user_id = user.user_id)
			INNER JOIN xf_user_profile AS user_profile ON
				(user_profile.user_id = user.user_id)
			' . $contentPermissionJoin . '
			LEFT JOIN xf_permission_combination AS permission_combination ON
				(permission_combination.permission_combination_id = user.permission_combination_id)
			WHERE tag_watch.tag_id = ?
				AND (tag_watch.send_alert <> 0 OR tag_watch.send_email <> 0)
		', 'user_id', array($tagId));
	}

	public function sendNotificationToWatchUsersOnTagged($tag, array $contentData = array(), array $contentPermissionConfig = array())
	{
		$userModel = $this->getModelFromCache('XenForo_Model_User');

		list($noEmail, $noAlert) = Tinhte_XenTag_Integration::getNoEmailAndAlert($contentData['content_type'], $contentData['content_id']);
		$emailed = array();
		$alerted = array();

		$emailTemplate = 'tinhte_xentag_watch_tag_' . $contentData['content_type'];
		if (XenForo_Application::get('options')->emailWatchedThreadIncludeMessage)
		{
			$parseBbCode = true;
		}
		else
		{
			$parseBbCode = false;
		}

		// fetch a full user record if we don't have one already
		if (!isset($contentData['avatar_width']) OR !isset($contentData['custom_title']))
		{
			$contentUser = $userModel->getUserById($contentData['user_id']);
			if ($contentUser)
			{
				$contentData = array_merge($contentUser, $contentData);
			}
			else
			{
				$contentData['avatar_width'] = 0;
				$contentData['custom_title'] = '';
			}
		}

		if (!empty($contentPermissionConfig['content_type']) AND !empty($contentPermissionConfig['content_id']))
		{
			$users = $this->getUsersWatchingTag($tag['tag_id'], $contentPermissionConfig['content_type'], $contentPermissionConfig['content_id']);
		}
		else
		{
			$users = $this->getUsersWatchingTag($tag['tag_id']);
		}

		foreach ($users AS $user)
		{
			if ($user['user_id'] == $contentData['user_id'])
			{
				// self notification? That's silly
				continue;
			}

			if ($userModel->isUserIgnored($user, $contentData['user_id']))
			{
				continue;
			}

			$globalPermissions = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if (!XenForo_Permission::hasPermission($globalPermissions, 'general', Tinhte_XenTag_Constants::PERM_USER_WATCH))
			{
				// no tag watch permission (or revoked)
				continue;
			}

			if (!empty($contentPermissionConfig['content_type']) AND !empty($contentPermissionConfig['content_id']) AND !empty($contentPermissionConfig['permissions']))
			{
				$contentPermissions = XenForo_Permission::unserializePermissions($user['content_permission_cache']);
				$contentPermissionFound = true;

				foreach ($contentPermissionConfig['permissions'] as $contentPermissionRequired)
				{
					if (!XenForo_Permission::hasContentPermission($contentPermissions, $contentPermissionRequired))
					{
						$contentPermissionFound = false;
					}
				}

				if (!$contentPermissionFound)
				{
					// no content permission
					continue;
				}
			}

			if ($user['send_email'] AND $user['email'] AND $user['user_state'] == 'valid' AND !in_array($user['user_id'], $noEmail))
			{
				if (!empty($contentData['message']) AND !isset($contentData['messageText']) AND $parseBbCode)
				{
					$bbCodeParserText = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Text'));
					$contentData['messageText'] = new XenForo_BbCode_TextWrapper($contentData['message'], $bbCodeParserText);

					$bbCodeParserHtml = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
					$contentData['messageHtml'] = new XenForo_BbCode_TextWrapper($contentData['message'], $bbCodeParserHtml);
				}

				if (!empty($contentData['title']) AND !isset($contentData['titleCensored']))
				{
					$contentData['titleCensored'] = XenForo_Helper_String::censorString($contentData['title']);
				}

				$user['email_confirm_key'] = $userModel->getUserEmailConfirmKey($user);

				$mail = XenForo_Mail::create($emailTemplate, array(
					'tag' => $tag,
					'contentType' => $contentData['content_type'],
					'contentId' => $contentData['content_id'],
					'contentData' => $contentData,
					'receiver' => $user
				), $user['language_id']);
				$mail->enableAllLanguagePreCache();
				$mail->queue($user['email'], $user['username']);

				$emailed[] = $user['user_id'];
			}

			if ($user['send_alert'] AND !in_array($user['user_id'], $noAlert))
			{
				call_user_func_array(array(
					'XenForo_Model_Alert',
					'alert'
				), array(
					$user['user_id'],
					$contentData['user_id'],
					$contentData['username'],
					$contentData['content_type'],
					$contentData['content_id'],
					'tinhte_xentag_tag_watch',
					array('tag' => $tag),
				));

				$alerted[] = $user['user_id'];
			}
		}

		Tinhte_XenTag_Integration::updateNoEmailAndAlert($contentData['content_type'], $contentData['content_id'], $emailed, $alerted);
	}

	public function setTagWatchState($userId, $tagId, $sendAlert = null, $sendEmail = null)
	{
		if (empty($userId))
		{
			return false;
		}

		$tagWatch = $this->getUserTagWatchByIds($userId, $tagId);

		if ($sendAlert === null AND $sendEmail === null)
		{
			if (!empty($tagWatch))
			{
				$dw = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TagWatch');
				$dw->setExistingData($tagWatch, true);
				$dw->delete();
			}
			return true;
		}

		$dw = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TagWatch');
		if (!empty($tagWatch))
		{
			$dw->setExistingData($tagWatch, true);
		}
		else
		{
			$dw->set('user_id', $userId);
			$dw->set('tag_id', $tagId);
		}
		if ($sendAlert !== null)
		{
			$dw->set('send_alert', $sendAlert ? 1 : 0);
		}
		if ($sendEmail !== null)
		{
			$dw->set('send_email', $sendEmail ? 1 : 0);
		}

		return $dw->save();
	}

	public function setTagWatchStateForAll($userId, $state)
	{
		$userId = intval($userId);
		if (!$userId)
		{
			return false;
		}

		$db = $this->_getDb();

		switch ($state)
		{
			case 'watch_email':
				return $db->update('xf_tinhte_xentag_tag_watch', array('send_email' => 1), "user_id = " . $db->quote($userId));

			case 'watch_no_email':
				return $db->update('xf_tinhte_xentag_tag_watch', array('send_email' => 0), "user_id = " . $db->quote($userId));

			case 'watch_alert':
				return $db->update('xf_tinhte_xentag_tag_watch', array('send_alert' => 1), "user_id = " . $db->quote($userId));

			case 'watch_no_alert':
				return $db->update('xf_tinhte_xentag_tag_watch', array('send_alert' => 0), "user_id = " . $db->quote($userId));

			case '':
				return $db->delete('xf_tinhte_xentag_tag_watch', "user_id = " . $db->quote($userId));

			default:
				return false;
		}
	}

	/**
	 * @return XenForo_Model_Thread
	 */
	protected function _getThreadModel()
	{
		return $this->getModelFromCache('XenForo_Model_Thread');
	}

	/**
	 * @return XenForo_Model_Alert
	 */
	protected function _getAlertModel()
	{
		return $this->getModelFromCache('XenForo_Model_Alert');
	}

}

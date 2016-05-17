<?php

class Tinhte_XenTag_Model_TagWatch extends XenForo_Model
{
    public function getUserTagWatchByUserId($userId)
    {
        return $this->fetchAllKeyed('
			SELECT tag_watch.*, tag.*
			FROM xf_tinhte_xentag_tag_watch AS tag_watch
			INNER JOIN xf_tag AS tag
				ON (tag.tag_id = tag_watch.tag_id)
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

    public function sendNotificationToWatchUsersOnTagged(
        $tags,
        array $contentData = array(),
        array $permissionConfig = array()
    ) {
        /** @var XenForo_Model_User $userModel */
        $userModel = $this->getModelFromCache('XenForo_Model_User');

        list($noEmail, $noAlert) = Tinhte_XenTag_Integration::getNoEmailAndAlert(
            $contentData['content_type'], $contentData['content_id']);
        $emailed = array();
        $alerted = array();

        $emailTemplate = 'tinhte_xentag_watch_tag_' . $contentData['content_type'];
        if (XenForo_Application::getOptions()->get('emailWatchedThreadIncludeMessage')) {
            $parseBbCode = true;
        } else {
            $parseBbCode = false;
        }

        if (!isset($contentData['avatar_width'])
            || !isset($contentData['custom_title'])
        ) {
            // fetch a full user record if we don't have one already
            $contentUser = $userModel->getUserById($contentData['user_id']);
            if ($contentUser) {
                $contentData = array_merge($contentUser, $contentData);
            } else {
                $contentData['avatar_width'] = 0;
                $contentData['custom_title'] = '';
            }
        }

        if (!empty($permissionConfig['content_type'])
            && !empty($permissionConfig['content_id'])
        ) {
            $users = $this->getUsersWatchingTags(array_keys($tags),
                $permissionConfig['content_type'], $permissionConfig['content_id']);
        } else {
            $users = $this->getUsersWatchingTags(array_keys($tags));
        }

        foreach ($users AS $user) {
            if (empty($user['watching_tag_id'])) {
                continue;
            }
            $tagId = $user['watching_tag_id'];

            if (empty($tags[$tagId])) {
                continue;
            }
            $tagText = $tags[$user['watching_tag_id']];

            if ($user['user_id'] == $contentData['user_id']) {
                // self notification? That's silly
                continue;
            }

            if ($userModel->isUserIgnored($user, $contentData['user_id'])) {
                continue;
            }

            $globalPermissions = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
            if (!XenForo_Permission::hasPermission($globalPermissions,
                'general', Tinhte_XenTag_Constants::PERM_USER_WATCH)
            ) {
                // no tag watch permission (or revoked)
                continue;
            }

            if (!empty($permissionConfig['content_type'])
                && !empty($permissionConfig['content_id'])
                && !empty($permissionConfig['permissions'])
            ) {
                $contentPermissions = XenForo_Permission::unserializePermissions($user['content_permission_cache']);
                $contentPermissionFound = true;

                foreach ($permissionConfig['permissions'] as $contentPermissionRequired) {
                    if (!XenForo_Permission::hasContentPermission($contentPermissions, $contentPermissionRequired)) {
                        $contentPermissionFound = false;
                    }
                }

                if (!$contentPermissionFound) {
                    // no content permission
                    continue;
                }
            }

            if ($user['send_email']
                && $user['email']
                && $user['user_state'] == 'valid'
                && !in_array($user['user_id'], $noEmail)
            ) {
                if (!empty($contentData['message'])
                    && !isset($contentData['messageText'])
                    && $parseBbCode
                ) {
                    $bbCodeParserText = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Text'));
                    $contentData['messageText'] = new XenForo_BbCode_TextWrapper(
                        $contentData['message'], $bbCodeParserText);

                    $bbCodeParserHtml = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
                    $contentData['messageHtml'] = new XenForo_BbCode_TextWrapper(
                        $contentData['message'], $bbCodeParserHtml);
                }

                if (!empty($contentData['title'])
                    && !isset($contentData['titleCensored'])
                ) {
                    $contentData['titleCensored'] = XenForo_Helper_String::censorString($contentData['title']);
                }

                $user['email_confirm_key'] = $userModel->getUserEmailConfirmKey($user);

                $mail = XenForo_Mail::create($emailTemplate, array(
                    'tagId' => $tagId,
                    'tagText' => $tagText,
                    'contentType' => $contentData['content_type'],
                    'contentId' => $contentData['content_id'],
                    'contentData' => $contentData,
                    'receiver' => $user
                ), $user['language_id']);
                $mail->enableAllLanguagePreCache();
                $mail->queue($user['email'], $user['username']);

                $emailed[] = $user['user_id'];
            }

            if ($user['send_alert'] AND !in_array($user['user_id'], $noAlert)) {
                XenForo_Model_Alert::alert(
                    $user['user_id'],
                    $contentData['user_id'],
                    $contentData['username'],
                    $contentData['content_type'],
                    $contentData['content_id'],
                    'tinhte_xentag_tag_watch',
                    array(
                        'tagId' => $tagId,
                        'tagText' => $tagText,
                    )
                );

                $alerted[] = $user['user_id'];
            }
        }

        Tinhte_XenTag_Integration::updateNoEmailAndAlert(
            $contentData['content_type'], $contentData['content_id'],
            $emailed, $alerted);
    }

    public function getUsersWatchingTags($tagIds, $permissionContentType = '', $permissionContentId = 0)
    {
        $permissionSelect = '';
        $permissionJoin = '';

        if (!empty($permissionContentType) AND !empty($permissionContentId)) {
            $permissionSelect = 'content_permission.cache_value AS content_permission_cache,';
            $permissionJoin = '
				LEFT JOIN xf_permission_cache_content AS content_permission
					ON (content_permission.permission_combination_id = user.permission_combination_id
						AND content_permission.content_type = ' . $this->_getDb()->quote($permissionContentType) . '
						AND content_permission.content_id = ' . $this->_getDb()->quote($permissionContentId) . ')';
        }

        return $this->fetchAllKeyed('
			SELECT user.*,
				user_option.*,
				user_profile.*,
				tag_watch.tag_id AS watching_tag_id,
				tag_watch.send_alert,
				tag_watch.send_email,
				' . $permissionSelect . '
				permission_combination.cache_value AS global_permission_cache
			FROM xf_tinhte_xentag_tag_watch AS tag_watch
			INNER JOIN xf_user AS user ON
				(user.user_id = tag_watch.user_id AND user.user_state = \'valid\' AND user.is_banned = 0)
			INNER JOIN xf_user_option AS user_option ON
				(user_option.user_id = user.user_id)
			INNER JOIN xf_user_profile AS user_profile ON
				(user_profile.user_id = user.user_id)
			' . $permissionJoin . '
			LEFT JOIN xf_permission_combination AS permission_combination ON
				(permission_combination.permission_combination_id = user.permission_combination_id)
			WHERE tag_watch.tag_id IN (' . $this->_getDb()->quote($tagIds) . ')
				AND (tag_watch.send_alert <> 0 OR tag_watch.send_email <> 0)
		', 'user_id');
    }

    public function setTagWatchState($userId, $tagId, $sendAlert = null, $sendEmail = null)
    {
        if (empty($userId)) {
            return false;
        }

        $tagWatch = $this->getUserTagWatchByIds($userId, $tagId);

        if ($sendAlert === null AND $sendEmail === null) {
            if (!empty($tagWatch)) {
                $dw = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TagWatch');
                $dw->setExistingData($tagWatch, true);
                $dw->delete();
            }
            return true;
        }

        $dw = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TagWatch');
        if (!empty($tagWatch)) {
            $dw->setExistingData($tagWatch, true);
        } else {
            $dw->set('user_id', $userId);
            $dw->set('tag_id', $tagId);
        }
        if ($sendAlert !== null) {
            $dw->set('send_alert', $sendAlert ? 1 : 0);
        }
        if ($sendEmail !== null) {
            $dw->set('send_email', $sendEmail ? 1 : 0);
        }

        return $dw->save();
    }

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

    public function setTagWatchStateForAll($userId, $state)
    {
        $userId = intval($userId);
        if (!$userId) {
            return false;
        }

        $db = $this->_getDb();

        switch ($state) {
            case 'watch_email':
                return $db->update('xf_tinhte_xentag_tag_watch', array('send_email' => 1),
                    "user_id = " . $db->quote($userId));

            case 'watch_no_email':
                return $db->update('xf_tinhte_xentag_tag_watch', array('send_email' => 0),
                    "user_id = " . $db->quote($userId));

            case 'watch_alert':
                return $db->update('xf_tinhte_xentag_tag_watch', array('send_alert' => 1),
                    "user_id = " . $db->quote($userId));

            case 'watch_no_alert':
                return $db->update('xf_tinhte_xentag_tag_watch', array('send_alert' => 0),
                    "user_id = " . $db->quote($userId));

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

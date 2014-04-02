<?php

class Tinhte_XenTag_XenForo_Model_ForumWatch extends XFCP_Tinhte_XenTag_XenForo_Model_ForumWatch
{
	public function sendNotificationToWatchUsersOnMessage(array $post, array $thread = null, array $noAlerts = array(), array $noEmail = array())
	{
		$result = parent::sendNotificationToWatchUsersOnMessage($post, $thread, $noAlerts, $noEmail);

		Tinhte_XenTag_Integration::updateNoEmailAndAlert('post', $post['post_id'], $noEmail, $noAlerts);
		Tinhte_XenTag_Integration::updateNoEmailAndAlert('post', $post['post_id'], $result['emailed'], $result['alerted']);

		return $result;
	}

}

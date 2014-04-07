<?php

class Tinhte_XenTag_XenForo_Model_ForumWatch extends XFCP_Tinhte_XenTag_XenForo_Model_ForumWatch
{
	public function sendNotificationToWatchUsersOnMessage(array $post, array $thread = null, array $noAlerts = array(), array $noEmail = array())
	{
		$result = parent::sendNotificationToWatchUsersOnMessage($post, $thread, $noAlerts, $noEmail);

		Tinhte_XenTag_Integration::updateNoEmailAndAlert('post', $post['post_id'], $noEmail, $noAlerts);

		$emailed = array();
		if (!empty($result['emailed']))
		{
			$emailed = $result['emailed'];
		}

		$alerted = array();
		if (!empty($result['alerted']))
		{
			$alerted = $result['alerted'];
		}

		Tinhte_XenTag_Integration::updateNoEmailAndAlert('post', $post['post_id'], $emailed, $alerted);

		return $result;
	}

}

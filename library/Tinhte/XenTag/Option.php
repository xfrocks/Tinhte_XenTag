<?php

class Tinhte_XenTag_Option {
	
	public static function get($key) {
		$options = XenForo_Application::get('options');
		
		static $availablePositions = array(
			'post_message_below',
			'post_message_above',
			'post_date_after',
			'post_permalink_after',
			'thread_pagenav_above',
			'thread_messages_above',
			'thread_qr_above',
			'thread_qr_below',
		);
		
		switch ($key) {
			case 'perPage': return 20;
			case 'cloudMax': return 100;
			case 'cloudLevelCount': return 5;
			case 'displayPosition':
				$position = $options->get('Tinhte_XenTag_' . $key);
				if (!in_array($position, $availablePositions)) {
					$position = $availablePositions[0];
				}
				return $position;
			case 'majorSection': return 'forums';
			case 'routePrefix': return Tinhte_XenTag_Listener::getRoutePrefix();
			case 'searchForceUseCache': return true;
		}
		
		return $options->get('Tinhte_XenTag_' . $key);
	}
	
}
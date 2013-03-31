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
			case 'routePrefix': return Tinhte_XenTag_Listener::getRoutePrefix();
			case 'majorSection': return 'Tinhte_XenTag';
			case 'displayPosition':
				$position = $options->get('tinhte_xentag_' . $key);
				if (!in_array($position, $availablePositions)) {
					$position = $availablePositions[0];
				}
				return $position;
		}
		
		return $options->get('tinhte_xentag_' . $key);
	}
	
}
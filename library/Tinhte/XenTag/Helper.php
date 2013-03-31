<?php

class Tinhte_XenTag_Helper {
	public static function unserialize($string) {
		$array = $string;
		
		if (!is_array($array)) $array = @unserialize($array);
		
		if (empty($array)) $array = array();
		
		return $array;
	}
	
	public static function getImplodedTagsFromThread($thread, $getLinks = false) {
		$result = array();
		
		if (is_array($thread) AND isset($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS])) {
			$tags = self::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		} else {
			$tags = array();
		}
		
		if ($getLinks) {
			foreach ($tags as $tag) {
				$result[] = '<a href="' 
								. XenForo_Link::buildPublicLink(Tinhte_XenTag_Option::get('routePrefix'), $tag)
								. '">' . htmlspecialchars($tag) . '</a>';
			}
		} else {
			foreach ($tags as $tag) {
				$result[] = htmlspecialchars($tag);
			}
		}
		
		return implode(', ', $result);
	}
	
	public static function getOption($key) {
		return Tinhte_XenTag_Option::get($key);
	}
}
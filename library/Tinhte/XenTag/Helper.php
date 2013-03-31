<?php

class Tinhte_XenTag_Helper {
	public static function unserialize($string) {
		$array = $string;
		
		if (!is_array($array)) $array = @unserialize($array);
		
		if (empty($array)) $array = array();
		
		return $array;
	}
	
	public static function explodeTags($tagsStr) {
		return preg_split(Tinhte_XenTag_Constants::REGEX_SEPARATOR, $tagsStr, -1, PREG_SPLIT_NO_EMPTY);
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
	
	public static function getSafeTagsTextArrayForSearch(array $tagsText) {
		$safe = array();
		
		foreach ($tagsText as $tagText) {
			$safe[] = str_replace(array(
				// list of all non-sense characters...
				// typing this base on my keyboard, going from upper left to bottom right
				// normal character before shift'd character
				'`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', /* '_' silly!, */ '=', '+',
				'[', '{', ']', '}', '\\', '|',
				';', ':', '\'', '"',
				',', '<', '.', '>', '/', '?',
				' ',
				'،', // Udu comma
			), '_', $tagText);
		}
		
		return $safe;
	}
}
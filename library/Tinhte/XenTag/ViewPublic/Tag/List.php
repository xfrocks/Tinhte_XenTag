<?php

class Tinhte_XenTag_ViewPublic_Tag_List extends XenForo_ViewPublic_Base {
	public function renderHtml() {
		$tags =& $this->_params['tags'];
		
		$levelCount = Tinhte_XenTag_Option::get('cloudLevelCount');
		$maxContentCount = 0;
		$levelStep = 9999;
		
		foreach ($tags as $tag) {
			if ($tag['content_count'] > $maxContentCount) {
				// this is actually not needed since the $tags
				// array is order by this field
				// but we do this anyway, just in case the array
				// is changed (get all tags for example)
				$maxContentCount = $tag['content_count'];
			}
		}
		if ($levelCount > 0) {
			$levelStep = max(1, floor($maxContentCount / $levelCount));
		}
		
		usort($tags, array(__CLASS__, 'sort')); // array indeces will not be maintained
		
		foreach ($tags as &$tag) {
			$tag['cloudLevel'] = max(1, min($levelCount, ceil($tag['content_count'] / $levelStep)));
		}
	}
	
	public static function sort($tag1, $tag2) {
		return strcmp($tag1['tag_text'], $tag2['tag_text']);
	}
}
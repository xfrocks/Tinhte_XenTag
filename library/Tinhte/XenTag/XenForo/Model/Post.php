<?php

class Tinhte_XenTag_XenForo_Model_Post extends XFCP_Tinhte_XenTag_XenForo_Model_Post {
	
	protected static $_threads = array(
		/* $postId => array(
		 * 	'thread_id' => 1
		 *  'node_id' => 2
		 *  ...
		 * ) */
	);
	
	public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null) {
		$post = parent::preparePost($post, $thread, $forum, $nodePermissions, $viewingUser);
		
		// keep the thread info of a post so we can use it later...
		self::$_threads[$post['post_id']] = $thread;
		
		return $post;
	}
	
	public function Tinhte_XenTag_getThread($postId) {
		if (isset(self::$_threads[$postId])) {
			return self::$_threads[$postId];
		} else {
			return false;
		}
	}
	
	public function Tinhte_XenTag_doAutoTag(array $post, array $thread) {
		$mode = Tinhte_XenTag_Option::get('autoTagMode');
		$onceOnly = Tinhte_XenTag_Option::get('autoTagOnceOnly');
		$tags = false;
				
		if ($mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_DISALBED) {
			// auto tagging is disabled, no thing to do here
			$tags = false;
		} elseif ($mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS
			OR $mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY) {
			// get the tags of current thread
			$tags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
		} else {
			// get all the tags
			// the thread tags are merged because sometimes the global tags are not the full list
			$threadTags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
			$globalTags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->getTagTextsForAutoTag();
			$tags = array_unique(array_merge($threadTags, $globalTags));
		}
		
		if ($mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY
			AND $post['position'] != 0) {
			// the mode targets first post only
			// but this is not the first one, so reset the $tags array
			$tags = false;
		}
		
		
		if (!empty($tags)) {
			// some tags found, start working
			$message =& $post['message'];
			
			foreach ($tags as $tag) {
				$offset = 0;
				
				while (true) {
					$pos = stripos($message, $tag, $offset);
					
					if ($pos !== false) {
						// the tag has been found
						if (!$this->_Tinhte_XenTag_isBetweenUrlTags($message, $pos)) {
							// and it's not between [URL] tags, start replacing
							// we have to use [TAG] with option (base64 encoded) because
							// we don't want to alter user's text
							$replacement = '[TAG=' . base64_encode($tag) . ']' . substr($message, $pos, strlen($tag)) . '[/TAG]';
							$message = substr_replace($message, $replacement, $pos, strlen($tag));
							
							$offset = $pos + strlen($replacement);
							
							if ($onceOnly) {
								// auto link only once per tag
								// break the loop now
								break; // while (true)
							}
						}
					} else {
						// no match has been found, stop working with this tag
						break; // while (true)
					}
				}
			}
		}
		
		return $post;
	}
	
	protected function _Tinhte_XenTag_isBetweenUrlTags($message, $position) {
		// this method is copied from [bd] Tag Me's source code
		// found the nearest [URL before the position
		$posOpen = strripos($message, '[URL', $position - strlen($message));
		
		if ($posOpen !== false) {
			// there is an open tag before us, checks for close tag
			$posClose = stripos($message, '[/URL]', $posOpen);
			
			if ($posClose === false) {
				// no close tag (?!)
			} else if ($posClose < $position) {
				// there is one but it's also before us
				// that means we are not in between them
			} else {
				// this position is in between 2 URL tags!!!
				return true;
			}
		} else {
			// no URL tag so far
		}
		
		return false;
	}
	
}
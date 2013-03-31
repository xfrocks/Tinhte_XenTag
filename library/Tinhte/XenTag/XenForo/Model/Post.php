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
						if (!$this->_Tinhte_XenTag_isBetweenUrlTags($message, $pos)
							AND !$this->_Tinhte_XenTag_hasValidCharacterAfterward($message, $pos, $tag)) {
							// and it's not between [URL] tags, start replacing
							// *added check for valid character after the tag (since 07-04-2012)
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
						} else {
							$offset = $pos + strlen($tag);
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
		static $urlTags = array(
			'URL', 'IMG',
			'MEDIA',
			'CODE', 'PHP',
			'TAG', // check for our own bb code tag
			'VIDEO', /* legacy support */
		);

		foreach ($urlTags as $urlTag) {
			$posOpen = strripos($message, '[' . $urlTag, $position - strlen($message));
			
			if ($posOpen !== false) {
				// there is an open tag before us, checks for close tag
				$posClose = stripos($message, '[/' . $urlTag . ']', $posOpen);
				
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
		}
		
		return false;
	}
	
	protected function _Tinhte_XenTag_hasValidCharacterAfterward($message, $position, $tag) {
		$pos = $position + strlen($tag);
		
		if ($pos >= strlen($message)) {
			// the founded position is at the end of the message
			// no character afterward so... it's valid
			return true;
		} else {
			$c = substr($message, $pos, 1);
			if (!preg_match('/[\s\(\)\.,!\?:;@\\\\]/', $c)) {
				return true;
			}
		}
		
		return false;
	}
	
}
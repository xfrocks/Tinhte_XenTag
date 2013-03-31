<?php

/**
 * Integration class with helper method to integrate other systems with XenTag.
 * Other developers should only use methods in this class when they need to
 * integrate something. For adventurous people, they can of course dig into 
 * other scripts to provide further integration. However, XenTag itself only
 * uses below methods to integrate with XenForo. 
 * 
 * @author sondh
 *
 */
class Tinhte_XenTag_Integration {
	
	/**
	 * Updates list of tags for specified piece of content. Any addition, removal
	 * will be processed accordingly with record from database.
	 * 
	 * @param string $contentType
	 * @param unsigned int $contentId
	 * @param unsigned int $contentUserId
	 * @param array $tagTexts
	 * @param XenForo_DataWriter $dw
	 * 
	 * @return number of tags after update.
	 * 
	 * @throws XenForo_Exception
	 */
	public static function updateTags($contentType, $contentId, $contentUserId, array $tagTexts, XenForo_DataWriter $dw) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $dw->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		/* @var $taggedModel Tinhte_XenTag_Model_TaggedContent */
		$taggedModel = $dw->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
		
		if ($dw->isInsert()) {
			// saves 1 query
			$existingTags = array();
		} else {
			$existingTags = $tagModel->getTagsOfContent($contentType, $contentId);
		}
		
		$newTags = array();
		$removedTags = array();
		$tagModel->lookForNewAndRemovedTags($existingTags, $tagTexts, $newTags, $removedTags);
		$canCreateNew = XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_CREATE_NEW);
		
		if (!empty($newTags)) {
			$newButExistingTags = $tagModel->getTagsByText($newTags);
			
			foreach ($newTags as $newTag) {
				$newTagData = $tagModel->getTagFromArrayByText($newButExistingTags, $newTag);
				
				if (empty($newTagData)) {
					if (!$canCreateNew) {
						throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_you_can_not_create_new_tag'), true);
					}
					/* @var $dwTag Tinhte_XenTag_DataWriter_Tag */
					$dwTag = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
					$dwTag->set('tag_text', $newTag);
					$dwTag->set('created_user_id', $contentUserId);
					$dwTag->save();
					
					$newTagData = $dwTag->getMergedData();
				}
				
				/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
				$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
				$dwTagged->set('tag_id', $newTagData['tag_id']);
				$dwTagged->set('content_type', $contentType);
				$dwTagged->set('content_id', $contentId);
				$dwTagged->set('tagged_user_id', $contentUserId);
				$dwTagged->save();
			}
		}
		
		if (!empty($removedTags)) {
			foreach ($removedTags as $removedTag) {
				$removedTagData = $tagModel->getTagFromArrayByText($existingTags, $removedTag);
				
				if (!empty($removedTagData)) {
					/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
					$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
					$data = array(
						'tag_id' => $removedTagData['tag_id'],
						'content_type' => $contentType,
						'content_id' => $contentId,
					);
					$dwTagged->setExistingData($data, true);
					$dwTagged->delete();
				}
			}
		}
		
		if (count($newTags) + count($removedTags) > 0) {
			$tagModel->rebuildCache();
		}
		
		return count($existingTags) + count($newTags) - count($removedTags);
	}
	
	/**
	 * Deletes all tags for specified piece of content.
	 * 
	 * @param string $contentType
	 * @param unsigned int $contentId
	 * @param XenForo_DataWriter $dw
	 */
	public static function deleteTags($contentType, $contentId, XenForo_DataWriter $dw) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $dw->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		$existingTags = $tagModel->getTagsOfContent($contentType, $contentId);
		
		foreach ($existingTags as $tag) {
			/* @var $dwTag Tinhte_XenTag_DataWriter_Tagged */
			$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
			$data = array(
				'tag_id' => $tag['tag_id'],
				'content_type' => 'thread',
				'content_id' => $dw->get('thread_id'),
			);
			$dwTagged->setExistingData($data, true);
			$dwTagged->delete();
		}
		
		return count($existingTags);
	}
	
	/**
	 * Inserts tagging metadata into search index. This method should be called
	 * within the method {@link XenForo_Search_DataHandler_Abstract#_insertIntoIndex}
	 * of the associated data handler for target content type.
	 * 
	 * @param array $tagTexts
	 * @param XenForo_Search_DataHandler_Abstract $sdh
	 * 
	 * @throws XenForo_Exception
	 */
	public static function insertIntoIndex(array $tagTexts, XenForo_Search_DataHandler_Abstract $sdh) {
		// call this to make sure Tinhte_XenTag_XenForo_Search_SourceHandler is available
		// it will be loaded dynamically via search_source_create event listener
		XenForo_Search_SourceHandler_Abstract::getDefaultSourceHandler();
		
		if (!empty($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_SEARCH_SOURCEHANDLER_LOADED])) {
			// we have to check for the  of Tinhte_XenTag_XenForo_Search_SourceHandler
			// because it's a common mistake when webmaster install this add-on
			// with XenForo Enhanced Search installed without doing the manual edit
			Tinhte_XenTag_XenForo_Search_SourceHandler::setExtraMetaData(array(
				Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS => Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($tagTexts), 
			));
		} else {
			throw new XenForo_Exception('Please make sure [Tinhte] XenTag has been installed properly, a problem with search handler occured. You may need to edit XenES file manually...', false);
		}
	}
	
	/**
	 * Inserts tag links into an HTML-formatted text. 
	 * 
	 * @param string $html
	 * @param array $tags
	 * @param array $options
	 */
	public static function autoTag($html, array $tags, array &$options = array()) {
		$html = strval($html);
		
		if (empty($tags)) return $html;
		
		// prepare the options
			$onceOnly = empty($options['onceOnly']) ? false : true;
			$options['autoTagged'] = array(); // reset this
		
		// sort tags with the longest one first
		// since 1.0.3
		usort($tags, array(__CLASS__, '_autoTag_sortTagsByLength'));
		
		foreach ($tags as $tag) {
			$offset = 0;
			$tagLength = utf8_strlen($tag);
			
			while (true) {
				$pos = Tinhte_XenTag_Helper::utf8_stripos($html, $tag, $offset);
				
				if ($pos !== false) {
					// the tag has been found
					if (!self::_autoTag_isBetweenHtmlTags($html, $pos)
						AND self::_autoTag_hasValidCharacterAround($html, $pos, $tag)) {
						// and it's not between HTML tags,
						// with good surrounding characters 
						// start replacing
						$replacement = '<a href="'
							. XenForo_Link::buildPublicLink(Tinhte_XenTag_Option::get('routePrefix'), $tag)
							. '">' . utf8_substr($html, $pos, $tagLength) . '</a>';
						
						$html = utf8_substr_replace($html, $replacement, $pos, $tagLength);
						
						// sondh@2012-09-20
						// keep track of the auto tagged tags
						$options['autoTagged'][$tag][$pos] = $replacement;
						
						$offset = $pos + utf8_strlen($replacement);
						
						if ($onceOnly) {
							// auto link only once per tag
							// break the loop now
							break; // while (true)
						}
					} else {
						$offset = $pos + $tagLength;
					}
				} else {
					// no match has been found, stop working with this tag
					break; // while (true)
				}
			}
		}
		
		return $html;
	}
	
	protected static function _autoTag_isBetweenHtmlTags($html, $position) {
		$htmlLength = utf8_strlen($html);
		
		// look for <a> and </a>
		$aBefore = Tinhte_XenTag_Helper::utf8_strripos($html, '<a', $position - $htmlLength);
		if ($aBefore !== false) {
			$aAfter = Tinhte_XenTag_Helper::utf8_stripos($html, '</a>', $aBefore);
			
			if ($aAfter > $position) {
				// too bad, this position is between <a> and </a>
				return true;
			}
		}

		// now that we are not inside <a />
		// we have to make sure we are not in the middle of any tag
		$symbolBefore  = Tinhte_XenTag_Helper::utf8_strrpos($html, '<', $position - $htmlLength);
		if ($symbolBefore !== false) {
			$symbolAfter = utf8_strpos($html, '>', $symbolBefore);
			
			if ($symbolAfter > $position) {
				// now this is extremly bad, get out of here now!
				return true;
			}
		}
		
		return false;
	}
	
	protected static function _autoTag_hasValidCharacterAround($html, $position, $tag) {
		static $regEx = '/[\s\(\)\.,!\?:;@\\\\\[\]{}"&]/u';
		
		$pos = $position + utf8_strlen($tag);
		$htmlLength = utf8_strlen($html);
		
		if ($pos >= $htmlLength) {
			// the found position is at the end of the html
			// no character afterward so... it's valid
		} else {
			if (!preg_match($regEx, utf8_substr($html, $pos, 1))) {
				return false;
			}
		}
		
		// sondh@2012-09-12
		// check for the previous character too
		$pos = $position - 1;
		if ($pos < 0) {
			// the found position is at the start of the html
		} else {
			if (!preg_match($regEx, utf8_substr($html, $pos, 1))) {
				return false;
			}
		}
		
		return true;
	}
	
	protected static function _autoTag_sortTagsByLength($tagText1, $tagText2) {
		return utf8_strlen($tagText1) < utf8_strlen($tagText2);
	}
}
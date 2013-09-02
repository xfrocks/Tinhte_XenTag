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
class Tinhte_XenTag_Integration
{

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
	public static function updateTags($contentType, $contentId, $contentUserId, array $tagTexts, XenForo_DataWriter $dw)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $dw->getModelFromCache('Tinhte_XenTag_Model_Tag');

		/* @var $taggedModel Tinhte_XenTag_Model_TaggedContent */
		$taggedModel = $dw->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');

		if ($dw->isInsert())
		{
			// saves 1 query
			$existingTags = array();
		}
		else
		{
			$existingTags = $tagModel->getTagsOfContent($contentType, $contentId);
		}

		$changed = false;
		$newTagTexts = array();
		$removedTagTexts = array();
		$updatedTags = $existingTags;
		$tagModel->lookForNewAndRemovedTags($existingTags, $tagTexts, $newTagTexts, $removedTagTexts);
		$canCreateNew = XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_CREATE_NEW);

		if (!empty($newTagTexts))
		{
			// sondh@2012-09-21
			// remove duplicate
			foreach (array_keys($newTagTexts) as $key)
			{
				$newTagTexts[$key] = Tinhte_XenTag_Helper::getNormalizedTagText($newTagTexts[$key]);
			}
			$newTagTexts = array_unique($newTagTexts);

			$newButExistingTags = $tagModel->getTagsByText($newTagTexts);

			foreach ($newTagTexts as $newTagText)
			{
				$newTag = $tagModel->getTagFromArrayByText($newButExistingTags, $newTagText);

				if (empty($newTag))
				{
					if (!$canCreateNew)
					{
						throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_you_can_not_create_new_tag'), true);
					}
					/* @var $dwTag Tinhte_XenTag_DataWriter_Tag */
					$dwTag = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
					$dwTag->set('tag_text', $newTagText);
					$dwTag->set('created_user_id', $contentUserId);
					$dwTag->save();

					$newTag = $dwTag->getMergedData();
				}

				if (!empty($newTag['is_staff']) AND !XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF))
				{
					// no permission to add this tag
					continue;
				}

				/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
				$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
				$dwTagged->set('tag_id', $newTag['tag_id']);
				$dwTagged->set('content_type', $contentType);
				$dwTagged->set('content_id', $contentId);
				$dwTagged->set('tagged_user_id', $contentUserId);
				$dwTagged->save();

				$updatedTags[] = $newTag;

				$changed = true;
			}
		}

		if (!empty($removedTagTexts))
		{
			foreach ($removedTagTexts as $removedTagText)
			{
				$removedTag = $tagModel->getTagFromArrayByText($existingTags, $removedTagText);

				if (!empty($removedTag))
				{
					if (!empty($removedTag['is_staff']) AND !XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF))
					{
						// no permission to remove this tag
						continue;
					}

					/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
					$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
					$data = array(
						'tag_id' => $removedTag['tag_id'],
						'content_type' => $contentType,
						'content_id' => $contentId,
					);
					$dwTagged->setExistingData($data, true);
					$dwTagged->delete();

					// remove the removed tag from updated tags array
					foreach (array_keys($updatedTags) as $key)
					{
						if ($updatedTags[$key]['tag_id'] == $removedTag['tag_id'])
						{
							unset($updatedTags[$key]);
						}
					}

					$changed = true;
				}
			}
		}

		if ($changed)
		{
			$tagModel->rebuildTagsCache();
		}

		$packedTags = $tagModel->packTags($updatedTags);

		foreach ($packedTags as $packedTag)
		{
			if (!is_string($packedTag))
			{
				// at least one of the packed tag is not tag text
				// we need to return the packed tags array
				return $packedTags;
			}
		}

		// simply return the counter
		return count($packedTags);
	}

	/**
	 * Deletes all tags for specified piece of content.
	 *
	 * @param string $contentType
	 * @param unsigned int $contentId
	 * @param XenForo_DataWriter $dw
	 */
	public static function deleteTags($contentType, $contentId, XenForo_DataWriter $dw)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $dw->getModelFromCache('Tinhte_XenTag_Model_Tag');

		$existingTags = $tagModel->getTagsOfContent($contentType, $contentId);

		foreach ($existingTags as $tag)
		{
			/* @var $dwTag Tinhte_XenTag_DataWriter_Tagged */
			$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
			$data = array(
				'tag_id' => $tag['tag_id'],
				'content_type' => $contentType,
				'content_id' => $contentId,
			);
			$dwTagged->setExistingData($data, true);
			$dwTagged->delete();
		}

		return count($existingTags);
	}

	public static function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo, array $constraints)
	{
		if ($constraint == Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS)
		{
			return array('metadata' => array(
					Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS,
					implode(' ', Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($constraintInfo)),
				));
		}

		return false;
	}

	/**
	 * Inserts tag links into an HTML-formatted text.
	 *
	 * @param string $html
	 * @param array $tags
	 * @param array $options
	 */
	public static function autoTag($html, array $tagsOrTexts, array &$options = array())
	{
		if (empty($tagsOrTexts))
		{
			return $html;
		}

		$html = strval($html);
		$tagTexts = Tinhte_XenTag_Helper::getTextsFromTagsOrTexts($tagsOrTexts);

		// prepare the options
		$onceOnly = empty($options['onceOnly']) ? false : true;
		$options['autoTagged'] = array();
		// reset this

		// sort tags with the longest one first
		// since 1.0.3
		usort($tagTexts, array(
			__CLASS__,
			'_autoTag_sortTagsByLength'
		));

		foreach ($tagTexts as $tagText)
		{
			$offset = 0;
			$tagLength = utf8_strlen($tagText);

			while (true)
			{
				$pos = Tinhte_XenTag_Helper::utf8_stripos($html, $tagText, $offset);

				if ($pos !== false)
				{
					// the tag has been found
					if (!self::_autoTag_isBetweenHtmlTags($html, $pos) AND self::_autoTag_hasValidCharacterAround($html, $pos, $tagText))
					{
						// and it's not between HTML tags,
						// with good surrounding characters
						// start replacing

						$template = new XenForo_Template_Public('tinhte_xentag_bb_code_tag_tag');
						$template->setParam('tag', $tagText);
						$template->setParam('displayText', utf8_substr($html, $pos, $tagLength));
						$replacement = $template->render();

						$html = utf8_substr_replace($html, $replacement, $pos, $tagLength);

						// sondh@2012-09-20
						// keep track of the auto tagged tags
						$options['autoTagged'][$tagText][$pos] = $replacement;

						$offset = $pos + utf8_strlen($replacement);

						if ($onceOnly)
						{
							// auto link only once per tag
							// break the loop now
							break;
							// while (true)
						}
					}
					else
					{
						$offset = $pos + $tagLength;
					}
				}
				else
				{
					// no match has been found, stop working with this tag
					break;
					// while (true)
				}
			}
		}

		return $html;
	}

	protected static function _autoTag_isBetweenHtmlTags($html, $position)
	{
		$htmlLength = utf8_strlen($html);

		// look for <a> and </a>
		$aBefore = Tinhte_XenTag_Helper::utf8_strripos($html, '<a', $position - $htmlLength);
		if ($aBefore !== false)
		{
			$aAfter = Tinhte_XenTag_Helper::utf8_stripos($html, '</a>', $aBefore);

			if ($aAfter > $position)
			{
				// too bad, this position is between <a> and </a>
				return true;
			}
		}

		// now that we are not inside <a />
		// we have to make sure we are not in the middle of any tag
		$symbolBefore = Tinhte_XenTag_Helper::utf8_strrpos($html, '<', $position - $htmlLength);
		if ($symbolBefore !== false)
		{
			$symbolAfter = utf8_strpos($html, '>', $symbolBefore);

			if ($symbolAfter > $position)
			{
				// now this is extremly bad, get out of here now!
				return true;
			}
		}

		return false;
	}

	protected static function _autoTag_hasValidCharacterAround($html, $position, $tagText)
	{
		static $regEx = '/[\s\(\)\.,!\?:;@\\\\\[\]{}"&<>]/u';

		$pos = $position + utf8_strlen($tagText);
		$htmlLength = utf8_strlen($html);

		if ($pos >= $htmlLength)
		{
			// the found position is at the end of the html
			// no character afterward so... it's valid
		}
		else
		{
			if (!preg_match($regEx, utf8_substr($html, $pos, 1)))
			{
				return false;
			}
		}

		// sondh@2012-09-12
		// check for the previous character too
		$pos = $position - 1;
		if ($pos < 0)
		{
			// the found position is at the start of the html
		}
		else
		{
			if (!preg_match($regEx, utf8_substr($html, $pos, 1)))
			{
				return false;
			}
		}

		return true;
	}

	protected static function _autoTag_sortTagsByLength($tagText1, $tagText2)
	{
		return utf8_strlen($tagText1) < utf8_strlen($tagText2);
	}

}

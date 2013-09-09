<?php

class Tinhte_XenTag_Model_Tag extends XenForo_Model
{

	const FETCH_TAGGED = 1;

	public function packTags($tags)
	{
		$packedTags = array();

		foreach ($tags as $tag)
		{
			if (empty($tag['target_type']) AND empty($tag['is_staff']))
			{
				$packedTags[] = $tag['tag_text'];
			}
			else
			{
				$packedTag = array('tag_text' => $tag['tag_text']);
				if (!empty($tag['target_type']) AND isset($tag['target_id']) AND isset($tag['target_data']))
				{
					$packedTag += array(
						'target_type' => $tag['target_type'],
						'target_id' => $tag['target_id'],
						'target_data' => $tag['target_data'],
					);
				}
				if (!empty($tag['is_staff']))
				{
					$packedTag['is_staff'] = $tag['is_staff'];
				}
				$packedTags[] = $packedTag;
			}
		}

		return $packedTags;
	}

	public function getTagLink($tag)
	{
		if (!empty($tag['target_type']))
		{
			switch ($tag['target_type'])
			{
				case 'link':
					if (!empty($tag['target_data']['link']))
					{
						return $tag['target_data']['link'];
					}
					break;
			}
		}

		return false;
	}

	public function deleteEmptyTags()
	{
		$this->_getDb()->query("DELETE FROM xf_tinhte_xentag_tag WHERE content_count = 0");
		$this->rebuildTagsCache();
	}

	public function rebuildTagsCache()
	{
		$fetchOptions = array(
			'order' => 'content_count',
			'direction' => 'desc',
		);

		$max = intval(Tinhte_XenTag_Option::get('autoTagGlobalMax'));
		if ($max > 0)
		{
			$fetchOptions['limit'] = $max;
		}

		$tags = $this->getAllTag(array(), $fetchOptions);
		$packed = $this->packTags($tags);

		$this->getModelFromCache('XenForo_Model_DataRegistry')->set(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY, $packed);

		return $packed;
	}

	public function updateTag($tagId, $contentCountDelta = 0)
	{
		/* @var $taggedContentModel Tinhte_XenTag_Model_TaggedContent */
		$taggedContentModel = $this->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');

		// get latest tagged contents
		$taggedContentsLimit = Tinhte_XenTag_Option::get('latestTaggedContentsLimit');
		if ($taggedContentsLimit > 0)
		{
			$taggedContents = $taggedContentModel->getAllTaggedContent(array('tag_id' => $tagId), array(
				'order' => 'tagged_date',
				'direction' => 'desc',
				'limit' => $taggedContentsLimit,
			));
		}
		else
		{
			// this feature has been disabled (?)
			$taggedContents = array();
		}

		if ($contentCountDelta === 0)
		{
			$contentCount = $this->_getDb()->fetchOne('
					SELECT COUNT(*)
					FROM xf_tinhte_xentag_tagged_content
					WHERE tag_id = ?
			', array($tagId));
			$contentCountSet = intval($contentCount);
		}
		else
		{
			$contentCountSet = (($contentCountDelta > 0) ? sprintf('content_count + %d', $contentCountDelta) : sprintf('IF(content_count > 0, content_count + %d, 0)', $contentCountDelta));
		}

		$this->_getDb()->query("
				UPDATE `xf_tinhte_xentag_tag`
				SET content_count = " . $contentCountSet . ",
				latest_tagged_contents = ?
				WHERE tag_id = ?
				", array(
			serialize($taggedContents),
			$tagId,
		));

		return $taggedContents;
	}

	public function getTagsOrTextsFromCache()
	{
		$tagsOrTexts = $this->getModelFromCache('XenForo_Model_DataRegistry')->get(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY);

		if ($tagsOrTexts === null)
		{
			// cache not found
			$tagsOrTexts = $this->rebuildTagsCache();
		}

		return $tagsOrTexts;
	}

	public function canTagThread($thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

		$canTagAll = XenForo_Permission::hasContentPermission($nodePermissions, Tinhte_XenTag_Constants::PERM_USER_TAG_ALL);

		if ($canTagAll)
		{
			// can tag all, nothing to check...
			return true;
		}

		if (!isset($thread['user_id']) OR $thread['user_id'] == $viewingUser['user_id'])
		{
			// IMPORTANT: if more data in $thread is used, please make sure to make
			// the appropriate change to Tinhte_XenTag_Model_Post::preparePost
			// and Tinhte_XenTag_XenForo_ControllerPublic_Post::Tinhte_XenTag_actionSave
			// or else this may get really nasty!
			return XenForo_Permission::hasContentPermission($nodePermissions, Tinhte_XenTag_Constants::PERM_USER_TAG);
		}
	}

	public function canTagResource($resource, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$canTagAll = XenForo_Permission::hasPermission($viewingUser['permissions'], 'resource', Tinhte_XenTag_Constants::PERM_USER_RESOURCE_TAG_ALL);

		if ($canTagAll)
		{
			// can tag all, nothing to check...
			return true;
		}

		if (!isset($resource['user_id']) OR $resource['user_id'] == $viewingUser['user_id'])
		{
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'resource', Tinhte_XenTag_Constants::PERM_USER_RESOURCE_TAG);
		}
	}

	public function processInput(XenForo_Input $input)
	{
		$data = $input->filter(array(
			Tinhte_XenTag_Constants::FORM_TAGS_ARRAY => XenForo_Input::ARRAY_SIMPLE,
			Tinhte_XenTag_Constants::FORM_TAGS_TEXT => XenForo_Input::STRING,
			Tinhte_XenTag_Constants::FORM_INCLUDED => XenForo_Input::UINT,
			Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED => XenForo_Input::STRING,
		));

		if (!empty($data[Tinhte_XenTag_Constants::FORM_INCLUDED]))
		{
			$tagTexts = $data[Tinhte_XenTag_Constants::FORM_TAGS_ARRAY];

			if (!empty($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT]))
			{
				$tagTexts2 = Tinhte_XenTag_Helper::explodeTags($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT]);
			}
			else
			{
				$tagTexts2 = array();
			}

			$merged = array_merge($tagTexts, $tagTexts2);

			foreach (array_keys($merged) as $key)
			{
				$merged[$key] = trim($merged[$key]);
				if (empty($merged[$key]))
				{
					unset($merged[$key]);
				}
			}

			return $merged;
		}
		elseif (!empty($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED]))
		{
			// used as a checkbox in search bar
			// so no *_included field is coming with it
			// we just use it as it's is
			$tagTexts = Tinhte_XenTag_Helper::explodeTags($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED]);

			foreach (array_keys($tagTexts) as $key)
			{
				$tagTexts[$key] = trim($tagTexts[$key]);
				if (empty($tagTexts[$key]))
				{
					unset($tagTexts[$key]);
				}
			}

			return $tagTexts;
		}
		else
		{
			return false;
		}
	}

	public function calculateCloudLevel(array &$tags)
	{
		$levelCount = Tinhte_XenTag_Option::get('cloudLevelCount');
		$maxContentCount = 0;
		$levelStep = 9999;

		foreach ($tags as $tag)
		{
			if ($tag['content_count'] > $maxContentCount)
			{
				$maxContentCount = $tag['content_count'];
			}
		}
		if ($levelCount > 0)
		{
			$levelStep = max(1, floor($maxContentCount / $levelCount));
		}

		usort($tags, create_function('$tag1, $tag2', 'return strcmp($tag1["tag_text"], $tag2["tag_text"]);'));
		// array indeces will not be maintained

		foreach ($tags as &$tag)
		{
			$tag['cloudLevel'] = max(1, min($levelCount, ceil($tag['content_count'] / $levelStep)));
		}
	}

	public function lookForNewAndRemovedTags(array $tags, array $newTagTexts, array &$foundNewTagTexts, array &$foundRemovedTagTexts)
	{
		foreach (array_keys($newTagTexts) as $key)
		{
			$tmp = $this->getTagFromArrayByText($tags, $newTagTexts[$key]);

			if (empty($tmp))
			{
				// found new tag text!
				$foundNewTagTexts[] = $newTagTexts[$key];

				// remove it from checking
				unset($newTagTexts[$key]);
			}
		}

		foreach ($tags as $tag)
		{
			$found = false;
			foreach ($newTagTexts as $newTagText)
			{
				if ($this->isTagIdenticalWithText($tag, $newTagText))
				{
					// this is matched, nothing to do
					$found = true;
				}
			}

			if (!$found)
			{
				// found removed tag text!
				$foundRemovedTagTexts[] = $tag['tag_text'];
			}
		}
	}

	public function getTagFromArrayByText(array $tags, $tagText)
	{
		foreach ($tags as $tag)
		{
			if ($this->isTagIdenticalWithText($tag, $tagText))
			{
				return $tag;
			}
		}

		return false;
	}

	public function isTagIdenticalWithText(array $tag, $tagText)
	{
		$tagText = utf8_trim(utf8_strtolower($tagText));

		if (isset($tag['tagTextLower']))
		{
			if ($tag['tagTextLower'] == $tagText)
			{
				return true;
			}
		}
		elseif (utf8_strtolower($tag['tag_text']) == $tagText)
		{
			return true;
		}

		return false;
	}

	public function getTagsOfContent($contentType, $contentId, array $fetchOptions = array())
	{
		$conditions = array('tagged_content' => array( array(
					$contentType,
					$contentId
				)));

		if (isset($fetchOptions['join']))
		{
			$fetchOptions['join'] |= self::FETCH_TAGGED;
		}
		else
		{
			$fetchOptions['join'] = self::FETCH_TAGGED;
		}

		return $this->getAllTag($conditions, $fetchOptions);
	}

	public function getTagsByText(array $tagTexts, array $fetchOptions = array())
	{
		$conditions = array('tag_text' => $tagTexts);

		return $this->getAllTag($conditions, $fetchOptions);
	}

	public function getTagByText($tagText, array $fetchOptions = array())
	{
		$conditions = array('tag_text' => $tagText);
		$tags = $this->getAllTag($conditions, $fetchOptions);

		return reset($tags);
	}

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$data = $this->getAllTag($conditions, $fetchOptions);
		$list = array();

		foreach ($data as $id => $row)
		{
			$list[$id] = $row['tag_text'];
		}

		return $list;
	}

	public function getTagById($id, array $fetchOptions = array())
	{
		$data = $this->getAllTag(array('tag_id' => $id), $fetchOptions);

		return reset($data);
	}

	public function getAllTag(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareTagConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareTagOrderOptions($fetchOptions);
		$joinOptions = $this->prepareTagFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$all = $this->fetchAllKeyed($this->limitQueryResults("
				SELECT tag.*
				$joinOptions[selectFields]
				FROM `xf_tinhte_xentag_tag` AS tag
				$joinOptions[joinTables]
				WHERE $whereConditions
				$orderClause
				", $limitOptions['limit'], $limitOptions['offset']), 'tag_id');

		foreach ($all as &$tag)
		{
			if (!empty($tag['target_data']))
			{
				$tag['target_data'] = Tinhte_XenTag_Helper::unserialize($tag['target_data']);
			}
		}

		return $all;
	}

	public function countAllTag(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareTagConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareTagOrderOptions($fetchOptions);
		$joinOptions = $this->prepareTagFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
				SELECT COUNT(*)
				FROM `xf_tinhte_xentag_tag` AS tag
				$joinOptions[joinTables]
				WHERE $whereConditions
				");
	}

	public function prepareTagConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		foreach (array('tag_id', 'created_date', 'created_user_id', 'content_count', 'target_type', 'target_id') as $intField)
		{
			if (!isset($conditions[$intField]))
				continue;

			if (is_array($conditions[$intField]))
			{
				$sqlConditions[] = "tag.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			}
			else
			{
				$sqlConditions[] = "tag.$intField = " . $db->quote($conditions[$intField]);
			}
		}

		if (isset($conditions['tag_text']))
		{
			if (is_array($conditions['tag_text']))
			{
				$sqlConditions[] = 'tag.tag_text IN(' . $db->quote($conditions['tag_text']) . ')';
			}
			else
			{
				$sqlConditions[] = 'tag.tag_text = ' . $db->quote($conditions['tag_text']);
			}
		}

		if (isset($conditions['tag_text_like']) AND is_array($conditions['tag_text_like']))
		{
			$sqlConditions[] = 'tag.tag_text LIKE ' . XenForo_Db::quoteLike($conditions['tag_text_like'][0], $conditions['tag_text_like'][1], $db);
		}

		if (isset($conditions['tagged_content']) AND is_array($conditions['tagged_content']))
		{
			$tmp = array();

			foreach ($conditions['tagged_content'] as $taggedContent)
			{
				$tmp[] = 'tagged_content.content_type = ' . $db->quote($taggedContent[0]) . ' AND tagged_content.content_id = ' . $db->quote($taggedContent[1]);
			}

			$sqlConditions[] = implode(' OR ', $tmp);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareTagFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_TAGGED)
			{
				$selectFields .= ' , tagged_content.* ';
				$joinTables .= ' INNER JOIN `xf_tinhte_xentag_tagged_content` AS tagged_content ' . ' ON (tagged_content.tag_id = tag.tag_id) ';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareTagOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'tag_text' => 'tag.tag_text',
			'content_count' => 'tag.content_count',
		);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}

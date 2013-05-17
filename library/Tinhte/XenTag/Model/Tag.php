<?php
class Tinhte_XenTag_Model_Tag extends XenForo_Model {
	
	const FETCH_TAGGED = 1;
	
	public function deleteEmptyTags() {
		$this->_getDb()->query("DELETE FROM xf_tinhte_xentag_tag WHERE content_count = 0");
		$this->rebuildCache();
	}
	
	public function rebuildCache() {
		$max = intval(Tinhte_XenTag_Option::get('autoTagGlobalMax'));
		
		$tags = $this->_getDb()->fetchCol("
			SELECT tag_text
			FROM xf_tinhte_xentag_tag
			ORDER BY content_count DESC
			" . ($max > 0 ? "LIMIT " . $max : "") . "
		");
		$this->getModelFromCache('XenForo_Model_DataRegistry')->set(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY, $tags);
		
		return $tags;
	}
	
	public function updateTag($tagId, $contentCountDelta = 0) {
		/* @var $taggedContentModel Tinhte_XenTag_Model_TaggedContent */
		$taggedContentModel = $this->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
		
		// get latest tagged contents
		$taggedContentsLimit = Tinhte_XenTag_Option::get('latestTaggedContentsLimit');
		if ($taggedContentsLimit > 0) {
			$taggedContents = $taggedContentModel->getAllTaggedContent(
				array('tag_id' => $tagId),
				array(
					'order' => 'tagged_date',
					'direction' => 'desc',
					'limit' => $taggedContentsLimit,
				)
			);
		} else {
			// this feature has been disabled (?)
			$taggedContents = array();
		}
		
		$this->_getDb()->query("
			UPDATE `xf_tinhte_xentag_tag`
			SET content_count = " . (
				($contentCountDelta >= 0) ?
				('content_count + ?') :
				('IF(content_count > 0, content_count + ?, 0)')
			) . "
				, latest_tagged_contents = ?
			WHERE tag_id = ?
		", array(
			$contentCountDelta,
			serialize($taggedContents),
			$tagId,
		));
		
		return $taggedContents;
	}
	
	public function getTagTextsFromCache() {
		$tags = $this->getModelFromCache('XenForo_Model_DataRegistry')->get(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY);

		if ($tags === null) {
			// cache not found
			$tags = $this->rebuildCache();
		}
		
		return $tags;
	}
	
	public function canTagThread($thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null) {
		$this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);
		
		$canTagAll = XenForo_Permission::hasContentPermission($nodePermissions, Tinhte_XenTag_Constants::PERM_USER_TAG_ALL);
		
		if ($canTagAll) return true; // can tag all, nothing to check...

		if (!isset($thread['user_id']) OR $thread['user_id'] == $viewingUser['user_id']) {
			// IMPORTANT: if more data in $thread is used, please make sure to make
			// the appropriate change to Tinhte_XenTag_Model_Post::preparePost
			// and Tinhte_XenTag_XenForo_ControllerPublic_Post::Tinhte_XenTag_actionSave
			// or else this may get really nasty!
			return XenForo_Permission::hasContentPermission($nodePermissions, Tinhte_XenTag_Constants::PERM_USER_TAG);
		}
	}
	
	public function canTagResource($resource, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null) {
		$this->standardizeViewingUserReference($viewingUser);
		
		$canTagAll = XenForo_Permission::hasPermission($viewingUser['permissions'], 'resource', Tinhte_XenTag_Constants::PERM_USER_RESOURCE_TAG_ALL);
		
		if ($canTagAll) return true; // can tag all, nothing to check...

		if (!isset($resource['user_id']) OR $resource['user_id'] == $viewingUser['user_id']) {
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'resource', Tinhte_XenTag_Constants::PERM_USER_RESOURCE_TAG);
		}
	}
	
	public function processInput(XenForo_Input $input) {
		$data = $input->filter(array(
			Tinhte_XenTag_Constants::FORM_TAGS_ARRAY => XenForo_Input::ARRAY_SIMPLE,
			Tinhte_XenTag_Constants::FORM_TAGS_TEXT => XenForo_Input::STRING,
			Tinhte_XenTag_Constants::FORM_INCLUDED => XenForo_Input::UINT,
			Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED => XenForo_Input::STRING,
		));
		
		if (!empty($data[Tinhte_XenTag_Constants::FORM_INCLUDED])) {
			$tags = $data[Tinhte_XenTag_Constants::FORM_TAGS_ARRAY];
			
			if (!empty($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT])) {
				$tags2 = Tinhte_XenTag_Helper::explodeTags($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT]);
			} else {
				$tags2 = array();
			}
			
			$merged = array_merge($tags, $tags2);
			
			foreach (array_keys($merged) as $key) {
				$merged[$key] = trim($merged[$key]);
				if (empty($merged[$key])) unset($merged[$key]);
			}
			
			return $merged;
		} elseif (!empty($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED])) {
			// used as a checkbox in search bar
			// so no *_included field is coming with it
			// we just use it as it's is
			$tags = Tinhte_XenTag_Helper::explodeTags($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED]);
			
			foreach (array_keys($tags) as $key) {
				$tags[$key] = trim($tags[$key]);
				if (empty($tags[$key])) unset($tags[$key]);
			}
			
			return $tags;
		} else {
			return false;
		}
	}
	
	public function calculateCloudLevel(array &$tags) {
		$levelCount = Tinhte_XenTag_Option::get('cloudLevelCount');
		$maxContentCount = 0;
		$levelStep = 9999;
		
		foreach ($tags as $tag) {
			if ($tag['content_count'] > $maxContentCount) {
				$maxContentCount = $tag['content_count'];
			}
		}
		if ($levelCount > 0) {
			$levelStep = max(1, floor($maxContentCount / $levelCount));
		}
		
		usort($tags, create_function('$tag1, $tag2', 'return strcmp($tag1["tag_text"], $tag2["tag_text"]);')); // array indeces will not be maintained
		
		foreach ($tags as &$tag) {
			$tag['cloudLevel'] = max(1, min($levelCount, ceil($tag['content_count'] / $levelStep)));
		}
	}
	
	public function lookForNewAndRemovedTags(array $tagsData, array $newTagsText, array &$foundNewTagsText, array &$foundRemovedTagsText) {
		foreach (array_keys($newTagsText) as $key) {
			$tmp = $this->getTagFromArrayByText($tagsData, $newTagsText[$key]);
			
			if (empty($tmp)) {
				// found new tag text!
				$foundNewTagsText[] = $newTagsText[$key];
				unset($newTagsText[$key]); // remove it from checking
			} else {
				// this will be checked further
				$newTagsText[$key] = utf8_strtolower($newTagsText[$key]);
			}
		}
		
		foreach ($tagsData as $tagData) {
			$found = false;
			foreach ($newTagsText as $textLower) {
				if ($this->isTagIdenticalWithText($tagData, $textLower)) {
					// this is matched, nothing to do
					$found = true;
				}
			}
			
			if (!$found) {
				// found removed tag text!
				$foundRemovedTagsText[] = $tagData['tag_text'];
			}
		}
	}
	
	public function getTagFromArrayByText(array $tagsData, $text) {
		$textLower = utf8_trim(utf8_strtolower($text));
		
		foreach ($tagsData as $tagData) {
			if ($this->isTagIdenticalWithText($tagData, $textLower)) {
				return $tagData;
			}
		}
		
		return false;
	}
	
	public function isTagIdenticalWithText(array $tagData, $tagText) {
		$textLower = utf8_strtolower($tagText);
		
		if (isset($tagData['tagTextLower'])) {
			if ($tagData['tagTextLower'] == $textLower) {
				return true;
			}
		} elseif (utf8_strtolower($tagData['tag_text']) == $textLower) {
			return true;
		}
		
		return false;
	}
	
	public function getTagsOfContent($contentType, $contentId, array $fetchOptions = array()) {
		$conditions = array(
			'tagged_content' => array(
				array($contentType, $contentId)
			),
		);
		
		if (isset($fetchOptions['join'])) {
			$fetchOptions['join'] |= self::FETCH_TAGGED;
		} else {
			$fetchOptions['join'] = self::FETCH_TAGGED;
		}
		
		return $this->getAllTag($conditions, $fetchOptions);
	}
	
	public function getTagsByText(array $tagsText, array $fetchOptions = array()) {
		$conditions = array(
			'tag_text' => $tagsText,
		);
		
		return $this->getAllTag($conditions, $fetchOptions);
	}
	
	public function getTagByText($tagText, array $fetchOptions = array()) {
		$conditions = array('tag_text' => $tagText);
		$tags = $this->getAllTag($conditions, $fetchOptions);
		
		return reset($tags);
	}

	private function getAllTagCustomized(array &$data, array $fetchOptions) {
		// customized processing for getAllTag() should go here
	}
	
	private function prepareTagConditionsCustomized(array &$sqlConditions, array $conditions, array &$fetchOptions) {
		$db = $this->_getDb();
		
		if (isset($conditions['tag_text'])) {
			if (is_array($conditions['tag_text'])) {
				$sqlConditions[] = 'tag.tag_text IN(' . $db->quote($conditions['tag_text']) . ')';
			} else {
				$sqlConditions[] = 'tag.tag_text = ' . $db->quote($conditions['tag_text']);
			}
		}
		
		if (isset($conditions['tag_text_like']) AND is_array($conditions['tag_text_like'])) {
			$sqlConditions[] = 'tag.tag_text LIKE ' . XenForo_Db::quoteLike($conditions['tag_text_like'][0], $conditions['tag_text_like'][1], $db);
		}
		
		if (isset($conditions['tagged_content']) AND is_array($conditions['tagged_content'])) {
			$tmp = array();
			
			foreach ($conditions['tagged_content'] as $taggedContent) {
				$tmp[] = 'tagged_content.content_type = ' . $db->quote($taggedContent[0])
							. ' AND tagged_content.content_id = ' . $db->quote($taggedContent[1]);
			}
			
			$sqlConditions[] = implode(' OR ', $tmp);
		}
	}
	
	public function prepareTagFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions) {
		$db = $this->_getDb();
		
		if (!empty($fetchOptions['join'])) {
			if ($fetchOptions['join'] & self::FETCH_TAGGED) {
				$selectFields .= ' , tagged_content.* ';
				$joinTables .= ' INNER JOIN `xf_tinhte_xentag_tagged_content` AS tagged_content '
								. ' ON (tagged_content.tag_id = tag.tag_id) ';
			}
		}
	}
	
	public function prepareTagOrderOptionsCustomized(array &$choices, array &$fetchOptions) {
		$choices['content_count'] = 'tag.content_count';
	}

	public function getList(array $conditions = array(), array $fetchOptions = array()) {
		$data = $this->getAllTag($conditions, $fetchOptions);
		$list = array();
		
		foreach ($data as $id => $row) {
			$list[$id] = $row['tag_text'];
		}
		
		return $list;
	}

	public function getTagById($id, array $fetchOptions = array()) {
		$data = $this->getAllTag(array ('tag_id' => $id), $fetchOptions);
		
		return reset($data);
	}
	
	public function getAllTag(array $conditions = array(), array $fetchOptions = array()) {
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
			", $limitOptions['limit'], $limitOptions['offset']
		), 'tag_id');



		$this->getAllTagCustomized($all, $fetchOptions);
		
		return $all;
	}
		
	public function countAllTag(array $conditions = array(), array $fetchOptions = array()) {
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
	
	public function prepareTagConditions(array $conditions, array &$fetchOptions) {
		$sqlConditions = array();
		$db = $this->_getDb();
		
		foreach (array('tag_id', 'created_date', 'created_user_id', 'content_count') as $intField) {
			if (!isset($conditions[$intField])) continue;
			
			if (is_array($conditions[$intField])) {
				$sqlConditions[] = "tag.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			} else {
				$sqlConditions[] = "tag.$intField = " . $db->quote($conditions[$intField]);
			}
		}
		
		$this->prepareTagConditionsCustomized($sqlConditions, $conditions, $fetchOptions);
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function prepareTagFetchOptions(array $fetchOptions) {
		$selectFields = '';
		$joinTables = '';
		
		$this->prepareTagFetchOptionsCustomized($selectFields,  $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function prepareTagOrderOptions(array &$fetchOptions, $defaultOrderSql = '') {
		$choices = array(
			'tag_text' => 'tag.tag_text',
			'content_count' => 'tag.content_count',
		);
		
		$this->prepareTagOrderOptionsCustomized($choices, $fetchOptions);
		
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}
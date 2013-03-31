<?php
class Tinhte_XenTag_Model_Tag extends XenForo_Model {
	
	const FETCH_TAGGED = 1;
	
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
				$tags2 = explode(',', $data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT]);
			} else {
				$tags2 = array();
			}
			
			return array_merge($tags, $tags2);
		} elseif (!empty($data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED])) {
			// used as a checkbox in search bar
			// so no *_included field is coming with it
			// we just use it as it's is
			return explode(',', $data[Tinhte_XenTag_Constants::FORM_TAGS_TEXT_NO_INCLUDED]);
		} else {
			return false;
		}
	}
	
	public function validateTag($text) {
		// $text = str_replace(',', '', $text); -- this must be taken care of in other places!
		$text = trim($text);
		$text = strtolower($text);
		
		return $text;
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
				$newTagsText[$key] = strtolower($newTagsText[$key]);
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
		$textLower = strtolower($text);
		
		foreach ($tagsData as $tagData) {
			if ($this->isTagIdenticalWithText($tagData, $textLower)) {
				return $tagData;
			}
		}
		
		return false;
	}
	
	public function isTagIdenticalWithText(array $tagData, $textLower) {
		if (isset($tagData['tagTextLower'])) {
			if ($tagData['tagTextLower'] == $textLower) {
				return true;
			}
		} elseif (strtolower($tagData['tag_text']) == $textLower) {
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

	/* Start auto-generated lines of code. Change made will be overwriten... */

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
			
		);
		
		$this->prepareTagOrderOptionsCustomized($choices, $fetchOptions);
		
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}
	


	/* End auto-generated lines of code. Feel free to make changes below */

}
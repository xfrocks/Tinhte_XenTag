<?php
class Tinhte_XenTag_Model_TaggedContent extends XenForo_Model {

	private function getAllTaggedContentCustomized(array &$data, array $fetchOptions) {
		// customized processing for getAllTaggedContent() should go here
	}
	
	private function prepareTaggedContentConditionsCustomized(array &$sqlConditions, array $conditions, array &$fetchOptions) {
		// customized code goes here
	}
	
	public function prepareTaggedContentFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions) {
		// customized code goes here
	}
	
	public function prepareTaggedContentOrderOptionsCustomized(array &$choices, array &$fetchOptions) {
		// customized code goes here
	}

	public function getList(array $conditions = array(), array $fetchOptions = array()) {
		$data = $this->getAllTaggedContent($conditions, $fetchOptions);
		$list = array();
		
		foreach ($data as $id => $row) {
			$list[$id] = $row['n/a'];
		}
		
		return $list;
	}

	public function getTaggedContentById($id, array $fetchOptions = array()) {
		$data = $this->getAllTaggedContent(array ('n/a' => $id), $fetchOptions);
		
		return reset($data);
	}
	
	public function getAllTaggedContent(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareTaggedContentConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareTaggedContentOrderOptions($fetchOptions);
		$joinOptions = $this->prepareTaggedContentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$all = $this->fetchAllKeyed($this->limitQueryResults("
				SELECT tagged_content.*
					$joinOptions[selectFields]
				FROM `xf_tinhte_xentag_tagged_content` AS tagged_content
					$joinOptions[joinTables]
				WHERE $whereConditions
					$orderClause
			", $limitOptions['limit'], $limitOptions['offset']
		), 'n/a');



		$this->getAllTaggedContentCustomized($all, $fetchOptions);
		
		return $all;
	}
		
	public function countAllTaggedContent(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareTaggedContentConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareTaggedContentOrderOptions($fetchOptions);
		$joinOptions = $this->prepareTaggedContentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_tinhte_xentag_tagged_content` AS tagged_content
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}
	
	public function prepareTaggedContentConditions(array $conditions, array &$fetchOptions) {
		$sqlConditions = array();
		$db = $this->_getDb();
		
		foreach (array('tag_id', 'content_id', 'tagged_user_id', 'tagged_date') as $intField) {
			if (!isset($conditions[$intField])) continue;
			
			if (is_array($conditions[$intField])) {
				$sqlConditions[] = "tagged_content.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			} else {
				$sqlConditions[] = "tagged_content.$intField = " . $db->quote($conditions[$intField]);
			}
		}
		
		$this->prepareTaggedContentConditionsCustomized($sqlConditions, $conditions, $fetchOptions);
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function prepareTaggedContentFetchOptions(array $fetchOptions) {
		$selectFields = '';
		$joinTables = '';
		
		$this->prepareTaggedContentFetchOptionsCustomized($selectFields,  $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function prepareTaggedContentOrderOptions(array &$fetchOptions, $defaultOrderSql = '') {
		$choices = array(
			
		);
		
		$this->prepareTaggedContentOrderOptionsCustomized($choices, $fetchOptions);
		
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}
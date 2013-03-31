<?php

class Tinhte_XenTag_XenForo_Model_Search extends XFCP_Tinhte_XenTag_XenForo_Model_Search {
	public function getGeneralConstraintsFromInput(array $input, &$errors = null) {
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH])) {
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH]->Tinhte_XenTag_actionSearch($this, $input);
		}
		
		$constraints = parent::getGeneralConstraintsFromInput($input, $errors);
		
		if (!empty($input[Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS])) {
			$tags = $input[Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS];
			if (!is_array($tags)) {
				$tags = array($tags);
			}
			
			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
			
			// runs through basic validation first
			foreach ($tags as &$tag) {
				$tag = $tagModel->validateTag($tag);
			}
			
			$constraints[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS] = $tags;
		}
		
		return $constraints;
	}
}
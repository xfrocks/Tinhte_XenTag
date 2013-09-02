<?php

class Tinhte_XenTag_XenForo_Model_Search extends XFCP_Tinhte_XenTag_XenForo_Model_Search
{
	public function getGeneralConstraintsFromInput(array $input, &$errors = null)
	{
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH]))
		{
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH]->Tinhte_XenTag_actionSearch($this, $input);
		}

		$constraints = parent::getGeneralConstraintsFromInput($input, $errors);

		if (!empty($input[Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS]))
		{
			$tagTexts = $input[Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS];
			if (!is_array($tagTexts))
			{
				$tagTexts = array($tagTexts);
			}

			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

			// runs through basic validation first
			foreach ($tagTexts as &$tagText)
			{
				$tagText = Tinhte_XenTag_Helper::getNormalizedTagText($tagText);
			}

			$constraints[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS] = $tagTexts;
		}

		return $constraints;
	}

	public function insertSearch(array $results, $searchType, $searchQuery, array $constraints, $order, $groupByDiscussion, array $userResults = array(), array $warnings = array(), $userId = null, $searchDate = null)
	{
		$search = parent::insertSearch($results, $searchType, $searchQuery, $constraints, $order, $groupByDiscussion, $userResults, $warnings, $userId, $searchDate);

		if (!empty($search['search_id']))
		{
			if (empty($search['search_type']))
			{
				// only perform search when no search types specified
				$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

				$tags = $tagModel->getAllTag(array('tag_text_like' => array(
						$search['search_query'],
						'lr',
					)), array(
					'limit' => 10,
					'order' => 'content_count',
					'direction' => 'desc',
				));

				$this->_getDb()->update('xf_search', array('tinhte_xentag_tags' => implode(',', array_keys($tags))), array('search_id = ?' => $search['search_id'], ));
			}
		}

		return $search;
	}

	public function prepareSearch(array $search)
	{
		$search = parent::prepareSearch($search);

		$search['tinhte_xentag_tags'] = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->getAllTag(array('tag_id' => explode(',', $search['tinhte_xentag_tags'])), array(
			// TODO
		));

		return $search;
	}

}

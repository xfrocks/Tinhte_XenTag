<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Search extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Search
{

	public function actionIndex()
	{
		$result = parent::actionIndex();

		if ($result instanceof XenForo_ControllerResponse_View)
		{
			$search = &$result->params['search'];
			if (isset($search['keywords']))
			{
				$c = $this->_input->filterSingle('c', XenForo_Input::ARRAY_SIMPLE);

				if (!empty($c[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS]))
				{
					$search[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS] = $c[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS];
				}
			}
		}

		return $result;
	}

	public function actionSearch()
	{
		// we will be called back from
		// Tinhte_XenTag_XenForo_Model_Search::getGeneralConstraintsFromInput
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH] = $this;

		return parent::actionSearch();
	}

	public function Tinhte_XenTag_actionSearch(XenForo_Model_Search $searchModel, array &$input)
	{
		$tags = $this->getModelFromCache('Tinhte_XenTag_Model_Tag')->processInput($this->_input);

		if ($tags !== false AND !empty($tags))
		{
			$input[Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS] = $tags;
		}

		// sondh@2012-08-11
		// unset the global variable to keep it from running multiple time
		// just to be safe, you know
		unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH]);
	}

}

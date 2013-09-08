<?php
class Tinhte_XenTag_Model_Search extends XenForo_Model
{
	public function prioritizeResults(array &$results, XenForo_Search_Searcher $searcher, $searchQuery, array $constraints = array(), $order = 'relevance')
	{
		// prioritize contents by doing a second search for prioritized contents
		// this may cause performance issue but shouldn't make a big impact
		// admin can easily disable the feature in AdminCP
		// it's required to do another search because the original search has its own
		// max results and may not include all prioritized results
		$prioritizedContents = $this->getPrioritizedContents();
		if (empty($prioritizedContents))
		{
			// nothing to do
			return;
		}

		$typeHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_General');
		$typeHandler->setSearchContentTypes(array_keys($prioritizedContents));
		$prioritizedResults = $searcher->searchType($typeHandler, $searchQuery, $constraints, $order);
		if (empty($prioritizedResults))
		{
			// no prioritized results could be found, do nothing
			return;
		}

		// drop all prioritized results from general results
		foreach (array_keys($results) as $resultKey)
		{
			if (isset($prioritizedContents[$results[$resultKey][0]]))
			{
				unset($results[$resultKey]);
			}
		}

		// append priorizied results
		asort($prioritizedContents);
		$prioritizedResultsKeys = array_reverse(array_keys($prioritizedResults));
		foreach ($prioritizedContents as $contentType => $displayOrder)
		{
			foreach ($prioritizedResultsKeys as $prioritizedResultKey)
			{
				if ($prioritizedResults[$prioritizedResultKey][0] == $contentType)
				{
					array_unshift($results, $prioritizedResults[$prioritizedResultKey]);
				}
			}
		}

		// normalized array indeces
		$results = array_values($results);
	}

	public function getPrioritizedContents()
	{
		$contents = array();

		$forums = intval(Tinhte_XenTag_Option::get('prioritizeForums'));
		if ($forums > 0)
		{
			$contents[Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM] = $forums;
		}

		$pages = intval(Tinhte_XenTag_Option::get('prioritizePages'));
		if ($pages > 0)
		{
			$contents[Tinhte_XenTag_Constants::CONTENT_TYPE_PAGE] = $pages;
		}

		$resources = intval(Tinhte_XenTag_Option::get('prioritizeResources'));
		if ($resources > 0)
		{
			$contents[Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE] = $resources;
		}

		return $contents;
	}

}

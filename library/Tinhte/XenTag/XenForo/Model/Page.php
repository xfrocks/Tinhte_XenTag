<?php

class Tinhte_XenTag_XenForo_Model_Page extends XFCP_Tinhte_XenTag_XenForo_Model_Page
{

	public function Tinhte_XenTag_getPageIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT node_id
			FROM xf_page
			WHERE node_id > ?
			ORDER BY node_id
		', $limit), $start);
	}

	public function Tinhte_XenTag_getPagesByIds(array $nodeIds, array $fetchOptions = array())
	{
		// this is not optimal but we don't want to do the query outselves so...
		$pages = array();

		foreach ($nodeIds as $nodeId)
		{
			$page = parent::getPageById($nodeId, $fetchOptions);

			if (!empty($page))
			{
				$pages[$nodeId] = $page;
			}
		}

		return $pages;
	}

}

<?php

class Tinhte_XenTag_XenForo_Model_Forum extends XFCP_Tinhte_XenTag_XenForo_Model_Forum
{
    public function prepareForum(array $forum)
    {
        $prepared = parent::prepareForum($forum);

        $prepared['Tinhte_XenTag_tagsList'] = $forum[Tinhte_XenTag_Constants::FIELD_FORUM_TAGS]
            ? @unserialize($forum[Tinhte_XenTag_Constants::FIELD_FORUM_TAGS])
            : array();

        return $prepared;
    }

    public function Tinhte_XenTag_getForumIdsInRange($start, $limit)
    {
        $db = $this->_getDb();

        return $db->fetchCol($db->limit('
			SELECT node_id
			FROM xf_forum
			WHERE node_id > ?
			ORDER BY node_id
		', $limit), $start);
    }

    public function Tinhte_XenTag_getForumsByIds(array $nodeIds, array $fetchOptions = array())
    {
        // this is not optimal but we don't want to do the query outselves so...
        $forums = array();

        foreach ($nodeIds as $nodeId) {
            $forum = parent::getForumById($nodeId, $fetchOptions);

            if (!empty($forum)) {
                $forums[$nodeId] = $forum;
            }
        }

        return $forums;
    }

}

<?php

class Tinhte_XenTag_Model_TagView extends XenForo_Model
{
    public function logTagView($tagId)
    {
        $this->_getDb()->query('
			INSERT ' . (XenForo_Application::get('options')->enableInsertDelayed ? 'DELAYED' : '')
            . ' INTO xf_tinhte_xentag_tag_view
				(tag_id)
			VALUES
				(?)
		', $tagId);
    }

    public function updateTagViews()
    {
        $db = $this->_getDb();

        $db->query('
            UPDATE xf_tag
            INNER JOIN (
                SELECT tag_id, COUNT(*) AS total
                FROM xf_tinhte_xentag_tag_view
                GROUP BY tag_id
            ) AS xf_tv ON (xf_tv.tag_id = xf_tag.tag_id)
            SET xf_tag.tinhte_xentag_view_count = xf_tag.tinhte_xentag_view_count + xf_tv.total
		');

        $db->query('TRUNCATE TABLE xf_tinhte_xentag_tag_view');
    }
}
<?php
class Tinhte_XenTag_Model_Content extends XenForo_Model
{
    //get list content_id have the same tag with error thread
    public function getListContentHaveTheSameTag($threadId, $numberOfContent)
    {
        return $this->fetchAllKeyed('
            SELECT DISTINCT content_id
            FROM xf_tag_content
            WHERE tag_id IN (
                                SELECT C.tag_id
                                FROM xf_tag_content C
                                WHERE C.content_id = '. $threadId .'
                            ) AND content_id <> '. $threadId .'
            ORDER BY content_id DESC
            LIMIT '. $numberOfContent .'
        ', 'content_id');
    }

}

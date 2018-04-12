<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Post extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Post
{
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        $metadata = array();

        if (!isset($data[Tinhte_XenTag_Constants::FIELD_POST_TAGS])
            && Tinhte_XenTag_Option::get('indexFirstPostTags')
        ) {
            if ($parentData
                && ($parentData['first_post_id'] == $data['post_id'] || $parentData['first_post_id'] === 0)
                && $data['message_state'] == 'visible'
                && $parentData['discussion_state'] == 'visible'
            ) {
                $data[Tinhte_XenTag_Constants::FIELD_POST_TAGS] = $parentData['tags'];
            }
        }

        if (isset($data[Tinhte_XenTag_Constants::FIELD_POST_TAGS])) {
            $title = '';
            Tinhte_XenTag_Helper::prepareSearchIndex($title, $metadata,
                $data[Tinhte_XenTag_Constants::FIELD_POST_TAGS]);
        }

        if (!empty($metadata)) {
            $indexer = new Tinhte_XenTag_XenForo_Search_Indexer($indexer, $metadata);
        }

        parent::_insertIntoIndex($indexer, $data, $parentData);
    }

}

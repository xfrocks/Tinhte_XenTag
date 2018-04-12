<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Post extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Post
{
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        $metadata = array();

        if (!isset($data[Tinhte_XenTag_Constants::FIELD_POST_TAGS])
            && Tinhte_XenTag_Option::get('indexFirstPostTags')
            && $this->_isContainsRequiredKeys($data, array('post_id', 'message_state'))
            && $this->_isContainsRequiredKeys($parentData, array('first_post_id', 'discussion_state', 'tags'))
        ) {
            if (($parentData['first_post_id'] == $data['post_id'] || $parentData['first_post_id'] === 0)
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

    protected function _isContainsRequiredKeys($data, array $keys)
    {
        if (!is_array($data)) {
            return false;
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }
}

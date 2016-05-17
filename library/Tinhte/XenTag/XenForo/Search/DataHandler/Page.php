<?php

class Tinhte_XenTag_XenForo_Search_DataHandler_Page extends XFCP_Tinhte_XenTag_XenForo_Search_DataHandler_Page
{
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        $title = $data['title'];
        $metadata = array();

        if (isset($data[Tinhte_XenTag_Constants::FIELD_PAGE_TAGS])) {
            Tinhte_XenTag_Helper::prepareSearchIndex($title, $metadata,
                $data[Tinhte_XenTag_Constants::FIELD_PAGE_TAGS]);

            $data['title'] = $title;
            $indexer = new Tinhte_XenTag_XenForo_Search_Indexer($indexer, $metadata);

        }

        parent::_insertIntoIndex($indexer, $data, $parentData);
    }

}

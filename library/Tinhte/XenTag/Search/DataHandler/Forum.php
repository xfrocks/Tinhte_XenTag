<?php

class Tinhte_XenTag_Search_DataHandler_Forum extends XenForo_Search_DataHandler_Abstract
{

    protected $_forumModel = null;

    public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
    {
        $ids = $this->_getForumModel()->Tinhte_XenTag_getForumIdsInRange($lastId, $batchSize);

        if (!$ids) {
            return false;
        }

        $this->quickIndex($indexer, $ids);

        return max($ids);
    }

    /**
     * @return Tinhte_XenTag_XenForo_Model_Forum
     */
    protected function _getForumModel()
    {
        if (!$this->_forumModel) {
            $this->_forumModel = XenForo_Model::create('XenForo_Model_Forum');
        }

        return $this->_forumModel;
    }

    public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
    {
        $forums = $this->_getForumModel()->Tinhte_XenTag_getForumsByIds($contentIds);

        foreach ($forums AS $forum) {
            $this->insertIntoIndex($indexer, $forum);
        }

        return true;
    }

    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        $forums = $this->_getForumModel()->Tinhte_XenTag_getForumsByIds($ids);

        return $forums;
    }

    public function canViewResult(array $result, array $viewingUser)
    {
        $errorPhraseKey = '';
        $null = null;

        return $this->_getForumModel()->canViewForum($result, $errorPhraseKey, $null, $viewingUser);
    }

    public function getResultDate(array $result)
    {
        return 0;
    }

    public function renderResult(XenForo_View $view, array $result, array $search)
    {
        return $view->createTemplateObject('tinhte_xentag_search_result_forum', array(
            'forum' => $result,
            'search' => $search
        ));
    }

    public function getSearchContentTypes()
    {
        return array(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM);
    }

    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        $title = $data['title'];
        $metadata = array();

        if (isset($data[Tinhte_XenTag_Constants::FIELD_FORUM_TAGS])) {
            Tinhte_XenTag_Helper::prepareSearchIndex($title, $metadata,
                $data[Tinhte_XenTag_Constants::FIELD_FORUM_TAGS]);
        }

        $indexer->insertIntoIndex(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM, $data['node_id'], $title,
            $data['description'], XenForo_Application::$time, 0, 0, $metadata);
    }

    protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
    {
        $indexer->updateIndex(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM, $data['node_id'], $fieldUpdates);
    }

    protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
    {
        $ids = array();

        foreach ($dataList AS $data) {
            $ids[] = $data['node_id'];
        }

        $indexer->deleteFromIndex(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM, $ids);
    }

}

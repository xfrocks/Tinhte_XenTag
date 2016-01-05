<?php

class Tinhte_XenTag_TagHandler_Forum extends Tinhte_XenTag_TagHandler_NodeAbstract
{
    public function getBasicContent($id)
    {
        return $this->_getForumModel()->getForumById($id);
    }

    public function updateContentTagCache(array $content, array $cache)
    {
        /** @var XenForo_DataWriter_Forum $dw */
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum');
        $dw->setExistingData($content['node_id']);
        $dw->set(Tinhte_XenTag_Constants::FIELD_FORUM_TAGS, $cache);
        $dw->save();
    }

    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        return $this->_getForumModel()->getForumsByIds($ids);
    }

    public function prepareResult(array $result, array $viewingUser)
    {
        return $this->_getForumModel()->prepareForum($result);
    }

    public function renderResult(XenForo_View $view, array $result)
    {
        return $view->createTemplateObject('tinhte_xentag_search_result_forum', array(
            'forum' => $result,
        ));
    }
}
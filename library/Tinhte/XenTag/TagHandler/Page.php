<?php

class Tinhte_XenTag_TagHandler_Page extends Tinhte_XenTag_TagHandler_NodeAbstract
{
    public function getBasicContent($id)
    {
        return $this->_getPageModel()->getPageById($id);
    }

    public function updateContentTagCache(array $content, array $cache)
    {
        /** @var XenForo_DataWriter_Page $dw */
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_Page');
        $dw->setExistingData($content['node_id']);
        $dw->set(Tinhte_XenTag_Constants::FIELD_PAGE_TAGS, $cache);
        $dw->save();
    }

    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        return $this->_getPageModel()->getPagesByIds($ids);
    }

    public function prepareResult(array $result, array $viewingUser)
    {
        return $result;
    }

    public function renderResult(XenForo_View $view, array $result)
    {
        return $view->createTemplateObject('search_result_page', array(
            'page' => $result,
        ));
    }
}
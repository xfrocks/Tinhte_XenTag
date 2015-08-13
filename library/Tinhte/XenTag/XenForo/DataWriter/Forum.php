<?php

class Tinhte_XenTag_XenForo_DataWriter_Forum extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Forum
{
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_forum'][Tinhte_XenTag_Constants::FIELD_FORUM_TAGS] = array(
            'type' => XenForo_DataWriter::TYPE_SERIALIZED,
            'default' => 'a:0:{}'
        );

        return $fields;
    }

    protected function _preSave()
    {
        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE])) {
            /** @var Tinhte_XenTag_XenForo_ControllerAdmin_Forum $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE];
            $controller->Tinhte_XenTag_actionSave($this);
        }

        parent::_preSave();
    }

    protected function _postSave()
    {
        $this->_Tinhte_XenTag_indexForSearch();

        parent::_postSave();
    }

    protected function _Tinhte_XenTag_indexForSearch()
    {
        $indexer = new XenForo_Search_Indexer();

        $data = $this->getMergedData();

        $dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Forum');

        $dataHandler->insertIntoIndex($indexer, $data);
    }

    protected function _postSaveAfterTransaction()
    {
        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE])) {
            /** @var Tinhte_XenTag_XenForo_ControllerAdmin_Forum $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE];
            $controller->Tinhte_XenTag_actionSaveAfterTransaction($this);
        }

        parent::_postSaveAfterTransaction();
    }

    protected function _postDelete()
    {
        /** @var XenForo_Model_Tag $tagModel */
        $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
        $tagModel->deleteContentTags(Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM, $this->get('node_id'));

        $this->_Tinhte_XenTag_unindexFromSearch();

        parent::_postDelete();
    }

    protected function _Tinhte_XenTag_unindexFromSearch()
    {
        $indexer = new XenForo_Search_Indexer();

        $data = $this->getMergedData();

        $dataHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_Forum');

        $dataHandler->deleteFromIndex($indexer, $data);
    }

}

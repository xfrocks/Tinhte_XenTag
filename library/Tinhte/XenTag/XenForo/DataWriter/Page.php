<?php

class Tinhte_XenTag_XenForo_DataWriter_Page extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Page
{
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_page'][Tinhte_XenTag_Constants::FIELD_PAGE_TAGS] = array(
            'type' => XenForo_DataWriter::TYPE_SERIALIZED,
            'default' => 'a:0:{}'
        );

        return $fields;
    }

    protected function _preSave()
    {
        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE])) {
            /** @var Tinhte_XenTag_XenForo_ControllerAdmin_Page $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE];
            $controller->Tinhte_XenTag_actionSave($this);
        }

        parent::_preSave();
    }

    protected function _postSaveAfterTransaction()
    {
        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE])) {
            /** @var Tinhte_XenTag_XenForo_ControllerAdmin_Page $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE];
            $controller->Tinhte_XenTag_actionSaveAfterTransaction($this);
        }

        parent::_postSaveAfterTransaction();
    }

    protected function _postDelete()
    {
        /** @var XenForo_Model_Tag $tagModel */
        $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
        $tagModel->deleteContentTags('page', $this->get('node_id'));

        parent::_postDelete();
    }

}

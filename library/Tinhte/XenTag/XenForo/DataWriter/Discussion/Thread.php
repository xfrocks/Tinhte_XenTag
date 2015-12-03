<?php

class Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread
    extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread
{
    protected function _discussionPostSave()
    {
        parent::_discussionPostSave();

        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_TAGS])) {
            /** @var Tinhte_XenTag_XenForo_ControllerPublic_Thread $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_TAGS];
            $controller->Tinhte_XenTag_actionTags($this);
        }
    }

}
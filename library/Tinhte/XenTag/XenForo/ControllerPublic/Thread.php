<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Thread
    extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Thread
{
    public function actionTags()
    {
        $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_TAGS] = $this;

        return parent::actionTags();
    }

    public function Tinhte_XenTag_actionTags(XenForo_DataWriter_Discussion_Thread $threadDw)
    {
        if (Tinhte_XenTag_Option::get('modLog')) {
            $this->_updateModeratorLogThreadEdit($threadDw->getMergedExistingData(), $threadDw);
        }
    }

}
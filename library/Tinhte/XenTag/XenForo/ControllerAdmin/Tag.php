<?php

class Tinhte_XenTag_XenForo_ControllerAdmin_Tag extends XFCP_Tinhte_XenTag_XenForo_ControllerAdmin_Tag
{
    public function actionSave()
    {
        $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_TAG_SAVE] = $this;

        return parent::actionSave();
    }

    public function Tinhte_XenTag_actionSave(XenForo_DataWriter_Tag $dw)
    {
        $dw->bulkSet($this->_input->filter(array(
            'tinhte_xentag_staff' => XenForo_Input::BOOLEAN,
        )));
    }

}
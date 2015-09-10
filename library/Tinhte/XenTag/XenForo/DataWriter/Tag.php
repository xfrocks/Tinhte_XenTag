<?php

class Tinhte_XenTag_XenForo_DataWriter_Tag extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Tag
{
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_tag']['tinhte_xentag_staff'] = array(
            'type' => XenForo_DataWriter::TYPE_BOOLEAN,
            'default' => 0,
        );

        $fields['xf_tag']['tinhte_xentag_title'] = array(
            'type' => XenForo_DataWriter::TYPE_STRING,
            'maxLength' => 255,
            'default' => '',
        );

        $fields['xf_tag']['tinhte_xentag_description'] = array(
            'type' => XenForo_DataWriter::TYPE_STRING,
            'default' => '',
        );

        return $fields;
    }

    protected function _preSave()
    {
        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_TAG_SAVE])) {
            /** @var Tinhte_XenTag_XenForo_ControllerAdmin_Tag $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_TAG_SAVE];
            $controller->Tinhte_XenTag_actionSave($this);
        }

        parent::_preSave();
    }


}
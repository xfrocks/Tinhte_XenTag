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

        $fields['xf_tag']['tinhte_xentag_url'] = array(
            'type' => XenForo_DataWriter::TYPE_STRING,
            'default' => '',
        );

        $fields['xf_tag']['tinhte_xentag_view_count'] = array(
            'type' => XenForo_DataWriter::TYPE_UINT,
            'default' => 0,
        );

        $fields['xf_tag']['tinhte_xentag_create_date'] = array(
            'type' => XenForo_DataWriter::TYPE_UINT,
            'default' => XenForo_Application::$time,
        );

        //chuyển từ XenTagSpecial sang
        $fields['xf_tag']['tinhte_xentag_richtext'] = array(
            'type' => XenForo_DataWriter::TYPE_STRING
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

        //Đoạn if(){} được chuyển từ 'Tinhte_XenTagSpecial_XenForo_DataWriter_Tag' sang
        if (isset($GLOBALS['Tinhte_XenTag_XenForo_ControllerPublic_Tag::actionEdit'])) {
            /** @var Tinhte_XenTag_XenForo_ControllerPublic_Tag $controller */
            $controller = $GLOBALS['Tinhte_XenTag_XenForo_ControllerPublic_Tag::actionEdit'];
            $controller->Tinhte_XenTag_actionEdit($this);
        }

        parent::_preSave();
    }


}
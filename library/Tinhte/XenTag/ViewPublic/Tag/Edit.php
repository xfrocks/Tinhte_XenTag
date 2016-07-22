<?php

class Tinhte_XenTag_ViewPublic_Tag_Edit extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        $this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
            $this, 'richtext', $this->_params['tag']['tinhte_xentag_richtext']);
    }

}

<?php

class Tinhte_XenTag_XenForo_ControllerAdmin_Page extends XFCP_Tinhte_XenTag_XenForo_ControllerAdmin_Page
{
    /**
     * @var XenForo_TagHandler_Tagger
     */
    protected $_tagger = null;

    public function actionEdit()
    {
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_View) {
            if (!empty($response->params['page']['node_id'])) {
                /** @var XenForo_Model_Tag $tagModel */
                $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
                $editTags = $tagModel->getTagListForEdit('page', $response->params['page']['node_id'], true);
                $response->params['Tinhte_XenTag_tags'] = $editTags;
            }
        }

        return $response;
    }

    public function actionSave()
    {
        // register this controller and let's the parent work its job
        // we will get called again from
        // Tinhte_XenTag_XenForo_DataWriter_Page::_preSave()
        $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE] = $this;

        return parent::actionSave();
    }

    public function Tinhte_XenTag_actionSave(XenForo_DataWriter_Page $dw)
    {
        if ($this->_input->inRequest('tinhte_xentag_included')) {
            /** @var XenForo_Model_Tag $tagModel */
            $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
            $this->_tagger = $tagModel->getTagger('page');
            $this->_tagger->setPermissionsFromContext(array());
            if ($dw->isUpdate()) {
                $this->_tagger->setContent($dw->get('node_id'), false);
            }

            $tags = $this->_input->filterSingle('tinhte_xentag_tags', XenForo_Input::STRING);
            $this->_tagger->setTags($tagModel->splitTags($tags));
            $errors = $this->_tagger->getErrors();
            if (!empty($errors)) {
                $dw->mergeErrors($errors);
            }
        }
    }

    public function Tinhte_XenTag_actionSaveAfterTransaction(XenForo_DataWriter_Page $dw)
    {
        if (!empty($this->_tagger)) {
            unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_PAGE_SAVE]);

            if ($dw->isInsert()) {
                $this->_tagger->setContent($dw->get('node_id'), true);
            }

            $this->_tagger->save();
        }
    }

}

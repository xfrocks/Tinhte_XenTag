<?php

class Tinhte_XenTag_XenForo_Deferred_ThreadAction extends XFCP_Tinhte_XenTag_XenForo_Deferred_ThreadAction
{
    protected $_data = array();

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $this->_data = $data;
        $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_DEFERRED_THREAD_ACTION] = $this;

        return parent::execute($deferred, $data, $targetRunTime, $status);
    }

    public function Tinhte_XenTag_execute(XenForo_DataWriter $dw)
    {
        if ($dw->isChanged('tags')) {
            return;
        }

        /** @var Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread $dw */
        if (empty($this->_data['actions']['tinhte_xentag'])) {
            return;
        }
        $data = $this->_data['actions']['tinhte_xentag'];

        if (empty($data['remove'])
            && empty($data['add'])
            && empty($data['replace'])
            && empty($data['remove_all'])
        ) {
            return;
        }

        /** @var XenForo_Model_Tag $tagModel */
        $tagModel = $dw->getModelFromCache('XenForo_Model_Tag');
        $tagger = $tagModel->getTagger('thread');
        $tagger->setContent($dw->get('thread_id'))
            ->setPermissionsFromContext($dw->getMergedData(), $dw->Tinhte_XenTag_getForumData());

        if (!empty($data['remove'])) {
            $tagTexts = $tagModel->splitTags($data['remove']);
            $tagger->removeTags($tagTexts, false);
        }

        if (!empty($data['add'])) {
            $tagTexts = $tagModel->splitTags($data['add']);
            $tagger->addTags($tagTexts);
        }

        if (!empty($data['replace'])) {
            $tagTexts = $tagModel->splitTags($data['replace']);
            $tagger->setTags($tagTexts, false);
        } elseif (!empty($data['remove_all'])) {
            $tagger->setTags(array(), false);
        }

        $tagger->save();
    }

}
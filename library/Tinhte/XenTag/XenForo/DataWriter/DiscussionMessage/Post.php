<?php

class Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post
{
    /**
     * @var XenForo_TagHandler_Tagger
     */
    protected $_tagger = null;

    protected function _postSaveAfterTransaction()
    {
        if (!empty($this->_tagger)) {
            if ($this->isInsert()) {
                $this->_tagger->setContent($this->get('post_id'), true);
            }

            $this->_tagger->save();
        }

        parent::_postSaveAfterTransaction();
    }

    protected function _setInternal($table, $field, $newValue, $forceSet = false)
    {
        if ($table === 'xf_post'
            && $field === 'message'
            && Tinhte_XenTag_Option::get('useHashtag')
        ) {
            $tagTexts = Tinhte_XenTag_Integration::parseHashtags($newValue, true);

            if ($this->_tagger === null) {
                /** @var XenForo_Model_Tag $tagModel */
                $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
                $this->_tagger = $tagModel->getTagger('post');

                if ($this->isUpdate()) {
                    $this->_tagger->setPermissionsFromContext($this->getDiscussionData(), $this->_getForumInfo());
                    $this->_tagger->setContent($this->get('post_id'), false);
                } else {
                    $this->_tagger->setPermissionsFromContext($this->_getForumInfo());
                }
            }

            $this->_tagger->setTags($tagTexts);
            $errors = $this->_tagger->getErrors();
            if (!empty($errors)) {
                $this->mergeErrors($errors);
            }
        }

        parent::_setInternal($table, $field, $newValue, $forceSet);
    }
}

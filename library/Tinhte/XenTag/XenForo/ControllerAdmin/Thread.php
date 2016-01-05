<?php

class Tinhte_XenTag_XenForo_ControllerAdmin_Thread extends XFCP_Tinhte_XenTag_XenForo_ControllerAdmin_Thread
{
    protected function _prepareThreadSearchCriteria(array $criteria)
    {
        $prepared = parent::_prepareThreadSearchCriteria($criteria);

        if (!empty($criteria[Tinhte_XenTag_Constants::THREAD_SEARCH_TAG])) {
            /** @var XenForo_Model_Tag $tagModel */
            $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
            $tag = $tagModel->getTag($criteria[Tinhte_XenTag_Constants::THREAD_SEARCH_TAG]);
            if (!empty($tag)) {
                $this->_getThreadModel();
                $prepared[Tinhte_XenTag_XenForo_Model_Thread::CONDITIONS_TAG_ID] = $tag['tag_id'];
            }
        }

        return $prepared;
    }

}
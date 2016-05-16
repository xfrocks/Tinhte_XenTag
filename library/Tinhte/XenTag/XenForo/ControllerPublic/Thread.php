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

    public function actionIndex()
    {
        try {
            return parent::actionIndex();
        } catch (XenForo_ControllerResponse_Exception $e) {
            $visitor = XenForo_Visitor::getInstance();

            $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
            $numberOfContent = Tinhte_XenTag_Option::get('suggestThreads');
            if ($numberOfContent <= 0) {
                throw $e;
            }

            /** @var Tinhte_XenTag_Model_Search $searchModel */
            $searchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');
            $threadIds = $searchModel->getThreadIdsRelatedToThreadId($threadId, $numberOfContent);

            /** @var XenForo_Model_Thread $threadModel */
            $threadModel = $this->getModelFromCache('XenForo_Model_Thread');
            $threadFetchOptions = array(
                'join' => XenForo_Model_Thread::FETCH_USER,
                'readUserId' => $visitor['user_id'],
                'watchUserId' => $visitor['user_id'],
                'postCountUserId' => $visitor['user_id'],
            );
            $threads = $threadModel->getThreadsByIds($threadIds, $threadFetchOptions);
            krsort($threads);

            $nodeId = array();
            foreach ($threads AS $thread) {
                $nodeId[] = $thread['node_id'];
            }
            $forumIds = array_unique($nodeId);

            $forums = $this->getModelFromCache('XenForo_Model_Forum')->getForumsByIds($forumIds);
            $inlineModOptions = array();
            foreach ($threads AS &$thread) {
                $forumId = $thread['node_id'];
                $forum = $forums[$forumId];

                $threadModOptions = $threadModel->addInlineModOptionToThread($thread, $forum);
                $inlineModOptions += $threadModOptions;

                $thread = $threadModel->prepareThread($thread, $forum);
            }
            unset($thread);

            $viewParam = array(
                'threads' => $threads
            );

            return $this->responseView('Tinhte_XenTag_ViewPublic_Content', 'tinhte_xentag_suggest_thread_list', $viewParam);
        }
    }

}

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
            XenForo_Phrase::loadPhrases('tinhte_xentag_sorry_this_thread_can_not_display');
            $visitor = XenForo_Visitor::getInstance();

            $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
            $numberOfContent = XenForo_Application::get('options')->Tinhte_XenTag_suggestThreads;

            /** @var Tinhte_XenTag_Model_Content $contentModel */
            $contentModel = $this->getModelFromCache('Tinhte_XenTag_Model_Content');
            $contents = $contentModel->getListContentHaveTheSameTag($threadId, $numberOfContent);

            /** @var XenForo_Model_Thread $threadModel */
            $threadModel = $this->getModelFromCache('XenForo_Model_Thread');
            $threadFetchOptions = array(
                'join' => XenForo_Model_Thread::FETCH_USER,
                'readUserId' => $visitor['user_id'],
                'watchUserId' => $visitor['user_id'],
                'postCountUserId' => $visitor['user_id'],
            );
            $threads = $threadModel->getThreadsByIds($contents, $threadFetchOptions);
            krsort($threads);

            $nodeId = array();
            foreach ($threads AS $thread) {
                $nodeId[] = $thread['node_id'];
            }
            $forumId = array_unique($nodeId);

            $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable(
                $forumId[0],
                $this->_getForumFetchOptions()
            );
            $inlineModOptions = array();
            foreach ($threads AS &$thread) {
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

    protected function _getForumFetchOptions()
    {
        $userId = XenForo_Visitor::getUserId();

        return array(
            'readUserId' => $userId,
            'watchUserId' => $userId
        );
    }

}

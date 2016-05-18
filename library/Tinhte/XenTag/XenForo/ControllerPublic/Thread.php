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
            if (!($e->getControllerResponse() instanceof XenForo_ControllerResponse_Error)) {
                throw $e;
            }
            $visitor = XenForo_Visitor::getInstance();

            /** @var XenForo_Model_Thread $threadModel */
            $threadModel = $this->getModelFromCache('XenForo_Model_Thread');
            /** @var Tinhte_XenTag_Model_Search $searchModel */
            $searchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');
            /** @var XenForo_Model_Node $nodeModel */
            $nodeModel = $this->getModelFromCache('XenForo_Model_Node');
            /** @var XenForo_ControllerHelper_ForumThreadPost $ftpHelper */
            $ftpHelper = $this->getHelper('ForumThreadPost');

            $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
            $limit = Tinhte_XenTag_Option::get('threadNotAvailableSuggestLimit');
            if ($limit <= 0) {
                throw $e;
            }

            $threadFetchOptions = array(
                'join' => XenForo_Model_Thread::FETCH_USER,
                'readUserId' => $visitor['user_id'],
                'watchUserId' => $visitor['user_id'],
                'postCountUserId' => $visitor['user_id'],
            );
            $threadIds = $searchModel->getThreadIdsRelatedToThreadId($threadId, $limit * 3);
            if (empty($threadIds)) {
                throw $e;
            }

            $theThread = $threadModel->getThreadById($threadId, $threadFetchOptions);
            if (empty($theThread)) {
                throw $e;
            }
            $theForum = $this->_getForumModel()->getForumById($theThread['node_id']);
            if (empty($theForum)) {
                throw $e;
            }

            $threads = $threadModel->getThreadsByIds($threadIds, $threadFetchOptions);
            krsort($threads);

            $nodeId = array();
            foreach ($threads AS $thread) {
                $nodeId[] = $thread['node_id'];
            }
            $forumIds = array_unique($nodeId);

            if (count($forumIds) > 1) {
                // cache permissions for all nodes
                $allNodePermissions = $nodeModel->getNodePermissionsForPermissionCombination($visitor['permission_combination_id']);
                foreach ($allNodePermissions as $nodeId => $nodePermission) {
                    $visitor->setNodePermissions($nodeId, $nodePermission);
                }
            }

            $forums = $this->_getForumModel()->getForumsByIds($forumIds);
            $preparedThreads = array();
            foreach ($threads as $threadId => $thread) {
                $forumId = $thread['node_id'];
                if (!isset($forums[$forumId])) {
                    continue;
                }
                $forum = $forums[$forumId];

                if (!$threadModel->canViewThread($thread, $forum)) {
                    continue;
                }

                $thread = $threadModel->prepareThread($thread, $forum);
                $preparedThreads[$threadId] = $thread;
            }
            if (empty($preparedThreads)) {
                throw $e;
            }

            $viewParams = $this->_getDefaultViewParams($theForum, $theThread, array());
            $viewParams['threads'] = $preparedThreads;
            $viewParams['nodeBreadCrumbs'] = $ftpHelper->getNodeBreadCrumbs($theForum);

            $response = $this->responseView(
                'Tinhte_XenTag_ViewPublic_Thread_RelatedThreads',
                'tinhte_xentag_thread_related_threads',
                $viewParams
            );

            $response->responseCode = 403;

            return $response;
        }
    }

}

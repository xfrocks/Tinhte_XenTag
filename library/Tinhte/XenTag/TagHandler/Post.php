<?php

class Tinhte_XenTag_TagHandler_Post extends XenForo_TagHandler_Abstract
{
    /**
     * @var XenForo_Model_Post
     */
    protected $_postModel = null;

    public function getPermissionsFromContext(array $context, array $parentContext = null)
    {
        return array();
    }

    public function getBasicContent($id)
    {
        return $this->_getPostModel()->getPostById($id, array(
            'join' => XenForo_Model_Post::FETCH_THREAD,
        ));
    }

    /**
     * @return XenForo_Model_Post
     */
    protected function _getPostModel()
    {
        if (!$this->_postModel) {
            $this->_postModel = XenForo_Model::create('XenForo_Model_Post');
        }

        return $this->_postModel;
    }

    public function getContentDate(array $content)
    {
        return $content['post_date'];
    }

    public function getContentVisibility(array $content)
    {
        return $content['message_state'] == 'visible';
    }

    public function updateContentTagCache(array $content, array $cache)
    {
        // intentionally left blank
    }

    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        $posts = $this->_getPostModel()->getPostsByIds($ids, array(
            'join' =>
                XenForo_Model_Post::FETCH_FORUM |
                XenForo_Model_Post::FETCH_THREAD |
                XenForo_Model_Post::FETCH_USER,
            'permissionCombinationId' => $viewingUser['permission_combination_id'],
        ));

        return $this->_getPostModel()->unserializePermissionsInList($posts, 'node_permission_cache');
    }

    public function canViewResult(array $result, array $viewingUser)
    {
        return $this->_getPostModel()->canViewPost($result, $result, $result, $null, $result['permissions'], $viewingUser);
    }

    public function prepareResult(array $result, array $viewingUser)
    {
        return $this->_getPostModel()->preparePost($result, $result, $result, $result['permissions'], $viewingUser);
    }

    public function renderResult(XenForo_View $view, array $result)
    {
        return $view->createTemplateObject('search_result_post', array(
            'thread' => $result,
            'forum' => array(
                'node_id' => $result['node_id'],
                'title' => $result['node_title'],
                'node_name' => $result['node_name']
            ),
            'post' => $result,
        ));
    }
}
<?php

class Tinhte_XenTag_WidgetRenderer_RelatedThreads extends WidgetFramework_WidgetRenderer
{
    public function extraPrepareTitle(array $widget)
    {
        if (empty($widget['title'])) {
            return new XenForo_Phrase('tinhte_xentag_related_threads');
        }

        return parent::extraPrepareTitle($widget);
    }

    public function useUserCache(array $widget)
    {
        if (!empty($widget['options']['as_guest'])) {
            // using guest permission
            // there is no reason to use the user cache
            return false;
        }

        return parent::useUserCache($widget);
    }

    protected function _getConfiguration()
    {
        return array(
            'name' => '[Tinhte] XenTag - Related Threads',
            'options' => array(
                'limit' => XenForo_Input::UINT,
                'as_guest' => XenForo_Input::UINT,
            ),
            'useCache' => true,
            'useUserCache' => true,
            'cacheSeconds' => 300, // cache for 5 minute
        );
    }

    protected function _getOptionsTemplate()
    {
        return 'tinhte_xentag_widget_related_threads_options';
    }

    protected function _validateOptionValue($optionKey, &$optionValue)
    {
        if ('limit' == $optionKey) {
            if (empty($optionValue)) {
                $optionValue = 5;
            }
        }

        return parent::_validateOptionValue($optionKey, $optionValue);
    }

    protected function _getRenderTemplate(array $widget, $positionCode, array $params)
    {
        return 'tinhte_xentag_widget_related_threads';
    }

    protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template)
    {
        $threads = array();

        if (isset($params['thread']) AND !empty($params['thread']['thread_id'])) {
            // $thread is found in the template params, try to fetch the tags
            $tags = Tinhte_XenTag_Helper::unserialize($params['thread'][Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
        }
        if (empty($tags) AND isset($params['page']) AND !empty($params['page']['node_id'])) {
            // fetch page's tags
            $tags = Tinhte_XenTag_Helper::unserialize($params['page'][Tinhte_XenTag_Constants::FIELD_PAGE_TAGS]);
        }
        if (empty($tags) AND isset($params['forum']) AND !empty($params['forum']['node_id'])) {
            // fetch forum's tags
            $tags = Tinhte_XenTag_Helper::unserialize($params['forum'][Tinhte_XenTag_Constants::FIELD_FORUM_TAGS]);
        }
        if (empty($tags) AND isset($params['resource']) AND !empty($params['resource']['resource_id'])) {
            // fetch resource's tags
            $tags = Tinhte_XenTag_Helper::unserialize($params['resource'][Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]);
        }

        if (!empty($tags)) {
            $core = WidgetFramework_Core::getInstance();

            /* @var $searchModel XenForo_Model_Search */
            $searchModel = $core->getModelFromCache('XenForo_Model_Search');
            $tagConstraint = implode(' ', array_keys($tags));
            $constraints = array(
                Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS => $tagConstraint
            );

            $searcher = new XenForo_Search_Searcher($searchModel);
            $searchQuery = '';
            $order = 'date';

            $typeHandler = $searchModel->getSearchDataHandler('thread');
            $results = $searcher->searchType($typeHandler, $searchQuery, $constraints, $order, false,
                $widget['options']['limit'] * 10);

            if (!empty($results)) {
                $threadIds = array();
                foreach ($results as $result) {
                    if ($result[0] === 'thread') {
                        $threadIds[] = $result[1];
                    }
                }

                $threadIds = array_unique($threadIds);
                $forumIds = $this->_helperGetForumIdsFromOption(array(), $params,
                    empty($widget['options']['as_guest']) ? false : true);

                $conditions = array(
                    'node_id' => $forumIds,
                    Tinhte_XenTag_XenForo_Model_Thread::CONDITIONS_THREAD_ID => $threadIds,
                    'deleted' => false,
                    'moderated' => false,
                );
                $fetchOptions = array(
                    'limit' => $widget['options']['limit'],
                    'join' => XenForo_Model_Thread::FETCH_AVATAR,
                    'order' => 'post_date',
                    'orderDirection' => 'desc',
                );

                /* @var $threadModel XenForo_Model_Thread */
                $threadModel = $core->getModelFromCache('XenForo_Model_Thread');
                $threads = $threadModel->getThreads($conditions, $fetchOptions);
            }
        }

        $template->setParam('threads', $threads);

        return $template->render();
    }

    protected function _getCacheId(array $widget, $positionCode, array $params, array $suffix = array())
    {
        if (isset($params['thread']) AND !empty($params['thread']['thread_id'])) {
            $suffix[] = 't' . $params['thread']['thread_id'];
        } elseif (isset($params['page']) AND !empty($params['page']['node_id'])) {
            $suffix[] = 'p' . $params['page']['node_id'];
        } elseif (isset($params['forum']) AND !empty($params['forum']['node_id'])) {
            $suffix[] = 'f' . $params['forum']['node_id'];
        } elseif (isset($params['resource']) AND !empty($params['resource']['resource_id'])) {
            $suffix[] = 'r' . $params['resource']['resource_id'];
        }

        return parent::_getCacheId($widget, $positionCode, $params, $suffix);
    }

}

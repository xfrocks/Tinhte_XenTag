<?php

class Tinhte_XenTag_WidgetRenderer_TaggedThreads extends WidgetFramework_WidgetRenderer
{
    public function extraPrepareTitle(array $widget)
    {
        if (empty($widget['title'])) {
            return new XenForo_Phrase('tinhte_xentag_tagged_threads');
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
            'name' => '[Tinhte] XenTag - Tagged Threads',
            'options' => array(
                'tags' => XenForo_Input::STRING,
                'limit' => XenForo_Input::UINT,
                'as_guest' => XenForo_Input::UINT,
            ),
            'useCache' => true,
            'useUserCache' => true,
            'cacheSeconds' => 3600, // cache for 1 hour
        );
    }

    protected function _getOptionsTemplate()
    {
        return 'tinhte_xentag_widget_tagged_threads_options';
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
        return 'tinhte_xentag_widget_tagged_threads';
    }

    protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template)
    {
        $threads = array();

        $core = WidgetFramework_Core::getInstance();

        /* @var $tagModel XenForo_Model_Tag */
        $tagModel = $core->getModelFromCache('XenForo_Model_Tag');
        $tagsText = $tagModel->splitTags($widget['options']['tags']);

        if (!empty($tagsText)) {
            $tags = $tagModel->getTags($tagsText);

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
            $results = $searcher->searchType($typeHandler, $searchQuery, $constraints, $order, false, $widget['options']['limit'] * 10);

            if (!empty($results)) {
                $threadIds = array();
                foreach ($results as $result) {
                    if ($result[0] === 'thread') {
                        $threadIds[] = $result[1];
                    }
                }

                $threadIds = array_unique($threadIds);
                $forumIds = $this->_helperGetForumIdsFromOption(array(), $params, empty($widget['options']['as_guest']) ? false : true);

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

}

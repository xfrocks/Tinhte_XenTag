<?php

class Tinhte_XenTag_WidgetRenderer_RelatedThreads extends WidgetFramework_WidgetRenderer_Threads
{
    public function extraPrepareTitle(array $widget)
    {
        if (empty($widget['title'])) {
            return new XenForo_Phrase('tinhte_xentag_related_threads');
        }

        return parent::extraPrepareTitle($widget);
    }

    protected function _getConfiguration()
    {
        $config = parent::_getConfiguration();
        $config['name'] = '[Tinhte] XenTag - Related Threads';
        $config['options']['use_search_engine'] = XenForo_Input::BOOLEAN;
        $config['options']['tags_first_few'] = XenForo_Input::UINT;

        return $config;
    }

    protected function _getOptionsTemplate()
    {
        return 'tinhte_xentag_widget_related_threads_options';
    }

    protected function _getThreads(
        array $widget,
        $positionCode,
        array $params,
        XenForo_Template_Abstract $renderTemplateObject
    ) {
        list(, $tags) = $this->_getSuffixAndTagsFromParams($params);
        if (empty($tags)) {
            return array();
        }

        $core = WidgetFramework_Core::getInstance();
        $useSearchEngine = isset($widget['options']['use_search_engine'])
            ? !empty($widget['options']['use_search_engine']) : true;
        $tagsFirstFew = isset($widget['options']['tags_first_few'])
            ? intval($widget['options']['tags_first_few']) : 0;

        if ($tagsFirstFew > 0 && count($tags) > $tagsFirstFew) {
            $tags = array_slice($tags, 0, $tagsFirstFew, true);
        }

        if ($useSearchEngine) {
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
                $widget['options']['limit'] * 3);

            if (empty($results)) {
                return array();
            }

            $threadIds = array();
            foreach ($results as $result) {
                if ($result[0] === 'thread') {
                    $threadIds[] = $result[1];
                }
            }
        } else {
            $db = XenForo_Application::getDb();
            $threadIds = $db->fetchCol('
                SELECT content_id
                FROM xf_tag_content
                WHERE tag_id IN (' . $db->quote(array_map('intval', array_keys($tags))) . ')
                    AND content_type = "thread"
                ORDER BY content_date DESC
                LIMIT ' . $widget['options']['limit'] * 3 . '
            ');
        }

        if (empty($threadIds)) {
            return array();
        }
        $core->getModelFromCache('XenForo_Model_Thread');
        $conditions = array(WidgetFramework_Model_Thread::CONDITIONS_THREAD_ID => $threadIds);

        if (!empty($params['thread']['thread_id'])) {
            $conditions[WidgetFramework_Model_Thread::CONDITIONS_THREAD_ID_NOT] = $params['thread']['thread_id'];
        } elseif (!empty($params['threads'])) {
            $conditions[WidgetFramework_Model_Thread::CONDITIONS_THREAD_ID_NOT] = array_keys($params['threads']);
        }

        // TODO: make this an option
        $widget['options']['type'] = 'recent';

        return $this->_getThreadsWithConditions($conditions, $widget, $positionCode, $params, $renderTemplateObject);
    }

    protected function _getCacheId(array $widget, $positionCode, array $params, array $suffix = array())
    {
        list($tagSuffix,) = $this->_getSuffixAndTagsFromParams($params);
        if (strlen($tagSuffix) > 0) {
            $suffix[] = $tagSuffix;
        }

        return parent::_getCacheId($widget, $positionCode, $params, $suffix);
    }

    protected function _getSuffixAndTagsFromParams(array $params)
    {
        $suffix = '';
        $tags = array();

        if (isset($params['thread']) && !empty($params['thread']['thread_id'])) {
            $suffix = 't' . $params['thread']['thread_id'];
            $tags = Tinhte_XenTag_Helper::unserializeFromArray($params['thread'],
                Tinhte_XenTag_Constants::FIELD_THREAD_TAGS);
        }

        if (count($tags) === 0 && !empty($params['page']['node_id'])) {
            $suffix = 'p' . $params['page']['node_id'];
            $tags = Tinhte_XenTag_Helper::unserializeFromArray($params['page'],
                Tinhte_XenTag_Constants::FIELD_PAGE_TAGS);
        }

        if (count($tags) === 0 && !empty($params['forum']['node_id'])) {
            $suffix = 'f' . $params['forum']['node_id'];
            $tags = Tinhte_XenTag_Helper::unserializeFromArray($params['forum'],
                Tinhte_XenTag_Constants::FIELD_FORUM_TAGS);
        }

        if (count($tags) === 0 && !empty($params['resource']['resource_id'])) {
            $suffix = 'r' . $params['resource']['resource_id'];
            $tags = Tinhte_XenTag_Helper::unserializeFromArray($params['resource'],
                Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS);
        }

        return array($suffix, $tags);
    }
}

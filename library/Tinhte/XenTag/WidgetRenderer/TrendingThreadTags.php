<?php

class Tinhte_XenTag_WidgetRenderer_TrendingThreadTags extends WidgetFramework_WidgetRenderer
{
    public function extraPrepareTitle(array $widget)
    {
        if (empty($widget['title'])) {
            return new XenForo_Phrase('tinhte_xentag_trending');
        }

        return parent::extraPrepareTitle($widget);
    }

    protected function _getConfiguration()
    {
        return array(
            'name' => '[Tinhte] XenTag - Trending Thread Tags',
            'options' => array(
                'forums' => XenForo_Input::ARRAY_SIMPLE,
                'days' => XenForo_Input::UINT,
                'days_created' => XenForo_Input::UINT,
                'limit' => XenForo_Input::UINT
            ),
            'useCache' => true,
            'cacheSeconds' => 3600, // cache for 1 hour
        );
    }

    protected function _getOptionsTemplate()
    {
        return 'tinhte_xentag_widget_trending_thread_tags_options';
    }

    protected function _renderOptions(XenForo_Template_Abstract $template)
    {
        $params = $template->getParams();

        $forums = $this->_helperPrepareForumsOptionSource(empty($params['options']['forums'])
            ? array() : $params['options']['forums'], true);
        $template->setParam('forums', $forums);

        return parent::_renderOptions($template);
    }

    protected function _getRenderTemplate(array $widget, $positionCode, array $params)
    {
        return 'tinhte_xentag_widget_trending';
    }

    protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template)
    {
        $core = WidgetFramework_Core::getInstance();
        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $core->getModelFromCache('XenForo_Model_Tag');

        if (!empty($widget['options']['days'])) {
            $days = $widget['options']['days'];
        } else {
            $days = Tinhte_XenTag_Option::get('trendingDays');
        }
        $cutoff = XenForo_Application::$time - $days * 86400;

        if (!empty($widget['options']['days_created'])) {
            $daysCreated = $widget['options']['days_created'];
        } else {
            $daysCreated = Tinhte_XenTag_Option::get('trendingDaysCreated');
        }
        $cutoffCreated = XenForo_Application::$time - $daysCreated * 86400;

        if (!empty($widget['options']['limit'])) {
            $limit = $widget['options']['limit'];
        } else {
            $limit = Tinhte_XenTag_Option::get('trendingMax');
        }

        $forumIds = array();
        if (!empty($widget['options']['forums'])) {
            $forumIds = $this->_helperGetForumIdsFromOption($widget['options']['forums'], $params, true);
        }

        $tags = $tagModel->Tinhte_XenTag_getTrendingThreadTags($cutoff, $limit, $cutoffCreated, $forumIds);
        $tagsLevels = $tagModel->getTagCloudLevels($tags);

        $template->setParam('tags', $tags);
        $template->setParam('tagsLevels', $tagsLevels);

        return $template->render();
    }

    protected function _getCacheId(array $widget, $positionCode, array $params, array $suffix = array())
    {
        if (!empty($widget['options']['forums'])
            && $this->_helperDetectSpecialForums($widget['options']['forums'])
        ) {
            if (is_callable(array($this, '_helperGetForumIdForCache'))) {
                $forumId = $this->_helperGetForumIdForCache($widget['options']['forums'], $params, true);
                if (!empty($forumId)) {
                    $suffix[] = 'f' . $forumId;
                }
            } else {
                $forumIds = $this->_helperGetForumIdsFromOption($widget['options']['forums'], $params, true);
                if (!empty($forumIds)) {
                    $forumSuffix = implode('|', $forumIds);
                    if (strlen($forumSuffix) > 32) {
                        $forumSuffix = md5(serialize($forumIds));
                    }
                    $suffix[] = 'f' . $forumSuffix;
                }
            }
        }

        return parent::_getCacheId($widget, $positionCode, $params, $suffix);
    }

}

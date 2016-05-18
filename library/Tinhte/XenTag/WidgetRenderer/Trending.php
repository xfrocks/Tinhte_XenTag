<?php

class Tinhte_XenTag_WidgetRenderer_Trending extends WidgetFramework_WidgetRenderer
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
            'name' => '[Tinhte] XenTag - Trending',
            'options' => array(
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
        return 'tinhte_xentag_widget_trending_options';
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

        $tags = $tagModel->Tinhte_XenTag_getTrendingTags($cutoff, $limit, $cutoffCreated);
        if (empty($tags)) {
            return '';
        }

        $tagsLevels = $tagModel->getTagCloudLevels($tags);

        $template->setParam('tags', $tags);
        $template->setParam('tagsLevels', $tagsLevels);

        return $template->render();
    }

}

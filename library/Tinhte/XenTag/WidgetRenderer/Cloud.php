<?php

class Tinhte_XenTag_WidgetRenderer_Cloud extends WidgetFramework_WidgetRenderer
{
    public function extraPrepareTitle(array $widget)
    {
        if (empty($widget['title'])) {
            return new XenForo_Phrase('tinhte_xentag_tag_cloud');
        }

        return parent::extraPrepareTitle($widget);
    }

    protected function _getConfiguration()
    {
        return array(
            'name' => '[Tinhte] XenTag - Tag Cloud',
            'options' => array(
                'limit' => XenForo_Input::UINT,
            ),
            'useCache' => true,
            'cacheSeconds' => 3600, // cache for 1 hour
        );
    }

    protected function _getOptionsTemplate()
    {
        return 'tinhte_xentag_widget_cloud_options';
    }

    protected function _validateOptionValue($optionKey, &$optionValue)
    {
        if ('limit' == $optionKey) {
            if (empty($optionValue)) {
                $optionValue = XenForo_Application::getOptions()->get('tagCloud', 'count');
            }
        }

        return parent::_validateOptionValue($optionKey, $optionValue);
    }

    protected function _getRenderTemplate(array $widget, $positionCode, array $params)
    {
        return 'tinhte_xentag_widget_cloud';
    }

    protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template)
    {

        $core = WidgetFramework_Core::getInstance();
        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $core->getModelFromCache('XenForo_Model_Tag');

        if (!empty($widget['options']['limit'])) {
            $limit = $widget['options']['limit'];
        } else {
            $limit = XenForo_Application::getOptions()->get('tagCloud', 'count');
        }

        $tags = $tagModel->getTagsForCloud($limit);
        $tagsLevels = $tagModel->getTagCloudLevels($tags);

        $template->setParam('tags', $tags);
        $template->setParam('tagsLevels', $tagsLevels);

        return $template->render();
    }

    protected function _getExtraDataLink(array $widget)
    {
        return XenForo_Link::buildPublicLink('tags');
    }

}

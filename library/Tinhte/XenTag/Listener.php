<?php

class Tinhte_XenTag_Listener
{

    public static function load_class($class, array &$extend)
    {
        static $classes = array(
            'bdApi_ControllerApi_Search',

            'XenForo_BbCode_Formatter_Base',
            'XenForo_BbCode_Formatter_HtmlEmail',
            'XenForo_BbCode_Formatter_Text',
            'XenForo_BbCode_Formatter_Wysiwyg',

            'XenForo_ControllerAdmin_Forum',
            'XenForo_ControllerAdmin_Page',
            'XenForo_ControllerPublic_Tag',
            'XenForo_ControllerPublic_Watched',

            'XenForo_DataWriter_DiscussionMessage_Post',
            'XenForo_DataWriter_Forum',
            'XenForo_DataWriter_Page',

            'XenForo_Model_Forum',
            'XenForo_Model_ForumWatch',
            'XenForo_Model_Tag',
            'XenForo_Model_Thread',

            'XenForo_Search_DataHandler_Page',
            'XenForo_Search_DataHandler_Post',

            'XenForo_ViewPublic_Page_View',
            'XenForo_ViewPublic_Thread_View',

            // XenForo 1.2+
            'XenForo_Html_Renderer_BbCode',

            // XenForo 1.5+
            'XenForo_TagHandler_Tagger',
        );

        if (in_array($class, $classes)) {
            $extend[] = 'Tinhte_XenTag_' . $class;
        }
    }

    public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {

        if ($hookName == 'tinhte_xentag_tag_cloud_item') {
            // our special hook to populate data to the sidebar
            // doing this will make it super-easy to use the sidebar template
            // just put the include statement in the target page and you are done!
            // <xen:include template="tinhte_xentag_sidebar_cloud" />
            // supported parameters:
            // - max: maximum number of links
            /** @var XenForo_Model_Tag $tagModel */
            $tagModel = XenForo_Model::create('XenForo_Model_Tag');

            $tagCloud = $tagModel->getTagsForCloud(
                isset($hookParams['max']) ? $hookParams['max'] : XenForo_Application::getOptions()->get('tagCloud', 'count'),
                XenForo_Application::getOptions()->get('tagCloudMinUses')
            );
            $tagCloudLevels = $tagModel->getTagCloudLevels($tagCloud);
            $results = '';

            foreach ($tagCloud as $tag) {
                $search = array(
                    '{TAG_TEXT}',
                    '{TAG_LINK}',
                    '{TAG_CONTENT_COUNT}',
                    '{TAG_LEVEL}'
                );
                $replace = array(
                    htmlspecialchars($tag['tag']),
                    XenForo_Link::buildPublicLink('tags', $tag),
                    XenForo_Template_Helper_Core::numberFormat($tag['use_count']),
                    $tagCloudLevels[$tag['tag_id']],
                );
                $results .= str_replace($search, $replace, $contents);
            }

            $contents = $results;
        }
    }

    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += Tinhte_XenTag_FileSums::getHashes();
    }

    public static function widget_framework_ready(array &$renderers)
    {
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_Cloud';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_RelatedThreads';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_TaggedThreads';
    }

}

<?php

class Tinhte_XenTag_Listener
{

    public static function load_class($class, array &$extend)
    {
        static $classes = array(
            'XenForo_BbCode_Formatter_Base',
            'XenForo_BbCode_Formatter_HtmlEmail',
            'XenForo_BbCode_Formatter_Text',
            'XenForo_BbCode_Formatter_Wysiwyg',

            'XenForo_ControllerAdmin_Forum',
            'XenForo_ControllerAdmin_Page',
            'XenForo_ControllerAdmin_Tag',
            'XenForo_ControllerAdmin_Thread',
            'XenForo_ControllerPublic_Tag',
            'XenForo_ControllerPublic_Thread',
            'XenForo_ControllerPublic_Watched',

            'XenForo_DataWriter_Discussion_Thread',
            'XenForo_DataWriter_DiscussionMessage_Post',
            'XenForo_DataWriter_Forum',
            'XenForo_DataWriter_Page',
            'XenForo_DataWriter_Tag',

            'XenForo_Deferred_ThreadAction',

            'XenForo_Model_Forum',
            'XenForo_Model_ForumWatch',
            'XenForo_Model_Tag',
            'XenForo_Model_Thread',

            'XenForo_Search_DataHandler_Page',
            'XenForo_Search_DataHandler_Post',

            'XenForo_ViewPublic_Page_View',
            'XenForo_ViewPublic_Tag_View',
            'XenForo_ViewPublic_Thread_View',
            'XenForo_ViewPublic_Thread_ViewNewPosts',

            // XenForo 1.2+
            'XenForo_Html_Renderer_BbCode',

            // XenForo 1.5+
            'XenForo_TagHandler_Tagger',
        );

        if (in_array($class, $classes)) {
            $extend[] = 'Tinhte_XenTag_' . $class;
        }
    }

    public static function template_create(&$templateName, array &$params)
    {
        switch ($templateName) {
            case 'post':
                Tinhte_XenTag_ContentWrapper_Post::wrap($params);
                break;
        }
    }

    public static function file_health_check(
        /** @noinspection PhpUnusedParameterInspection */
        XenForo_ControllerAdmin_Abstract $controller,
        array &$hashes
    ) {
        $hashes += Tinhte_XenTag_FileSums::getHashes();
    }

    public static function widget_framework_ready(array &$renderers)
    {
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_Cloud';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_RelatedThreads';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_TaggedThreads';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_Trending';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_TrendingThreadTags';
    }

}

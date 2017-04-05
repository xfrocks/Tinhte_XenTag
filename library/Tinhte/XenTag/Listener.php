<?php

class Tinhte_XenTag_Listener
{

    public static function load_class($class, array &$extend)
    {
        static $classes = array(
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

            // XenForo 1.5+
            'XenForo_TagHandler_Tagger',
        );

        if (in_array($class, $classes)) {
            $extend[] = 'Tinhte_XenTag_' . $class;
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
        $addOns = XenForo_Application::get('addOns');
        if (empty($addOns['widget_framework'])) {
            return;
        }
        $wfVersionId = $addOns['widget_framework'];

        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_Cloud';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_Trending';
        $renderers[] = 'Tinhte_XenTag_WidgetRenderer_TrendingThreadTags';

        if ($wfVersionId >= 2060319) {
            $renderers[] = 'Tinhte_XenTag_WidgetRenderer_RelatedThreads';
        }
    }

    public static function bb_code_hashtag(array $tag, array $rendererStates, XenForo_BbCode_Formatter_Base $formatter)
    {
        $tagText = $formatter->stringifyTree($tag['children']);
        if (substr($tagText, 0, 1) === '#') {
            $tagText = substr($tagText, 1);
        }

        $displayText = $tagText;

        $view = $formatter->getView();

        if ($view) {
            // standard rendering
            $template = $view->createTemplateObject('tinhte_xentag_bb_code_tag_hashtag', array(
                'tagText' => $tagText,
                'displayText' => $displayText,
            ));
            return $template->render();
        } elseif (is_callable(array($formatter, 'handleTag'))) {
            // rendering text
            return '#' . $tagText;
        } else {
            // rendering something else, most likely email
            /** @noinspection HtmlUnknownTarget */
            return sprintf('<a href="%s" class="Tinhte_XenTag_HashTag" style="text-decoration:none">'
                . '<span class="hash">#</span><span class="text">%s</span></a>',
                XenForo_Link::buildPublicLink('tags', null, array('t' => $tagText)),
                htmlentities($displayText));
        }
    }

    public static function load_class_XenForo_Html_Renderer_BbCode($class, array &$extend)
    {
        if ($class === 'XenForo_Html_Renderer_BbCode') {
            $extend[] = 'Tinhte_XenTag_XenForo_Html_Renderer_BbCode';
        }
    }

    public static function load_class_XenForo_BbCode_Formatter_Wysiwyg($class, array &$extend)
    {
        if ($class === 'XenForo_BbCode_Formatter_Wysiwyg') {
            $extend[] = 'Tinhte_XenTag_XenForo_BbCode_Formatter_Wysiwyg';
        }
    }

    public static function load_class_0bb181793ccd6591174690a2577cf7a8($class, array &$extend)
    {
        if ($class === 'XenResource_ViewPublic_Resource_Description') {
            $extend[] = 'Tinhte_XenTag_XenResource_ViewPublic_Resource_Description';
        }
    }

    public static function load_class_XenForo_ViewPublic_Thread_View($class, array &$extend)
    {
        if ($class === 'XenForo_ViewPublic_Thread_View') {
            $extend[] = 'Tinhte_XenTag_XenForo_ViewPublic_Thread_View';
        }
    }

    public static function load_class_XenForo_ViewPublic_Thread_ViewNewPosts($class, array &$extend)
    {
        if ($class === 'XenForo_ViewPublic_Thread_ViewNewPosts') {
            $extend[] = 'Tinhte_XenTag_XenForo_ViewPublic_Thread_ViewNewPosts';
        }
    }
}

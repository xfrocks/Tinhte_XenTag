<?php

class Tinhte_XenTag_Listener
{
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
        // new XenForo_Phrase('custom_bb_code_hashtag_title')
        // new XenForo_Phrase('custom_bb_code_hashtag_desc')
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

    public static function load_class_XenForo_TagHandler_Tagger($class, array &$extend)
    {
        if ($class === 'XenForo_TagHandler_Tagger') {
            $extend[] = 'Tinhte_XenTag_XenForo_TagHandler_Tagger';
        }
    }

    public static function load_class_XenForo_Deferred_ThreadAction($class, array &$extend)
    {
        if ($class === 'XenForo_Deferred_ThreadAction') {
            $extend[] = 'Tinhte_XenTag_XenForo_Deferred_ThreadAction';
        }
    }

    public static function load_class_XenForo_ControllerAdmin_Forum($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_Forum') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerAdmin_Forum';
        }
    }

    public static function load_class_XenForo_ControllerAdmin_Page($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_Page') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerAdmin_Page';
        }
    }

    public static function load_class_XenForo_ControllerAdmin_Tag($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_Tag') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerAdmin_Tag';
        }
    }

    public static function load_class_XenForo_ControllerAdmin_Thread($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_Thread') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerAdmin_Thread';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Tag($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Tag') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerPublic_Tag';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Thread($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Thread') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerPublic_Thread';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Watched($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Watched') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerPublic_Watched';
        }
    }

    public static function load_class_XenForo_DataWriter_Discussion_Thread($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_Discussion_Thread') {
            $extend[] = 'Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread';
        }
    }

    public static function load_class_4f477c58235ffb475271e2521731d700($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_DiscussionMessage_Post') {
            $extend[] = 'Tinhte_XenTag_XenForo_DataWriter_DiscussionMessage_Post';
        }
    }

    public static function load_class_XenForo_DataWriter_Forum($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_Forum') {
            $extend[] = 'Tinhte_XenTag_XenForo_DataWriter_Forum';
        }
    }

    public static function load_class_XenForo_DataWriter_Page($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_Page') {
            $extend[] = 'Tinhte_XenTag_XenForo_DataWriter_Page';
        }
    }

    public static function load_class_XenForo_DataWriter_Tag($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_Tag') {
            $extend[] = 'Tinhte_XenTag_XenForo_DataWriter_Tag';
        }
    }

    public static function load_class_XenForo_Model_Forum($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Forum') {
            $extend[] = 'Tinhte_XenTag_XenForo_Model_Forum';
        }
    }

    public static function load_class_XenForo_Model_ForumWatch($class, array &$extend)
    {
        if ($class === 'XenForo_Model_ForumWatch') {
            $extend[] = 'Tinhte_XenTag_XenForo_Model_ForumWatch';
        }
    }

    public static function load_class_XenForo_Model_Tag($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Tag') {
            $extend[] = 'Tinhte_XenTag_XenForo_Model_Tag';
        }
    }

    public static function load_class_XenForo_Model_Thread($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Thread') {
            $extend[] = 'Tinhte_XenTag_XenForo_Model_Thread';
        }
    }

    public static function load_class_XenForo_Search_DataHandler_Page($class, array &$extend)
    {
        if ($class === 'XenForo_Search_DataHandler_Page') {
            $extend[] = 'Tinhte_XenTag_XenForo_Search_DataHandler_Page';
        }
    }

    public static function load_class_XenForo_Search_DataHandler_Post($class, array &$extend)
    {
        if ($class === 'XenForo_Search_DataHandler_Post') {
            $extend[] = 'Tinhte_XenTag_XenForo_Search_DataHandler_Post';
        }
    }

    public static function load_class_XenForo_ViewPublic_Page_View($class, array &$extend)
    {
        if ($class === 'XenForo_ViewPublic_Page_View') {
            $extend[] = 'Tinhte_XenTag_XenForo_ViewPublic_Page_View';
        }
    }

    public static function load_class_XenForo_ViewPublic_Tag_View($class, array &$extend)
    {
        if ($class === 'XenForo_ViewPublic_Tag_View') {
            $extend[] = 'Tinhte_XenTag_XenForo_ViewPublic_Tag_View';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Misc($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Misc') {
            $extend[] = 'Tinhte_XenTag_XenForo_ControllerPublic_Misc';
        }
    }

    public static function load_class_bdApi_ControllerApi_Index($class, array &$extend)
    {
        if ($class === 'bdApi_ControllerApi_Index') {
            $extend[] = 'Tinhte_XenTag_bdApi_ControllerApi_Index';
        }
    }

    public static function load_class_bdApi_ControllerApi_Tag($class, array &$extend)
    {
        if ($class === 'bdApi_ControllerApi_Tag') {
            $extend[] = 'Tinhte_XenTag_bdApi_ControllerApi_Tag';
        }
    }
}

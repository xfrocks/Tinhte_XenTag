<?php

class Tinhte_XenTag_ContentWrapper_Post extends Tinhte_XenTag_ContentWrapper_Abstract
{
    public static function wrap(array &$params)
    {
        $mode = Tinhte_XenTag_Option::get('autoTagMode');
        if ($mode === Tinhte_XenTag_Option::AUTO_TAG_MODE_DISABLED) {
            return;
        }

        if (empty($params['thread'])) {
            return;
        }

        if (isset($params['posts']) && is_array($params['posts'])) {
            foreach (array_keys($params['posts']) as $key) {
                self::wrapPost($mode, $params['posts'][$key], $params['thread']);
            }
        }

        if (isset($params['post'])) {
            self::wrapPost($mode, $params['post'], $params['thread']);
        }
    }

    public static function wrapPost($mode, array &$post, array &$thread)
    {
        if (!isset($post['messageHtml'])) {
            return;
        }

        $tags = array();
        $useGlobalTags = false;

        if (!empty($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS])) {
            $tags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
        }

        switch ($mode) {
            case Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY:
                if (!isset($post['position']) || $post['position'] > 0) {
                    return;
                }
                break;
            case Tinhte_XenTag_Option::AUTO_TAG_MODE_ALL_TAGS:
                $useGlobalTags = true;
                break;
        }

        $class = XenForo_Application::resolveDynamicClass(__CLASS__);
        $post['messageHtml'] = new $class($post['messageHtml'], $tags, $useGlobalTags);
    }

}

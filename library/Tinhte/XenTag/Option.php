<?php

class Tinhte_XenTag_Option
{

    const AUTO_TAG_MODE_THREAD_TAGS = 'thread_tags';
    const AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY = 'thread_tags_first_post_only';
    const AUTO_TAG_RESOURCE_TAGS = 'resource_tags';
    const AUTO_TAG_MODE_ALL_TAGS = 'all_tags';
    const AUTO_TAG_MODE_DISALBED = 'disabled';

    const LINK_FORMAT_BEAUTIFUL = 'beautiful';

    public static function get($key)
    {
        $options = XenForo_Application::get('options');

        static $keyPrefix = 'Tinhte_XenTag_';

        static $availableAutoTagModes = array(
            self::AUTO_TAG_MODE_THREAD_TAGS,
            self::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY,
            self::AUTO_TAG_MODE_ALL_TAGS,
            self::AUTO_TAG_MODE_DISALBED
        );

        switch ($key) {
            case 'cloudLevelCount':
                return 3;
            case 'majorSection':
                return 'forums';
            case 'searchForceUseCache':
                return !XenForo_Application::debugMode();

            case 'autoTagMode':
                $mode = $options->get($keyPrefix . $key);
                if (!in_array($mode, $availableAutoTagModes)) {
                    $mode = self::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY;
                }
                return $mode;

            case 'latestTaggedContentsLimit':
                return 10;

            case 'tagMinLength':
                return $options->get('tagLength', 'min');

            case 'seoKwStuffing':
                return true;
        }

        return $options->get($keyPrefix . $key);
    }

    public static function verifyTagMaxLength(&$value)
    {
        if ($value > 100) {
            $value = 100;
            // TODO: throw error?
        }

        return true;
    }

    public static function xfrmFound()
    {
        static $result = false;

        if ($result === false) {
            $result = 0;

            $addOns = XenForo_Application::get('addOns');
            if (isset($addOns['XenResource'])) {
                $result = intval($addOns['XenResource']);
            }
        }

        return $result;
    }

}

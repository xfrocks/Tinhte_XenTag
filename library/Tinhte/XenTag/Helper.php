<?php

class Tinhte_XenTag_Helper
{
    public static function prepareSearchIndex(&$title, array &$metadata, $tagsStr)
    {
        if (!empty($tagsStr)) {
            $tags = @unserialize($tagsStr);
            if ($tags) {
                $tagIds = array();
                foreach ($tags AS $tagId => $tag) {
                    if (is_array($tag)
                        && isset($tag['tag'])
                    ) {
                        $title .= " $tag[tag]";
                        $tagIds[] = $tagId;
                    }
                }

                $metadata[Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS] = $tagIds;
            }
        }
    }

    public static function unserialize($string)
    {
        $array = $string;

        if (!is_array($array)) {
            // TODO: use XenForo_Helper_Php::safeUnserialize
            $array = @unserialize($array);
        }

        if (!is_array($array)) {
            $array = array();
        }

        return $array;
    }

    public static function unserializeFromArray(array $array, $key)
    {
        if (!isset($array[$key])) {
            return array();
        }

        return self::unserialize($array[$key]);
    }
}

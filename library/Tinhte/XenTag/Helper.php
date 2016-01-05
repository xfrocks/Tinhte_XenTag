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
            $array = @unserialize($array);
        }

        if (empty($array)) {
            $array = array();
        }

        return $array;
    }

}

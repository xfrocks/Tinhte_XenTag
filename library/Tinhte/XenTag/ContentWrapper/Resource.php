<?php

class Tinhte_XenTag_ContentWrapper_Resource extends Tinhte_XenTag_ContentWrapper_Abstract
{
    public static function wrap(array &$params)
    {
        $mode = Tinhte_XenTag_Option::get('autoTagResource');
        if ($mode === Tinhte_XenTag_Option::AUTO_TAG_MODE_DISABLED) {
            return;
        }

        if (!isset($params['resource'])
            || !isset($params['update'])
        ) {
            return;
        }
        $resource =& $params['resource'];
        $update =& $params['update'];

        $tags = array();
        $useGlobalTags = false;

        if (!empty($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS])) {
            $tags = Tinhte_XenTag_Helper::unserialize($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]);
        }

        switch ($mode) {
            case Tinhte_XenTag_Option::AUTO_TAG_MODE_ALL_TAGS:
                $useGlobalTags = true;
                break;
        }

        $class = XenForo_Application::resolveDynamicClass(__CLASS__);
        $update['messageHtml'] = new $class($update['messageHtml'], $tags, $useGlobalTags);
    }

}

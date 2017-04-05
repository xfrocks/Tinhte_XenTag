<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_Wysiwyg extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_Wysiwyg
{
    protected function _setupCustomTagInfo($tagName, array $tag)
    {
        if ($tagName === 'hashtag') {
            return array(
                'replace' => array(
                    '<span class="Tinhte_XenTag_HashTag" style="color: blue; text-decoration: underline">',
                    '</span>'
                ),
            );
        }

        return parent::_setupCustomTagInfo($tagName, $tag);
    }
}

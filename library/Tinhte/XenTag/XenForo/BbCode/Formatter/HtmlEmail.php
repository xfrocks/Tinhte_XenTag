<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_HtmlEmail extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_HtmlEmail
{
    public function getTags()
    {
        $tags = parent::getTags();

        $tags['hashtag'] = array(
            'plainChildren' => true,
            'callback' => array(
                $this,
                'renderTagHashtag'
            )
        );

        return $tags;
    }

    public function renderTagHashtag(array $tag)
    {
        $tagText = $this->stringifyTree($tag['children']);
        if (substr($tagText, 0, 1) === '#') {
            $tagText = substr($tagText, 1);
        }

        $tag = array('tag_text' => $tagText);
        $displayText = $tagText;

        /** @noinspection HtmlUnknownTarget */
        return sprintf('#<a href="%s" style="text-decoration: none">%s</a>',
            XenForo_Link::buildPublicLink('tags', $tag),
            htmlentities($displayText));
    }

}

<?php

class Tinhte_XenTag_BbCode_Formatter_HashtagPick extends XFCP_Tinhte_XenTag_BbCode_Formatter_HashtagPick
{
    protected $_Tinhte_XenTag_tagTexts = array();

    public function Tinhte_XenTag_getTagTexts()
    {
        return $this->_Tinhte_XenTag_tagTexts;
    }

    public function getTags()
    {
        $tags = parent::getTags();

        $tags['hashtag'] = array(
            'plainChildren' => true,
            'callback' => array(
                $this,
                'pickHashtag'
            )
        );

        return $tags;
    }

    public function filterString($string, array $rendererStates)
    {
        return $string;
    }

    public function pickHashtag(array $tag, array $rendererStates)
    {
        $tagText = $this->stringifyTree($tag['children']);
        if (substr($tagText, 0, 1) === '#') {
            $tagText = substr($tagText, 1);
        }

        $this->_Tinhte_XenTag_tagTexts[] = $tagText;

        return $this->renderTagUnparsed($tag, $rendererStates);
    }

    public function renderTree(array $tree, array $extraStates = array())
    {
        $this->_Tinhte_XenTag_tagTexts = array();

        return parent::renderTree($tree, $extraStates);
    }
}

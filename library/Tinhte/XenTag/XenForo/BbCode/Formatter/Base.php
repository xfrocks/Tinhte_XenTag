<?php

class Tinhte_XenTag_XenForo_BbCode_Formatter_Base extends XFCP_Tinhte_XenTag_XenForo_BbCode_Formatter_Base
{

    public function getTags()
    {
        // intentionally not check $this->_tags
        $tags = parent::getTags();

        $tags['tag'] = array(
            'plainChildren' => true,
            'callback' => array(
                $this,
                'renderTagTag'
            )
        );

        $tags['hashtag'] = array(
            'plainChildren' => true,
            'callback' => array(
                $this,
                'renderTagHashtag'
            )
        );

        return $tags;
    }

    public function preLoadTemplates(XenForo_View $view)
    {
        $view->preLoadTemplate('tinhte_xentag_bb_code_tag_tag');
        $view->preLoadTemplate('tinhte_xentag_bb_code_tag_hashtag');

        parent::preLoadTemplates($view);
    }

    public function renderTagTag(array $tag, array $rendererStates)
    {
        $tagText = $this->stringifyTree($tag['children']);
        $displayText = $tagText;

        // support option version of this tag
        // the tag text can be put as option (must be base64 encoded)
        if (!empty($tag['option'])) {
            $option = $tag['option'];
            $optionDecoded = @base64_decode($option);
            if (!empty($optionDecoded)) {
                $tagText = $optionDecoded;
            }
        }

        if ($this->_view) {
            $template = $this->_view->createTemplateObject('tinhte_xentag_bb_code_tag_tag', array(
                'tagText' => $tagText,
                'displayText' => $displayText,
            ));
            return $template->render();
        } else {
            // sometime we don't have a view
            // so just render everything ourself...
            return '<a href="' . XenForo_Link::buildPublicLink('tags', null, array('t' => $tagText)) . '" class="Tinhte_XenTag_TagLink">' . htmlentities($displayText) . '</a>';
        }
    }

    public function renderTagHashtag(array $tag, array $rendererStates)
    {
        $tagText = $this->stringifyTree($tag['children']);
        if (substr($tagText, 0, 1) === '#') {
            $tagText = substr($tagText, 1);
        }

        $displayText = $tagText;

        if ($this->_view) {
            $template = $this->_view->createTemplateObject('tinhte_xentag_bb_code_tag_hashtag', array(
                'tagText' => $tagText,
                'displayText' => $displayText,
            ));
            return $template->render();
        } else {
            // sometime we don't have a view
            // so just render everything ourself...
            return '<a href="' . XenForo_Link::buildPublicLink('tags', null, array('t' => $tagText)) . '" class="Tinhte_XenTag_HashTag"><span class="hash">#</span><span class="text">' . htmlentities($displayText) . '</span></a>';
        }
    }

}

<?php

abstract class Tinhte_XenTag_ContentWrapper_Abstract
{
    static protected $_tagModel = false;
    protected $_html = '';
    protected $_tags = array();
    protected $_useGlobalTags = false;

    protected function __construct($html, $tags, $useGlobalTags)
    {
        $this->_html = $html;
        $this->_tags = $tags;
        $this->_useGlobalTags = $useGlobalTags;
    }

    public function __toString()
    {
        return strval($this->render());
    }

    public function render()
    {
        if ($this->_useGlobalTags) {
            $globalTags = $this->_getTagModel()->Tinhte_XenTag_getTagsFromCache();
            foreach ($globalTags as $tag) {
                $this->_tags[$tag['tag_id']] = $tag;
            }
        }

        if (!empty($this->_tags)) {
            $autoTagOptions = array('onceOnly' => Tinhte_XenTag_Option::get('autoTagOnceOnly'));

            return Tinhte_XenTag_Integration::autoTag($this->_html, $this->_tags, $autoTagOptions);
        } else {
            return $this->_html;
        }
    }

    /**
     * @return Tinhte_XenTag_XenForo_Model_Tag
     */
    protected function _getTagModel()
    {
        if (self::$_tagModel === false) {
            self::$_tagModel = XenForo_Model::create('XenForo_Model_Tag');
        }

        return self::$_tagModel;
    }

}

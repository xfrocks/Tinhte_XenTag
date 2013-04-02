<?php

abstract class Tinhte_XenTag_ContentWrapper_Abstract {
	protected $_html = '';
	protected $_tags = array();
	protected $_useGlobalTags = false;
	
	static protected $_tagModel = false;
	
	public function __toString() {
		return strval($this->render());
	}
	
	public function render() {
		if ($this->_useGlobalTags) {
			$globalTags = $this->_getTagModel()->getTagTextsFromCache();
			
			$tags = array();
			foreach ($this->_tags as $tag) {
				$tags[Tinhte_XenTag_Helper::getNormalizedTagText($tag)] = $tag;
			}
			foreach ($globalTags as $globalTag) {
				$tags[Tinhte_XenTag_Helper::getNormalizedTagText($globalTag)] = $globalTag;
			}
			
			$this->_tags = $tags;
		}
		
		if (!empty($this->_tags)) {
			$autoTagOptions = array(
				'onceOnly' => Tinhte_XenTag_Option::get('autoTagOnceOnly'),
			);
			
			return Tinhte_XenTag_Integration::autoTag(
				$this->_html,
				$this->_tags,
				$autoTagOptions
			);
		} else {
			return $this->_html;
		}
	}
	
	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel() {
		if (self::$_tagModel === false) {
			self::$_tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');
		}
		
		return self::$_tagModel;
	}
}
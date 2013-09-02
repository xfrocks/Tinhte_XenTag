<?php

abstract class Tinhte_XenTag_ContentWrapper_Abstract
{
	protected $_html = '';
	protected $_tagOrTexts = array();
	protected $_useGlobalTags = false;

	static protected $_tagModel = false;

	public function __toString()
	{
		return strval($this->render());
	}

	public function render()
	{
		if ($this->_useGlobalTags)
		{
			$globalTagsOrTexts = $this->_getTagModel()->getTagsOrTextsFromCache();

			$tagsOrTexts = array();
			foreach ($this->_tagsOrTexts as $tagOrText)
			{
				$tagText = Tinhte_XenTag_Helper::getTextFromTagOrText($tagOrText);
				$tagsOrTexts[Tinhte_XenTag_Helper::getNormalizedTagText($tagText)] = $tagOrText;
			}
			foreach ($globalTagsOrTexts as $globalTagOrText)
			{
				$globalTagText = Tinhte_XenTag_Helper::getTextFromTagOrText($globalTagOrText);
				$tagsOrTexts[Tinhte_XenTag_Helper::getNormalizedTagText($globalTagText)] = $globalTagOrText;
			}

			$this->_tagsOrTexts = $tagsOrTexts;
		}

		if (!empty($this->_tagsOrTexts))
		{
			$autoTagOptions = array('onceOnly' => Tinhte_XenTag_Option::get('autoTagOnceOnly'));

			return Tinhte_XenTag_Integration::autoTag($this->_html, $this->_tagsOrTexts, $autoTagOptions);
		}
		else
		{
			return $this->_html;
		}
	}

	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel()
	{
		if (self::$_tagModel === false)
		{
			self::$_tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');
		}

		return self::$_tagModel;
	}

}

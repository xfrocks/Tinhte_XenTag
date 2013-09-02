<?php

abstract class Tinhte_XenTag_ContentWrapper_Abstract
{
	protected $_html = '';
	protected $_tagTexts = array();
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
			$globalTagTexts = $this->_getTagModel()->getTagTextsFromCache();

			$tagTexts = array();
			foreach ($this->_tagTexts as $tagText)
			{
				$tagTexts[Tinhte_XenTag_Helper::getNormalizedTagText($tagText)] = $tagText;
			}
			foreach ($globalTagTexts as $globalTagText)
			{
				$tagTexts[Tinhte_XenTag_Helper::getNormalizedTagText($globalTagText)] = $globalTagText;
			}

			$this->_tagTexts = $tagTexts;
		}

		if (!empty($this->_tagTexts))
		{
			$autoTagOptions = array('onceOnly' => Tinhte_XenTag_Option::get('autoTagOnceOnly'));

			return Tinhte_XenTag_Integration::autoTag($this->_html, $this->_tagTexts, $autoTagOptions);
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

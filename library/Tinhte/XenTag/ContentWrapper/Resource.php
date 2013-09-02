<?php

class Tinhte_XenTag_ContentWrapper_Resource extends Tinhte_XenTag_ContentWrapper_Abstract
{

	protected function __construct($html, array &$update, array &$resource)
	{
		$this->_html = $html;

		$mode = Tinhte_XenTag_Option::get('autoTagResource');

		switch ($mode)
		{
			case Tinhte_XenTag_Option::AUTO_TAG_RESOURCE_TAGS:
				if (!empty($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]))
				{
					$this->_tagsOrTexts = Tinhte_XenTag_Helper::unserialize($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]);
				}
				break;
			case Tinhte_XenTag_Option::AUTO_TAG_MODE_ALL_TAGS:
				if (!empty($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]))
				{
					$this->_tagsOrTexts = Tinhte_XenTag_Helper::unserialize($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]);
				}
				$this->_useGlobalTags = true;
				break;
		}
	}

	public static function wrap(array &$update, array &$resource)
	{
		if (isset($update['messageHtml']))
		{
			$update['messageHtml'] = new Tinhte_XenTag_ContentWrapper_Resource($update['messageHtml'], $update, $resource);
		}
	}

}

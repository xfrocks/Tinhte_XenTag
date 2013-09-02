<?php

class Tinhte_XenTag_CacheRebuilder_Tag extends XenForo_CacheRebuilder_Abstract
{
	public function getRebuildMessage()
	{
		return new XenForo_Phrase('tinhte_xentag_tags');
	}

	public function showExitLink()
	{
		return true;
	}

	public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '')
	{
		$options['batch'] = max(1, isset($options['batch']) ? $options['batch'] : 100);

		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');

		$tags = $tagModel->getAllTag(array(), array(
			'limit' => $options['batch'],
			'offset' => $position
		));
		if (empty($tags))
		{
			if (class_exists('WidgetFramework_Core'))
			{
				WidgetFramework_Core::clearCachedWidgetByClass('Tinhte_XenTag_WidgetRenderer_Cloud');
			}

			return true;
		}

		foreach ($tags AS $tag)
		{
			$position = $tag['tag_id'];

			$tagModel->updateTag($tag['tag_id']);
		}

		$detailedMessage = XenForo_Locale::numberFormat($position);

		return $position;
	}

}

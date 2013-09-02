<?php

class Tinhte_XenTag_ContentWrapper_Post extends Tinhte_XenTag_ContentWrapper_Abstract
{

	protected function __construct($html, array &$post, array &$thread)
	{
		$this->_html = $html;

		$mode = Tinhte_XenTag_Option::get('autoTagMode');

		switch ($mode)
		{
			case Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS:
				if (!empty($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]))
				{
					$this->_tagsOrTexts = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
				}
				break;
			case Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY:
				if ($post['position'] == 0 AND !empty($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]))
				{
					$this->_tagsOrTexts = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
				}
				break;
			case Tinhte_XenTag_Option::AUTO_TAG_MODE_ALL_TAGS:
				if (!empty($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]))
				{
					$this->_tagsOrTexts = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
				}
				$this->_useGlobalTags = true;
				break;
		}
	}

	public static function wrap(array &$params)
	{
		if (!empty($params['thread']))
		{
			if (!empty($params['posts']))
			{
				foreach ($params['posts'] as &$post)
				{
					if (isset($post['messageHtml']))
					{
						$post['messageHtml'] = new Tinhte_XenTag_ContentWrapper_Post($post['messageHtml'], $post, $params['thread']);
					}
				}
			}

			if (!empty($params['post']))
			{
				if (isset($params['post']['messageHtml']))
				{
					$params['post']['messageHtml'] = new Tinhte_XenTag_ContentWrapper_Post($params['post']['messageHtml'], $params['post'], $params['thread']);
				}
			}
		}
	}

}

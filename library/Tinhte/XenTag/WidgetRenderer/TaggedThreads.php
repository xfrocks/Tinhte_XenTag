<?php

class Tinhte_XenTag_WidgetRenderer_TaggedThreads extends WidgetFramework_WidgetRenderer
{
	public function extraPrepareTitle(array $widget)
	{
		if (empty($widget['title']))
		{
			return new XenForo_Phrase('tinhte_xentag_tagged_threads');
		}

		return parent::extraPrepareTitle($widget);
	}

	protected function _getConfiguration()
	{
		return array(
			'name' => '[Tinhte] XenTag - Tagged Threads',
			'options' => array(
				'tags' => XenForo_Input::STRING,
				'limit' => XenForo_Input::UINT,
				'as_guest' => XenForo_Input::UINT,
			),
			'useCache' => true,
			'useUserCache' => true,
			'cacheSeconds' => 3600, // cache for 1 hour
		);
	}

	protected function _getOptionsTemplate()
	{
		return 'tinhte_xentag_widget_tagged_threads_options';
	}

	protected function _validateOptionValue($optionKey, &$optionValue)
	{
		if ('limit' == $optionKey)
		{
			if (empty($optionValue))
			{
				$optionValue = 5;
			}
		}

		return true;
	}

	protected function _getRenderTemplate(array $widget, $positionCode, array $params)
	{
		return 'tinhte_xentag_widget_tagged_threads';
	}

	protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template)
	{
		$threads = array();

		$tags = Tinhte_XenTag_Helper::explodeTags($widget['options']['tags']);
		$tagsText = array();

		foreach ($tags as $tag)
		{
			$tag = trim($tag);
			if (!empty($tag))
			{
				$tagsText[] = $tag;
			}
		}

		if (!empty($tagsText))
		{
			$core = WidgetFramework_Core::getInstance();

			/* @var $threadModel XenForo_Model_Thread */
			$threadModel = $core->getModelFromCache('XenForo_Model_Thread');

			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $core->getModelFromCache('Tinhte_XenTag_Model_Tag');

			$tags = $tagModel->getTagsByText($tagsText);

			$threadIds = array();
			foreach ($tags as $tag)
			{
				$latest = Tinhte_XenTag_Helper::unserialize($tag['latest_tagged_contents']);

				if (empty($latest))
				{
					// for some reason, the latest_tagged_contents field is empty
					// this is illogical because at least there is 1 tagged content (current thread)
					// so we will rebuild the tag...
					$latest = $tagModel->updateTag($tag['tag_id']);
				}

				if (!empty($latest))
				{
					foreach ($latest as $taggedContent)
					{
						if ($taggedContent['content_type'] == 'thread')
						{
							$threadIds[] = $taggedContent['content_id'];
						}
					}
				}
			}

			if (!empty($threadIds))
			{
				$threadIds = array_unique($threadIds);
				$forumIds = $this->_helperGetForumIdsFromOption(array(), $params, empty($widget['options']['as_guest']) ? false : true);
				// quick way to get all viewable forum ids

				$conditions = array(
					'node_id' => $forumIds,
					'thread_id' => $threadIds,
					'deleted' => false,
					'moderated' => false,
				);
				$fetchOptions = array(
					'limit' => $widget['options']['limit'],
					'join' => XenForo_Model_Thread::FETCH_AVATAR,
					'order' => 'post_date',
					'orderDirection' => 'desc',
				);

				$threads = $threadModel->getThreads($conditions, $fetchOptions);
			}
		}

		$template->setParam('threads', $threads);

		return $template->render();
	}

	public function useUserCache(array $widget)
	{
		if (!empty($widget['options']['as_guest']))
		{
			// using guest permission
			// there is no reason to use the user cache
			return false;
		}

		return parent::useUserCache($widget);
	}

}

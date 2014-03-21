<?php

class Tinhte_XenTag_WidgetRenderer_TrendingThreadTags extends WidgetFramework_WidgetRenderer
{
	public function extraPrepareTitle(array $widget)
	{
		if (empty($widget['title']))
		{
			return new XenForo_Phrase('tinhte_xentag_trending');
		}

		return parent::extraPrepareTitle($widget);
	}

	protected function _getConfiguration()
	{
		return array(
			'name' => '[Tinhte] XenTag - Trending Thread Tags',
			'options' => array(
				'forums' => XenForo_Input::ARRAY_SIMPLE,
				'days' => XenForo_Input::UINT,
				'created_days' => XenForo_Input::UINT,
				'limit' => XenForo_Input::UINT
			),
			'useCache' => true,
			'cacheSeconds' => 3600, // cache for 1 hour
		);
	}

	protected function _getOptionsTemplate()
	{
		return 'tinhte_xentag_widget_trending_thread_tags_options';
	}

	protected function _renderOptions(XenForo_Template_Abstract $template)
	{
		$params = $template->getParams();

		$forums = $this->_helperPrepareForumsOptionSource(empty($params['options']['forums']) ? array() : $params['options']['forums'], true);
		$template->setParam('forums', $forums);

		return parent::_renderOptions($template);
	}

	protected function _validateOptionValue($optionKey, &$optionValue)
	{
		if ('days' == $optionKey)
		{
			if (empty($optionValue))
			{
				$optionValue = Tinhte_XenTag_Option::get('trendingDays');
			}
		}
		elseif ('limit' == $optionKey)
		{
			if (empty($optionValue))
			{
				$optionValue = Tinhte_XenTag_Option::get('trendingMax');
			}
		}

		return true;
	}

	protected function _getRenderTemplate(array $widget, $positionCode, array $params)
	{
		return 'tinhte_xentag_widget_trending';
	}

	protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template)
	{
		$core = WidgetFramework_Core::getInstance();
		$tagModel = $core->getModelFromCache('Tinhte_XenTag_Model_Tag');

		if (!empty($widget['options']['days']))
		{
			$days = $widget['options']['days'];
		}
		else
		{
			$days = Tinhte_XenTag_Option::get('trendingDays');
		}
		$cutoff = XenForo_Application::$time - $days * 86400;

		$createdCutoff = 0;
		if (!empty($widget['options']['created_days']))
		{
			$createdCutoff = XenForo_Application::$time - $widget['options']['created_days'] * 86400;
		}

		if (!empty($widget['options']['limit']))
		{
			$limit = $widget['options']['limit'];
		}
		else
		{
			$limit = Tinhte_XenTag_Option::get('trendingMax');
		}

		$forumIds = $this->_helperGetForumIdsFromOption($widget['options']['forums'], $params, true);

		$db = XenForo_Application::getDb();
		$taggedCount = $db->fetchPairs('
			SELECT tagged.tag_id, count(*) AS tagged_count
			FROM `xf_tinhte_xentag_tagged_content` AS tagged
			INNER JOIN `xf_thread` AS thread
				ON (thread.thread_id = tagged.content_id)
			' . ($createdCutoff > 0 ? 'INNER JOIN `xf_tinhte_xentag_tag` AS tag ON (tag.tag_id = tagged.tag_id AND tag.created_date > ' . $createdCutoff . ')' : '') . '
			WHERE tagged.content_type = "thread" AND tagged.tagged_date > ?
				' . (!empty($forumIds) ? 'AND thread.node_id IN (' . $db->quote($forumIds) . ')' : '') . '
			GROUP BY tagged.tag_id
			ORDER BY tagged_count DESC
			LIMIT ?;
		', array(
			$cutoff,
			$limit
		));

		$tags = array();
		if (!empty($taggedCount))
		{
			$tagsDb = $tagModel->getAllTag(array('tag_id' => array_keys($taggedCount)));
			foreach ($taggedCount as $tagId => $count)
			{
				if (isset($tagsDb[$tagId]))
				{
					$tags[$tagId] = $tagsDb[$tagId];
					$tags[$tagId]['tagged_count'] = $count;
				}
			}
		}

		$tagModel->calculateCloudLevel($tags);

		$template->setParam('tags', $tags);

		return $template->render();
	}

	protected function _getCacheId(array $widget, $positionCode, array $params, array $suffix = array())
	{
		if ($this->_helperDetectSpecialForums($widget['options']['forums']))
		{
			// we have to use special cache id when special forum ids are used
			if (isset($params['forum']))
			{
				$suffix[] = 'f' . $params['forum']['node_id'];
			}
		}

		return parent::_getCacheId($widget, $positionCode, $params, $suffix);
	}

}

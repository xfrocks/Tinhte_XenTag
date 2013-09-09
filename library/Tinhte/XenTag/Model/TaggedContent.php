<?php
class Tinhte_XenTag_Model_TaggedContent extends XenForo_Model
{

	public function deleteTaggedContentsByTagId(array $tag, array $taggeds)
	{
		$this->_getDb()->query("
			DELETE FROM `xf_tinhte_xentag_tagged_content`
			WHERE tag_id = ?
		", array($tag['tag_id']));

		$this->_deleteTaggedContentsThreads($tag, $taggeds);
		$this->_deleteTaggedContentsPages($tag, $taggeds);
		$this->_deleteTaggedContentsForums($tag, $taggeds);
		$this->_deleteTaggedContentsResources($tag, $taggeds);
	}

	protected function _deleteTaggedContentsThreads(array $tag, array $taggeds)
	{
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		$threadModel = $this->getModelFromCache('XenForo_Model_Thread');

		$threadIds = array();
		foreach ($taggeds as $tagged)
		{
			if ($tagged['content_type'] == 'thread')
			{
				$threadIds[] = $tagged['content_id'];
			}
		}

		$threads = $threadModel->getThreadsByIds($threadIds);

		foreach ($threads as $thread)
		{
			$tagTexts = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
			$filteredTagTexts = array();

			foreach ($tagTexts as $tagText)
			{
				if ($tagModel->isTagIdenticalWithText($tag, $tagText))
				{
					// drop this tag
				}
				else
				{
					$filteredTagTexts[] = $tagText;
				}
			}

			if (count($tagTexts) != count($filteredTagTexts))
			{
				/* @var $dw XenForo_DataWriter_Discussion_Thread*/
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');

				$dw->setExistingData($thread, true);
				// save queries
				$dw->Tinhte_XenTag_setTags($filteredTagTexts);
				$dw->setExtraData(Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread::DATA_SKIP_UPDATE_TAGS_IN_DATABASE, true);
				$dw->save();
			}
		}
	}

	protected function _deleteTaggedContentsPages(array $tag, array $taggeds)
	{
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		$pageModel = $this->getModelFromCache('XenForo_Model_Page');

		$nodeIds = array();
		foreach ($taggeds as $tagged)
		{
			if ($tagged['content_type'] == Tinhte_XenTag_Constants::CONTENT_TYPE_PAGE)
			{
				$nodeIds[] = $tagged['content_id'];
			}
		}

		$pages = $pageModel->Tinhte_XenTag_getPagesByIds($nodeIds);

		foreach ($pages as $page)
		{
			$tagTexts = Tinhte_XenTag_Helper::unserialize($page[Tinhte_XenTag_Constants::FIELD_PAGE_TAGS]);
			$filteredTagTexts = array();

			foreach ($tagTexts as $tagText)
			{
				if ($tagModel->isTagIdenticalWithText($tag, $tagText))
				{
					// drop this tag
				}
				else
				{
					$filteredTagTexts[] = $tagText;
				}
			}

			if (count($tagTexts) != count($filteredTagTexts))
			{
				/* @var $dw XenForo_DataWriter_Page */
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Page');

				$dw->setExistingData($page, true);
				// save queries
				$dw->Tinhte_XenTag_setTags($filteredTagTexts);
				$dw->setExtraData(Tinhte_XenTag_XenForo_DataWriter_Page::DATA_SKIP_UPDATE_TAGS_IN_DATABASE, true);
				$dw->save();
			}
		}
	}

	protected function _deleteTaggedContentsForums(array $tag, array $taggeds)
	{
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		$forumModel = $this->getModelFromCache('XenForo_Model_Forum');

		$nodeIds = array();
		foreach ($taggeds as $tagged)
		{
			if ($tagged['content_type'] == Tinhte_XenTag_Constants::CONTENT_TYPE_FORUM)
			{
				$nodeIds[] = $tagged['content_id'];
			}
		}

		$forums = $forumModel->Tinhte_XenTag_getForumsByIds($nodeIds);

		foreach ($forums as $forum)
		{
			$tagTexts = Tinhte_XenTag_Helper::unserialize($forum[Tinhte_XenTag_Constants::FIELD_FORUM_TAGS]);
			$filteredTagTexts = array();

			foreach ($tagTexts as $tagText)
			{
				if ($tagModel->isTagIdenticalWithText($tag, $tagText))
				{
					// drop this tag
				}
				else
				{
					$filteredTagTexts[] = $tagText;
				}
			}

			if (count($tagTexts) != count($filteredTagTexts))
			{
				/* @var $dw XenForo_DataWriter_Forum */
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum');

				$dw->setExistingData($forum, true);
				// save queries
				$dw->Tinhte_XenTag_setTags($filteredTagTexts);
				$dw->setExtraData(Tinhte_XenTag_XenForo_DataWriter_Forum::DATA_SKIP_UPDATE_TAGS_IN_DATABASE, true);
				$dw->save();
			}
		}
	}

	protected function _deleteTaggedContentsResources(array $tag, array $taggeds)
	{
		if (!Tinhte_XenTag_Option::xfrmFound())
		{
			// XFRM is not installed/enabled
			return false;
		}

		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		$resourceModel = $this->getModelFromCache('XenResource_Model_Resource');

		$resourceIds = array();
		foreach ($taggeds as $tagged)
		{
			if ($tagged['content_type'] == Tinhte_XenTag_Constants::CONTENT_TYPE_RESOURCE)
			{
				$resourceIds[] = $tagged['content_id'];
			}
		}

		$resources = $resourceModel->getResourcesByIds($resourceIds);

		foreach ($resources as $resource)
		{
			$tagTexts = Tinhte_XenTag_Helper::unserialize($resource[Tinhte_XenTag_Constants::FIELD_RESOURCE_TAGS]);
			$filteredTagTexts = array();

			foreach ($tagTexts as $tagText)
			{
				if ($tagModel->isTagIdenticalWithText($tag, $tagText))
				{
					// drop this tag
				}
				else
				{
					$filteredTagTexts[] = $tagText;
				}
			}

			if (count($tagTexts) != count($filteredTagTexts))
			{
				/* @var $dw XenResource_DataWriter_Resource */
				$dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');

				$dw->setExistingData($resource, true);
				// save queries
				$dw->Tinhte_XenTag_setTags($filteredTagTexts);
				$dw->setExtraData(Tinhte_XenTag_XenForo_DataWriter_Forum::DATA_SKIP_UPDATE_TAGS_IN_DATABASE, true);
				$dw->save();
			}
		}
	}

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$data = $this->getAllTaggedContent($conditions, $fetchOptions);
		$list = array();

		foreach ($data as $id => $row)
		{
			$list[$id] = $row['n/a'];
		}

		return $list;
	}

	public function getTaggedContentById($id, array $fetchOptions = array())
	{
		$data = $this->getAllTaggedContent(array('n/a' => $id), $fetchOptions);

		return reset($data);
	}

	public function getAllTaggedContent(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareTaggedContentConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareTaggedContentOrderOptions($fetchOptions);
		$joinOptions = $this->prepareTaggedContentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$all = $this->fetchAllKeyed($this->limitQueryResults("
				SELECT tagged_content.*
					$joinOptions[selectFields]
				FROM `xf_tinhte_xentag_tagged_content` AS tagged_content
					$joinOptions[joinTables]
				WHERE $whereConditions
					$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'n/a');

		return $all;
	}

	public function countAllTaggedContent(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareTaggedContentConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareTaggedContentOrderOptions($fetchOptions);
		$joinOptions = $this->prepareTaggedContentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_tinhte_xentag_tagged_content` AS tagged_content
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareTaggedContentConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		foreach (array('tag_id', 'content_type', 'content_id', 'tagged_user_id', 'tagged_date') as $intField)
		{
			if (!isset($conditions[$intField]))
				continue;

			if (is_array($conditions[$intField]))
			{
				$sqlConditions[] = "tagged_content.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			}
			else
			{
				$sqlConditions[] = "tagged_content.$intField = " . $db->quote($conditions[$intField]);
			}
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareTaggedContentFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareTaggedContentOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array('tagged_date' => 'tagged_content.tagged_date');

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}

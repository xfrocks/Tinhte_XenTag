<?php

class Tinhte_XenTag_ReportHandler_Tag extends XenForo_ReportHandler_Abstract
{

	public function getReportDetailsFromContent(array $content)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');

		if (!empty($content['tag_id']))
		{
			$tag = $tagModel->getTagById($content['tag_id']);
		}
		elseif (!empty($content['tag_text']))
		{
			$tag = $tagModel->getTagByText($content['tag_text']);
		}

		if (!$tag)
		{
			return array(
				false,
				false,
				false
			);
		}

		return array(
			$tag['tag_id'],
			$tag['created_user_id'],
			$tag
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');

		foreach ($reports AS $reportId => $report)
		{
			$tag = unserialize($report['content_info']);
			if (!$tagModel->canEditTag($tag))
			{
				unset($reports[$reportId]);
			}
		}

		return $reports;
	}

	public function getContentTitle(array $report, array $contentInfo)
	{
		return $contentInfo['tag_text'];
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('tags', $contentInfo);
	}

}

<?php

class Tinhte_XenTag_XenForo_Search_Indexer extends XenForo_Search_Indexer
{
	protected $_extraMetadata = array();

	public function __construct(XenForo_Search_Indexer $otherIndexer, array $extraMetadata)
	{
		$this->_sourceHandler = $otherIndexer->_sourceHandler;
		$this->_extraMetadata = $extraMetadata;
	}

	public function insertIntoIndex($contentType, $contentId, $title, $message, $itemDate, $userId, $discussionId = 0, array $metadata = array())
	{
		$metadata = XenForo_Application::mapMerge($metadata, $this->_extraMetadata);

		return parent::insertIntoIndex($contentType, $contentId, $title, $message, $itemDate, $userId, $discussionId, $metadata);
	}

}

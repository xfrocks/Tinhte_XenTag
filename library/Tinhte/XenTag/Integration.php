<?php

class Tinhte_XenTag_Integration {
	public static function updateTags($contentType, $contentId, $contentUserId, array $tags, XenForo_DataWriter $dw) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $dw->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		/* @var $taggedModel Tinhte_XenTag_Model_TaggedContent */
		$taggedModel = $dw->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
		
		if ($dw->isInsert()) {
			// saves 1 query
			$existingTags = array();
		} else {
			$existingTags = $tagModel->getTagsOfContent($contentType, $contentId);
		}
		
		$newTags = array();
		$removedTags = array();
		$tagModel->lookForNewAndRemovedTags($existingTags, $tags, $newTags, $removedTags);
		$canCreateNew = XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_CREATE_NEW);
		
		if (!empty($newTags)) {
			$newButExistingTags = $tagModel->getTagsByText($newTags);
			
			foreach ($newTags as $newTag) {
				$newTagData = $tagModel->getTagFromArrayByText($newButExistingTags, $newTag);
				
				if (empty($newTagData)) {
					if (!$canCreateNew) {
						throw new XenForo_Exception(new XenForo_Phrase('tinhte_xentag_you_can_not_create_new_tag'), true);
					}
					/* @var $dwTag Tinhte_XenTag_DataWriter_Tag */
					$dwTag = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
					$dwTag->set('tag_text', $newTag);
					$dwTag->set('created_user_id', $contentUserId);
					$dwTag->save();
					
					$newTagData = $dwTag->getMergedData();
				}
				
				/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
				$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
				$dwTagged->set('tag_id', $newTagData['tag_id']);
				$dwTagged->set('content_type', $contentType);
				$dwTagged->set('content_id', $contentId);
				$dwTagged->set('tagged_user_id', $contentUserId);
				$dwTagged->save();
			}
		}
		
		if (!empty($removedTags)) {
			foreach ($removedTags as $removedTag) {
				$removedTagData = $tagModel->getTagFromArrayByText($existingTags, $removedTag);
				
				if (!empty($removedTagData)) {
					/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
					$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
					$data = array(
						'tag_id' => $removedTagData['tag_id'],
						'content_type' => $contentType,
						'content_id' => $contentId,
					);
					$dwTagged->setExistingData($data, true);
					$dwTagged->delete();
				}
			}
		}
		
		return count($existingTags) + count($newTags) - count($removedTags);
	}
	
	public static function deleteTags($contentType, $contentId, XenForo_DataWriter $dw) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $dw->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		$existingTags = $tagModel->getTagsOfContent($contentType, $contentId);
		
		foreach ($existingTags as $tag) {
			/* @var $dwTag Tinhte_XenTag_DataWriter_Tagged */
			$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
			$data = array(
				'tag_id' => $tag['tag_id'],
				'content_type' => 'thread',
				'content_id' => $this->get('thread_id'),
			);
			$dwTagged->setExistingData($data, true);
			$dwTagged->delete();
		}
		
		return count($existingTags);
	}
	
	public static function insertIntoIndex(array $tags, XenForo_Search_DataHandler_Abstract $sdh) {
		// call this to make sure Tinhte_XenTag_XenForo_Search_SourceHandler is available
		// it will be loaded dynamically via search_source_create event listener
		XenForo_Search_SourceHandler_Abstract::getDefaultSourceHandler();
		
		if (class_exists('Tinhte_XenTag_XenForo_Search_SourceHandler')) {
			// we have to check for the  of Tinhte_XenTag_XenForo_Search_SourceHandler
			// because it's a common mistake when webmaster install this add-on
			// with XenForo Enhanced Search installed without doing the manual edit
			Tinhte_XenTag_XenForo_Search_SourceHandler::setExtraMetaData(array(
				Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS => Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($tags), 
			));
		}
	}
}
<?php

class Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Discussion_Thread {
	
	public function Tinhte_XenTag_setTags(array $tagsRaw) {
		$tags = array();
		
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		foreach ($tagsRaw as $tag) {
			$tag = $tagModel->validateTag($tag);
			if (!empty($tag) AND !in_array($tag, $tags)) {
				$tags[] = $tag;
			}
		}
		
		asort($tags); // index association is maintained 
		
		$this->set(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS, $tags);
	}
	
	protected function _getFields() {
		$fields = parent::_getFields();
		
		$fields['xf_thread'][Tinhte_XenTag_Constants::FIELD_THREAD_TAGS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => ''
		);
		
		return $fields;
	}
	
	protected function _discussionPreSave() {
		// checks for our controller and call it first
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD])) {
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD]->Tinhte_XenTag_actionAddThread($this);
		} elseif (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_SAVE])) {
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_THREAD_SAVE]->Tinhte_XenTag_actionSave($this);
		}
		
		return parent::_discussionPreSave();
	}
	
	protected function _discussionPostSave(array $messages) {
		if ($this->isInsert()) {
			$tags = Tinhte_XenTag_Helper::unserialize($this->get(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS));
			$this->_Tinhte_XenTag_updateTagsInDatabase($tags);
		}
		
		if ($this->isUpdate() && $this->isChanged(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS)) {
			$tags = Tinhte_XenTag_Helper::unserialize($this->get(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS));
			$this->_Tinhte_XenTag_updateTagsInDatabase($tags);
			
			// below lines of code are copied from XenForo_DataWriter_Discussion_Thread::_discussionPostSave
			$indexer = new XenForo_Search_Indexer();

			$messageHandler = $this->_messageDefinition->getSearchDataHandler();
			if ($messageHandler) {
				$thread = $this->getMergedData();
				$fullMessages = $this->_getMessagesInDiscussionSimple(true); // re-get with message contents
				foreach ($fullMessages AS $key => $message) {
					$messageHandler->insertIntoIndex($indexer, $message, $thread);
					unset($fullMessages[$key]);
				}
			}
		}
		
		return parent::_discussionPostSave($messages);
	}
	
	protected function _discussionPostDelete(array $messages) {
		$existingTags = $tagModel->getTagsOfContent('thread', $this->get('thread_id'));
		
		foreach ($existingTags as $tag) {
			/* @var $dwTag Tinhte_XenTag_DataWriter_Tagged */
			$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tagged');
			$data = array(
				'tag_id' => $tag['tag_id'],
				'content_type' => 'thread',
				'content_id' => $this->get('thread_id'),
			);
			$dwTagged->setExistingData($data, true);
			$dwTagged->delete();
		}
		
		return _discussionPostDelete($messages);
	}
	
	protected function _needsSearchIndexUpdate() {
		return (parent::_needsSearchIndexUpdate() || $this->isChanged(Tinhte_XenTag_Constants::FIELD_THREAD_TAGS));
	}
	
	protected function _Tinhte_XenTag_updateTagsInDatabase(array $tags) {
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
		
		/* @var $taggedModel Tinhte_XenTag_Model_TaggedContent */
		$taggedModel = $this->getModelFromCache('Tinhte_XenTag_Model_TaggedContent');
		
		if ($this->isInsert()) {
			// saves 1 query
			$existingTags = array();
		} else {
			$existingTags = $tagModel->getTagsOfContent('thread', $this->get('thread_id'));
		}
		
		$newTags = array();
		$removedTags = array();
		$tagModel->lookForNewAndRemovedTags($existingTags, $tags, $newTags, $removedTags);
		
		if (!empty($newTags)) {
			$newButExistingTags = $tagModel->getTagsByText($newTags);
			
			foreach ($newTags as $newTag) {
				$newTagData = $tagModel->getTagFromArrayByText($newButExistingTags, $newTag);
				
				if (empty($newTagData)) {
					/* @var $dwTag Tinhte_XenTag_DataWriter_Tag */
					$dwTag = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
					$dwTag->set('tag_text', $newTag);
					$dwTag->set('created_user_id', $this->get('user_id'));
					$dwTag->save();
					
					$newTagData = $dwTag->getMergedData();
				}
				
				/* @var $dwTag Tinhte_XenTag_DataWriter_TaggedContent */
				$dwTagged = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_TaggedContent');
				$dwTagged->set('tag_id', $newTagData['tag_id']);
				$dwTagged->set('content_type', 'thread');
				$dwTagged->set('content_id', $this->get('thread_id'));
				$dwTagged->set('tagged_user_id', $this->get('user_id'));
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
						'content_type' => 'thread',
						'content_id' => $this->get('thread_id'),
					);
					$dwTagged->setExistingData($data, true);
					$dwTagged->delete();
				}
			}
		}
	}
	
}
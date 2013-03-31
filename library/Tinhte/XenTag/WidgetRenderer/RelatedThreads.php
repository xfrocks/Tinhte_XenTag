<?php

class Tinhte_XenTag_WidgetRenderer_RelatedThreads extends WidgetFramework_WidgetRenderer {
	protected function _getConfiguration() {
		return array(
			'name' => '[Tinhte] XenTag - Related Threads',
			'options' => array(
				'limit' => XenForo_Input::UINT,
				'as_guest' => XenForo_Input::UINT,
				'use_live_cache' => XenForo_Input::UINT,
			),
			'useCache' => true,
			'useUserCache' => true,
			'cacheSeconds' => 300, // cache for 5 minute
		);
	}
	
	protected function _getOptionsTemplate() {
		return 'tinhte_xentag_widget_related_threads_options';
	}
	
	protected function _validateOptionValue($optionKey, &$optionValue) {
		if ('limit' == $optionKey) {
			if (empty($optionValue)) $optionValue = 5;
		}
		
		return true;
	}
	
	protected function _getRenderTemplate(array $widget, $positionCode, array $params) {
		return 'tinhte_xentag_widget_related_threads';
	}
	
	protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template) {
		$threads = array();
		
		if (isset($params['thread'])) {
			// $thread is found in the template params
			// assuming we are in the correct template (thread_view or something similar)
			// start working
			$tagsText = Tinhte_XenTag_Helper::unserialize($params['thread'][Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
			
			if (!empty($tagsText)) {
				$core = WidgetFramework_Core::getInstance();
				
				/* @var $threadModel XenForo_Model_Thread */
				$threadModel = $core->getModelFromCache('XenForo_Model_Thread');
				
				/* @var $tagModel Tinhte_XenTag_Model_Tag */
				$tagModel = $core->getModelFromCache('Tinhte_XenTag_Model_Tag');
				
				$tags = $tagModel->getTagsByText($tagsText);
				
				$threadIds = array();
				foreach ($tags as $tag) {
					$latest = Tinhte_XenTag_Helper::unserialize($tag['latest_tagged_contents']);
					
					if (empty($latest)) {
						// for some reason, the latest_tagged_contents field is empty
						// this is illogical because at least there is 1 tagged content (current thread)
						// so we will rebuild the tag...
						$latest = $tagModel->updateTag($tag['tag_id']);
					}
					
					if (!empty($latest)) {
						foreach ($latest as $taggedContent) {
							if ($taggedContent['content_type'] == 'thread') {
								// ignore current thread id, obviously!!!
								if ($taggedContent['content_id'] == $params['thread']['thread_id']) continue;
								
								$threadIds[] = $taggedContent['content_id'];
							}
						}
					}
				}
				
				if (!empty($threadIds)) {
					$threadIds = array_unique($threadIds);
					$forumIds = $this->_helperGetForumIdsFromOption(
						array(),
						$params,
						empty($widget['options']['as_guest']) ? false : true
					); // quick way to get all viewable forum ids
					
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
		}
		
		$template->setParam('threads', $threads);
		
		return $template->render();
	}
	
	public function useUserCache(array $widget) {
		if (!empty($widget['options']['as_guest'])) {
			// using guest permission
			// there is no reason to use the user cache
			return false;
		}
		
		return parent::useUserCache($widget);
	}
	
	public function useLiveCache(array $widget) {
		if (!empty($widget['options']['use_live_cache'])) {
			return true;
		}

		return parent::useLiveCache($widget);
	}
	
	protected function _getCacheId(array $widget, $positionCode, array $params, array $suffix = array()) {
		if (isset($params['thread'])) {
			$suffix[] = 't' . $params['thread']['thread_id'];
		}
		
		return parent::_getCacheId($widget, $positionCode, $params, $suffix);
	}
}
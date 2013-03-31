<?php

class Tinhte_XenTag_PostMessageWrapper {
	
	public static function wrap(array &$params) {
		if (!empty($params['thread'])) {
			if (!empty($params['posts'])) {
				foreach ($params['posts'] as &$post) {
					if (isset($post['messageHtml'])) {
						$post['messageHtml'] = new Tinhte_XenTag_PostMessageWrapper($post['messageHtml'], $post, $params['thread']);
					}
				}
			}
			
			if (!empty($params['post'])) {
				if (isset($params['post']['messageHtml'])) {
					$params['post']['messageHtml'] = new Tinhte_XenTag_PostMessageWrapper($params['post']['messageHtml'], $params['post'], $params['thread']);
				}
			}
		}
	}
	
	protected $_html;
	protected $_post;
	protected $_thread;
	
	protected function __construct($html, array &$post, array &$thread) {
		$this->_html = $html;
		$this->_post = &$post;
		$this->_thread = &$thread;
	}
	
	public function __toString() {
		return $this->render();
	}
	
	public function render() {
		$mode = Tinhte_XenTag_Option::get('autoTagMode');
		$onceOnly = Tinhte_XenTag_Option::get('autoTagOnceOnly');
		$tags = false;
		
		if ($mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_DISALBED) {
			// auto tagging is disabled, no thing to do here
			$tags = false;
		} elseif ($mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS
			OR $mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY) {
			// get the tags of current thread
			if (!empty($this->_thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS])) {
				$tags = Tinhte_XenTag_Helper::unserialize($this->_thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
			}
		} else {
			// get all the tags
			// the thread tags are merged because sometimes the global tags are not the full list
			$threadTags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);
			$globalTags = $this->_getTagModel()->getTagTextsForAutoTag();
			$tags = array_unique(array_merge($threadTags, $globalTags));
		}
		
		if ($mode == Tinhte_XenTag_Option::AUTO_TAG_MODE_THREAD_TAGS_FIRST_POST_ONLY
			AND $post['position'] != 0) {
			// the mode targets first post only
			// but this is not the first one, so reset the $tags array
			$tags = false;
		}
		
		if (!empty($tags)) {
			return Tinhte_XenTag_Integration::autoTag(
				$this->_html,
				$tags,
					array(
					'onceOnly' => Tinhte_XenTag_Option::get('autoTagOnceOnly'),
				)
			);
		} else {
			return strval($this->_html);
		}
	}
	
	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel() {
		static $model = false;
		
		if ($model === false) {
			$model = XenForo_Model::create('Tinhte_XenTag_Model_Tag');
		}
		
		return $model;
	}
}
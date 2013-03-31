<?php

class Tinhte_XenTag_ViewPublic_Tag_View extends XenForo_ViewPublic_Base {
	
	public function renderRss() {
		// below lines of code are copied from XenForo_ViewPublic_Forum_View::renderRss
		$tag = $this->_params['tag'];

		$buggyXmlNamespace = (defined('LIBXML_DOTTED_VERSION') && LIBXML_DOTTED_VERSION == '2.6.24');

		$feed = new Zend_Feed_Writer_Feed();
		$feed->setEncoding('utf-8');
		$feed->setTitle($tag['tag_text']);
		$feed->setDescription('' . new XenForo_Phrase('tinhte_xentag_all_threads_tagged_x', array(
			'board_title' => XenForo_Application::get('options')->get('boardTitle'),
			'tag_text' => $tag['tag_text'] 
		)));
		
		$feed->setLink(XenForo_Link::buildPublicLink('canonical:' . Tinhte_XenTag_Option::get('routePrefix'), $tag));
		if (!$buggyXmlNamespace) {
			$feed->setFeedLink(XenForo_Link::buildPublicLink('canonical:' . Tinhte_XenTag_Option::get('routePrefix') . '.rss', $tag), 'rss');
		}
		$feed->setDateModified(XenForo_Application::$time);
		$feed->setLastBuildDate(XenForo_Application::$time);
		if (XenForo_Application::get('options')->boardTitle) {
			$feed->setGenerator(XenForo_Application::get('options')->boardTitle);
		}

		foreach ($this->_params['threads'] AS $thread) {
			$entry = $feed->createEntry();
			$entry->setTitle($thread['title']);
			$entry->setLink(XenForo_Link::buildPublicLink('canonical:threads', $thread));
			$entry->setDateCreated(new Zend_Date($thread['post_date'], Zend_Date::TIMESTAMP));
			$entry->setDateModified(new Zend_Date($thread['last_post_date'], Zend_Date::TIMESTAMP));
			if (!$buggyXmlNamespace) {
				$entry->addAuthor(array(
					'name' => $thread['username'],
					'uri' => XenForo_Link::buildPublicLink('canonical:members', $thread)
				));
				if ($thread['reply_count']) {
					$entry->setCommentCount($thread['reply_count']);
				}
			}

			$feed->addEntry($entry);
		}

		return $feed->export('rss');
	}
	
}
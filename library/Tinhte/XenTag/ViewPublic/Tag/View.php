<?php

class Tinhte_XenTag_ViewPublic_Tag_View extends XenForo_ViewPublic_Base
{

	public function renderHtml()
	{
		$this->_params['results'] = XenForo_ViewPublic_Helper_Search::renderSearchResults($this, $this->_params['results'], $this->_params['search']);
	}

	public function renderRss()
	{
		// below lines of code are copied from XenForo_ViewPublic_Forum_View::renderRss
		$tag = $this->_params['tag'];

		$buggyXmlNamespace = (defined('LIBXML_DOTTED_VERSION') && LIBXML_DOTTED_VERSION == '2.6.24');

		$feed = new Zend_Feed_Writer_Feed();
		$feed->setEncoding('utf-8');
		$feed->setTitle($tag['tag_text']);
		$feed->setDescription('' . new XenForo_Phrase('tinhte_xentag_all_contents_tagged_x', array(
			'board_title' => XenForo_Application::get('options')->get('boardTitle'),
			'tag_text' => $tag['tag_text']
		)));

		$feed->setLink(XenForo_Link::buildPublicLink('canonical:tags', $tag));
		if (!$buggyXmlNamespace)
		{
			$feed->setFeedLink(XenForo_Link::buildPublicLink('canonical:tags.rss', $tag), 'rss');
		}
		$feed->setDateModified(XenForo_Application::$time);
		$feed->setLastBuildDate(XenForo_Application::$time);
		if (XenForo_Application::get('options')->boardTitle)
		{
			$feed->setGenerator(XenForo_Application::get('options')->boardTitle);
		}

		foreach ($this->_params['results']['results'] AS $result)
		{
			if ($result[XenForo_Model_Search::CONTENT_TYPE] == 'thread')
			{
				$thread = $result['content'];

				$entry = $feed->createEntry();
				$entry->setTitle($thread['title']);
				$entry->setLink(XenForo_Link::buildPublicLink('canonical:threads', $thread));
				$entry->setDateCreated(new Zend_Date($thread['post_date'], Zend_Date::TIMESTAMP));
				$entry->setDateModified(new Zend_Date($thread['last_post_date'], Zend_Date::TIMESTAMP));
				if (!$buggyXmlNamespace)
				{
					$entry->addAuthor(array(
						'name' => $thread['username'],
						'uri' => XenForo_Link::buildPublicLink('canonical:members', $thread)
					));
					if ($thread['reply_count'])
					{
						$entry->setCommentCount($thread['reply_count']);
					}
				}
			}
			else
			{
				$entry = $this->_prepareRssEntry($result, $feed);
			}

			if ($entry !== false)
			{
				$feed->addEntry($entry);
			}
		}

		return $feed->export('rss');
	}

	protected function _prepareRssEntry($result, Zend_Feed_Writer_Feed $feed)
	{
		return false;
	}

}

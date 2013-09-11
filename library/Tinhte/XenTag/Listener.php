<?php

class Tinhte_XenTag_Listener
{

	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_BbCode_Formatter_Base',

			'XenForo_ControllerAdmin_Forum',
			'XenForo_ControllerAdmin_Page',

			'XenForo_ControllerPublic_Forum',
			'XenForo_ControllerPublic_Post',
			'XenForo_ControllerPublic_Search',
			'XenForo_ControllerPublic_Thread',

			'XenForo_DataWriter_Discussion_Thread',
			'XenForo_DataWriter_DiscussionMessage_Post',
			'XenForo_DataWriter_Forum',
			'XenForo_DataWriter_Page',

			'XenForo_Model_Forum',
			'XenForo_Model_Post',
			'XenForo_Model_Page',
			'XenForo_Model_Search',
			'XenForo_Model_ThreadRedirect',
			'XenForo_Model_Thread',

			'XenForo_Search_DataHandler_Post',
			'XenForo_Search_DataHandler_Thread',

			'XenForo_ViewPublic_Thread_View',

			'XenResource_ControllerPublic_Resource',
			'XenResource_DataWriter_Resource',
			'XenResource_Model_Resource',
			'XenResource_ViewPublic_Resource_Description',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'Tinhte_XenTag_' . $class;
		}
	}

	public static function load_class_importer($class, array &$extend)
	{
		static $extended = false;

		// extend all vbulletin importer
		if ($extended === false AND strpos(strtolower($class), 'vbulletin') !== false)
		{
			$extend[] = 'Tinhte_XenTag_XenForo_Importer_vBulletin';
			$extended = true;
		}
	}

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Template_Helper_Core::$helperCallbacks['tinhte_xentag_getimplodedtagsfromthread'] = array(
			'Tinhte_XenTag_Helper',
			'getImplodedTagsFromThread'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['tinhte_xentag_getimplodedtagsfrompage'] = array(
			'Tinhte_XenTag_Helper',
			'getImplodedTagsFromPage'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['tinhte_xentag_getimplodedtagsfromforum'] = array(
			'Tinhte_XenTag_Helper',
			'getImplodedTagsFromForum'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['tinhte_xentag_getimplodedtagsfromresource'] = array(
			'Tinhte_XenTag_Helper',
			'getImplodedTagsFromResource'
		);
		XenForo_Template_Helper_Core::$helperCallbacks['tinhte_xentag_getoption'] = array(
			'Tinhte_XenTag_Helper',
			'getOption'
		);

		XenForo_CacheRebuilder_Abstract::$builders['Tinhte_XenTag_Tag'] = 'Tinhte_XenTag_CacheRebuilder_Tag';
	}

	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'tools_rebuild':
			case 'forum_view':
			case 'post_edit':
			case 'search_form_post':
			case 'thread_create':
			case 'thread_edit':
			case 'thread_list_item_edit':
			case 'thread_list_item_preview':
			case 'resource_add':
			case 'resource_description':
				$template->preloadTemplate('tinhte_xentag_' . $templateName);
				break;
			case 'PAGE_CONTAINER':
				// these template will be preloaded in all pages
				// should over-use this...
				$template->preloadTemplate('tinhte_xentag_hook_message_below');
				$template->preloadTemplate('tinhte_xentag_hook_message_content');
				$template->preloadTemplate('tinhte_xentag_hook_message_notices');
				$template->preloadTemplate('tinhte_xentag_hook_post_private_controls');
				$template->preloadTemplate('tinhte_xentag_hook_post_public_controls');
				$template->preloadTemplate('tinhte_xentag_bb_code_tag_tag');
				break;
		}

		if ($templateName == 'search_results')
		{
			$template->preloadTemplate('tinhte_xentag_sidebar_search_results');
		}

		if ($templateName == 'thread_view')
		{
			$template->preloadTemplate('tinhte_xentag_hook_thread_view_pagenav_before');
			$template->preloadTemplate('tinhte_xentag_hook_thread_view_form_before');
			$template->preloadTemplate('tinhte_xentag_hook_thread_view_qr_before');
			$template->preloadTemplate('tinhte_xentag_hook_thread_view_qr_after');
		}

		if ($templateName == 'post')
		{
			Tinhte_XenTag_ContentWrapper_Post::wrap($params);
		}

		if ($templateName == 'forum_edit')
		{
			$template->preloadTemplate('tinhte_xentag_hook_admin_forum_edit_tabs');
			$template->preloadTemplate('tinhte_xentag_hook_admin_forum_edit_panes');
		}

		if ($templateName == 'page_edit')
		{
			$template->preloadTemplate('tinhte_xentag_hook_admin_page_edit_basic_informati');
		}

		if ($templateName == 'pagenode_container')
		{
			$template->preloadTemplate('tinhte_xentag_hook_pagenode_container_article');
		}

		if ($templateName == 'resource_view')
		{
			$template->preloadTemplate('tinhte_xentag_hook_resource_view_sidebar_resource_');
		}
	}

	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'tools_rebuild':
			case 'forum_view':
			case 'post_edit':
			case 'search_form_post':
			case 'thread_create':
			case 'thread_edit':
			case 'thread_list_item_edit':
			case 'thread_list_item_preview':
			case 'resource_add':
				$ourTemplate = $template->create('tinhte_xentag_' . $templateName, $template->getParams());
				$rendered = $ourTemplate->render();

				self::injectRendered($content, $rendered);
				break;
			case 'resource_description':
				$ourTemplate = $template->create('tinhte_xentag_resource_description', $template->getParams());
				$rendered = $ourTemplate->render();

				$search = '<div class="section reviews">';

				$strPos = strpos($content, $search);
				if ($strPos === false)
				{
					// no reviews
					$content .= $rendered;
				}
				else
				{
					// reviews found, we have to put the tags above them
					$content = substr_replace($content, $rendered, $strPos, 0);
				}

				break;
		}

		if ($templateName == 'search_results')
		{
			$ourTemplate = $template->create('tinhte_xentag_sidebar_search_results', $template->getParams());
			$rendered = trim($ourTemplate->render());
			if (!empty($rendered))
			{
				if (empty($containerData['sidebar']))
				{
					$containerData['sidebar'] = '';
				}
				$containerData['sidebar'] = $rendered . $containerData['sidebar'];
			}
		}
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'admin_forum_edit_tabs':
			case 'admin_forum_edit_panes':

			case 'admin_page_edit_basic_information':

			case 'message_below':
			case 'message_content':
			case 'message_notices':
			case 'post_private_controls':
			case 'post_public_controls':

			case 'thread_view_pagenav_before':
			case 'thread_view_form_before':
			case 'thread_view_qr_before':
			case 'thread_view_qr_after':

			case 'pagenode_container_article':

			case 'resource_view_sidebar_resource_info':
				$ourTemplate = $template->create(substr('tinhte_xentag_hook_' . $hookName, 0, 50), $template->getParams());
				$ourTemplate->setParams($hookParams);
				$rendered = $ourTemplate->render();

				self::injectRendered($contents, $rendered);
				break;
		}

		if ($hookName == 'tinhte_xentag_tag_cloud_item')
		{
			// our special hook to populate data to the sidebar
			// doing this will make it super-easy to use the sidebar template
			// just put the include statement in the target page and you are done!
			// <xen:include template="tinhte_xentag_sidebar_cloud" />
			// supported parameters:
			// - max: maximum number of links
			$tagModel = XenForo_Model::create('Tinhte_XenTag_Model_Tag');

			$conditions = array();
			$fetchOptions = array(
				'order' => 'content_count',
				'direction' => 'desc',
				'limit' => isset($hookParams['max']) ? $hookParams['max'] : Tinhte_XenTag_Option::get('cloudMax'),
			);

			$tags = $tagModel->getAllTag($conditions, $fetchOptions);
			$tagModel->calculateCloudLevel($tags);
			$results = '';

			foreach ($tags as $tag)
			{
				if (empty($tag['content_count']))
				{
					continue;
				}

				$search = array(
					'{TAG_TEXT}',
					'{TAG_LINK}',
					'{TAG_CONTENT_COUNT}',
					'{TAG_LEVEL}'
				);
				$replace = array(
					htmlspecialchars($tag['tag_text']),
					XenForo_Link::buildPublicLink('tags', $tag),
					XenForo_Template_Helper_Core::numberFormat($tag['content_count']),
					$tag['cloudLevel'],
				);
				$results .= str_replace($search, $replace, $contents);
			}

			$contents = $results;
		}
	}

	public static function injectRendered(&$target, $html, $offsetInTarget = 0, $mark = '<!-- [Tinhte] XenTag / Mark -->', $revertMark = '<!-- [Tinhte] XenTag / Revert Mark -->')
	{
		if ($offsetInTarget === false OR empty($html))
		{
			// do nothing if invalid offset is given
			// or the html is empty
			return;
		}

		$injected = false;
		$isRevert = (strpos($html, $revertMark) !== false);

		$startPos = strpos($html, $mark);
		if ($startPos !== false)
		{
			$endPos = strpos($html, $mark, $startPos + 1);
			if ($endPos !== false)
			{
				// found the two marks
				$markLen = strlen($mark);
				$marked = trim(substr($html, $startPos + $markLen, $endPos - $startPos - $markLen));

				if (!$isRevert)
				{
					// normal mode, look for the first occurence
					$markedPos = strpos($target, $marked, $offsetInTarget);
				}
				else
				{
					// revert mode, look for the last occurence
					$markedPos = strrpos($target, $marked, $offsetInTarget);
				}

				if ($markedPos !== false)
				{
					// the marked text has been found
					// start injecting our html in place
					$html = str_replace($mark, '', $html);
					$html = str_replace($revertMark, '', $html);

					$target = substr_replace($target, $html, $markedPos, strlen($marked));
				}

				// assume that it was injected
				$injected = true;
			}
		}

		if (!$injected)
		{
			$html = str_replace($mark, '', $html);
			$html = str_replace($revertMark, '', $html);

			if (!$isRevert)
			{
				//  normal mode, append the html
				$target .= $html;
			}
			else
			{
				// revert mode, insert instead of append
				$target = $html . $target;
			}
		}
	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += Tinhte_XenTag_FileSums::getHashes();
	}

	public static function widget_framework_ready(array &$renderers)
	{
		$renderers[] = 'Tinhte_XenTag_WidgetRenderer_Cloud';
		$renderers[] = 'Tinhte_XenTag_WidgetRenderer_RelatedThreads';
		$renderers[] = 'Tinhte_XenTag_WidgetRenderer_TaggedThreads';
	}

}

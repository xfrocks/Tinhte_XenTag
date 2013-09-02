<?php
class Tinhte_XenTag_Route_Prefix_Tags implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		if (in_array($routePath, array(
			'',
			'index'
		)))
		{
			$action = $routePath;
		}
		else
		{
			$action = $router->resolveActionWithStringParam($routePath, $request, Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT);

			if (preg_match('/^page-(\d+)$/', $action, $matches))
			{
				// supports matching /tags/text/page-n links
				$request->setParam('page', $matches[1]);
				$action = 'view';
			}
		}

		return $router->getRouteMatch('Tinhte_XenTag_ControllerPublic_Tag', $action, Tinhte_XenTag_Option::get('majorSection'));
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		if (!empty($data))
		{

			if (!is_array($data))
			{
				$data = array('tag_text' => $data);
			}

			if (!empty($data['tag_text']))
			{
				if (Tinhte_XenTag_Option::get('linkFormat') == Tinhte_XenTag_Option::LINK_FORMAT_BEAUTIFUL)
				{
					// try to use the beautiful format

					if (self::_isSafeText($data['tag_text']))
					{
						$encodedData = array('tag_text' => urlencode($data['tag_text']));
						$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);
						return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $encodedData, 'tag_text');
					}
				}

				// use the ugly format
				$extraParams[Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT] = $data['tag_text'];
				return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
			}
		}

		return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
	}

	protected function _isSafeText(&$text)
	{
		if (strpos($text, '/') !== false)
			return false;
		if (strpos($text, '"') !== false)
			return false;

		$text = trim(strtolower($text));

		return true;
	}

}

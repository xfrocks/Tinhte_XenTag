<?php
class Tinhte_XenTag_Route_PrefixAdmin_Tag implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'tag_id');
		$majorSection = 'Tinhte_XenTag_Tag';
		if (empty($action) OR strtolower($action) == 'index')
		{
			$majorSection = 'threadsPosts';
		}

		return $router->getRouteMatch('Tinhte_XenTag_ControllerAdmin_Tag', $action, $majorSection);
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		if (is_array($data))
		{
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'tag_id');
		}
		else
		{
			return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
		}
	}

}

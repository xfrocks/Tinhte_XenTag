<?php
class Tinhte_XenTag_Route_Prefix_Tags implements XenForo_Route_Interface {
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router) {
		if (in_array($routePath, array('', 'index'))) {
			$action = $routePath;			
		} else {
			$action = $router->resolveActionWithStringParam($routePath, $request, 'tag_text');
			
			if (preg_match('/^page-(\d+)$/', $action, $matches)) {
				// supports matching /tags/text/page-n links
				$request->setParam('page', $matches[1]);
				$action = 'view';
			}
		}
		
		return $router->getRouteMatch('Tinhte_XenTag_ControllerPublic_Tag', $action, Tinhte_XenTag_Option::get('majorSection'));
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams) {
		if (!empty($data)) {
			
			if (!is_array($data)) {
				$data = array('tag_text' => $data);
			}
			
			$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);
			
			if (self::_isSafeText($data['tag_text'])) {
				return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'tag_text');
			} else {
				$extraParams['tag_text'] = $data['tag_text'];
				return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
			}
		} else {
			return XenForo_Link::buildBasicLink($outputPrefix, $action, $extension);
		}
	}
	
	protected function _isSafeText($text) {
		if (strpos($text, '/') !== false) return false;
		if (strpos($text, '"') !== false) return false;
		
		return true;
	}
}
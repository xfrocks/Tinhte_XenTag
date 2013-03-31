<?php

class Tinhte_XenTag_WidgetRenderer_Cloud extends WidgetFramework_WidgetRenderer {
	protected function _getConfiguration() {
		return array(
			'name' => '[Tinhte] XenTag - Tag Cloud',
			'options' => array(
				'limit' => XenForo_Input::UINT,
			),
			'useCache' => true,
			'cacheSeconds' => 600, // cache for 10 minutes
		);
	}
	
	protected function _getOptionsTemplate() {
		return 'tinhte_xentag_widget_cloud_options';
	}
	
	protected function _getRenderTemplate(array $widget, $positionCode, array $params) {
		return 'tinhte_xentag_widget_cloud';
	}
	
	protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $template) {
		return $template->render();
	}
	
	protected function _getExtraDataLink(array $widget) {
		return XenForo_Link::buildPublicLink(Tinhte_XenTag_Option::get('routePrefix'));
	}
	
	protected function _getRequiredExternal(array $widget) {
		return array(
			array('css', 'tinhte_xentag'),
		);
	}
}
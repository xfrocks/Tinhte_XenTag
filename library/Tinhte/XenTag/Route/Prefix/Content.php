<?php
class Tinhte_XenTag_Route_Prefix_Content implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        return $router->getRouteMatch('Tinhte_XenTag_ControllerPublic_Content', $routePath);
    }

}

<?php

class Tinhte_XenTag_bdApi_ControllerApi_Index extends XFCP_Tinhte_XenTag_bdApi_ControllerApi_Index
{
    protected function _getModules()
    {
        $modules = parent::_getModules();

        $modules['tags/followers'] = 2017041101;

        return $modules;
    }
}
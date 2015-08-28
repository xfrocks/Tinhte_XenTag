<?php

class Tinhte_XenTag_XenForo_ViewPublic_Page_View extends XFCP_Tinhte_XenTag_XenForo_ViewPublic_Page_View
{
    public function prepareParams()
    {
        if (!isset($this->_params['Tinhte_XenTag_tagsList'])
            && !empty($this->_params['page'])
        ) {
            $this->_params['Tinhte_XenTag_tagsList'] = $this->_params['page'][Tinhte_XenTag_Constants::FIELD_PAGE_TAGS]
                ? Tinhte_XenTag_Helper::unserialize($this->_params['page'][Tinhte_XenTag_Constants::FIELD_PAGE_TAGS])
                : array();
        }

        parent::prepareParams();
    }

}
<?php

class Tinhte_XenTag_XenForo_ViewPublic_Thread_ViewNewPosts extends XFCP_Tinhte_XenTag_XenForo_ViewPublic_Thread_ViewNewPosts
{
    public function renderHtml()
    {
        parent::renderHtml();

        Tinhte_XenTag_ContentWrapper_Post::wrap($this->_params);
    }

}

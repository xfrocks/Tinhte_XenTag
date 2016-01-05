<?php

abstract class Tinhte_XenTag_TagHandler_NodeAbstract extends XenForo_TagHandler_Abstract
{
    /**
     * @var XenForo_Model_Node
     */
    protected $_nodeModel = null;

    public function getPermissionsFromContext(array $context, array $parentContext = null)
    {
        return array();
    }

    public function getContentDate(array $content)
    {
        return 0;
    }

    public function getContentVisibility(array $content)
    {
        return true;
    }

    public function canViewResult(array $result, array $viewingUser)
    {
        return true;
    }

    /**
     * @return XenForo_Model_Forum
     */
    protected function _getForumModel()
    {
        return $this->_getNodeModel()->getModelFromCache('XenForo_Model_Forum');
    }

    /**
     * @return XenForo_Model_Node
     */
    protected function _getNodeModel()
    {
        if (!$this->_nodeModel) {
            $this->_nodeModel = XenForo_Model::create('XenForo_Model_Node');
        }

        return $this->_nodeModel;
    }

    /**
     * @return XenForo_Model_Page
     */
    protected function _getPageModel()
    {
        return $this->_getNodeModel()->getModelFromCache('XenForo_Model_Page');
    }

}
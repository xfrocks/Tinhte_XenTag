<?php

class Tinhte_XenTag_XenForo_TagHandler_Tagger extends XFCP_Tinhte_XenTag_XenForo_TagHandler_Tagger
{
    public function save()
    {
        $cache = parent::save();

        if (!empty($cache)
            && !empty($this->_addTags)
        ) {
            $contentData = array(
                'content_type' => $this->_handler->getContentType(),
                'content_id' => $this->_contentId,
            );
            $permissionConfig = null;

            switch ($contentData['content_type']) {
                case 'post':
                case 'thread':
                    $contentData += $this->_handler->getBasicContent($this->_contentId);
                    if ($contentData['content_type'] === 'thread') {
                        $contentData['content_type'] = 'post';
                        $contentData['content_id'] = $contentData['first_post_id'];
                    }
                    $permissionConfig = array(
                        'content_type' => 'node',
                        'content_id' => $contentData['node_id'],
                        'permissions' => array(
                            'view',
                            'viewOthers',
                            'viewContent'
                        ),
                    );
                    break;
                case 'resource':
                    /** @var XenResource_Model_Resource $resourceModel */
                    $resourceModel = $this->_tagModel->getModelFromCache('XenResource_Model_Resource');
                    $contentData = $resourceModel->getResourceById($this->_contentId, array(
                        'join' => XenResource_Model_Resource::FETCH_USER
                            | XenResource_Model_Resource::FETCH_DESCRIPTION
                            | XenResource_Model_Resource::FETCH_CATEGORY
                    ));
                    $contentData['content_type'] = 'resource_update';
                    $contentData['content_id'] = $contentData['description_update_id'];
                    $contentData['message'] = $contentData['description'];
                    $permissionConfig = array(
                        'content_type' => 'resource_category',
                        'content_id' => $contentData['resource_category_id'],
                        'permissions' => array('view'),
                    );
                    break;
            }

            if (!empty($permissionConfig)) {
                /** @var Tinhte_XenTag_Model_TagWatch $tagWatchModel */
                $tagWatchModel = $this->_tagModel->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
                $tagWatchModel->sendNotificationToWatchUsersOnTagged($this->_addTags, $contentData, $permissionConfig);
            }
        }

        return $cache;
    }

}
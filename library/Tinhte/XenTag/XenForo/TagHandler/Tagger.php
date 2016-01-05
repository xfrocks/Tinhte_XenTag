<?php

class Tinhte_XenTag_XenForo_TagHandler_Tagger extends XFCP_Tinhte_XenTag_XenForo_TagHandler_Tagger
{
    protected $_Tinhte_XenTag_queriedTags = array();
    protected $_Tinhte_XenTag_unauthorizedStaffTags = array();
    protected $_Tinhte_XenTag_orderedTags = array();

    public function setTags(array $tags, $ignoreNonRemovable = true)
    {
        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $this->_tagModel;
        $tagModel->Tinhte_XenTag_cacheQueriedTags(true);
        $result = parent::setTags($tags, $ignoreNonRemovable);
        $this->_Tinhte_XenTag_queriedTags += $tagModel->Tinhte_XenTag_getQueriedTags();
        $tagModel->Tinhte_XenTag_cacheQueriedTags(false);

        $staffTagsBeingAdded = array();
        foreach ($this->_addTags as $tagId => $tagText) {
            if (isset($this->_Tinhte_XenTag_queriedTags[$tagId])) {
                $tagRef =& $this->_Tinhte_XenTag_queriedTags[$tagId];
                if (!empty($tagRef['tinhte_xentag_staff'])) {
                    $staffTagsBeingAdded[$tagId] = $tagText;
                }
            }
        }

        $staffTagsBeingRemoved = array();
        foreach ($this->_removeTags as $tagId => $tagText) {
            if (isset($this->_existingTags[$tagId])) {
                $tagRef =& $this->_existingTags[$tagId];
                if (!empty($tagRef['tinhte_xentag_staff'])) {
                    $staffTagsBeingRemoved[$tagId] = $tagText;
                }
            }
        }

        if (count($staffTagsBeingAdded) > 0) {
            $visitor = XenForo_Visitor::getInstance();
            if (!$visitor->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF)) {
                $this->_Tinhte_XenTag_unauthorizedStaffTags = $staffTagsBeingAdded;
            }
        }

        if (count($staffTagsBeingRemoved) > 0) {
            // silently ignore it, regardless of $ignoreNonRemovable
            foreach ($staffTagsBeingRemoved as $tagId => $tagText) {
                if (isset($this->_removeTags[$tagId])) {
                    unset($this->_removeTags[$tagId]);
                }
            }
        }

        $this->_Tinhte_XenTag_orderedTags = $tags;

        return $result;
    }

    public function getErrors()
    {
        $errors = parent::getErrors();

        if (count($this->_Tinhte_XenTag_unauthorizedStaffTags) > 0) {
            $errors['tinhte_xentag_staff'] = new XenForo_Phrase('tinhte_xentag_unauthorized_tags_x',
                array('tags' => implode(', ', $this->_Tinhte_XenTag_unauthorizedStaffTags)));
        }

        return $errors;
    }

    public function save()
    {
        $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_TAGGER_SAVE] = $this;
        $cache = parent::save();
        unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_TAGGER_SAVE]);

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

    public function Tinhte_XenTag_getContentTagCacheOnSave(XenForo_Model_Tag $model, array $cache)
    {
        if (!Tinhte_XenTag_Option::get('keepOrder')
            || empty($this->_Tinhte_XenTag_orderedTags)
        ) {
            return $cache;
        }

        $cacheOrdered = $model->getFoundTagsInList($this->_Tinhte_XenTag_orderedTags, $cache);

        foreach ($cache as $tagId => $tag) {
            if (!isset($cacheOrdered[$tagId])) {
                $cacheOrdered[$tagId] = $tag;
            }
        }

        return $cacheOrdered;
    }

}
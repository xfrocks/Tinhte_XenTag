<?php

class Tinhte_XenTag_XenForo_Model_Tag extends XFCP_Tinhte_XenTag_XenForo_Model_Tag
{
    protected $_Tinhte_XenTag_queriedTags = null;

    public function getTagListForEdit($contentType, $contentId, $editOthers, $userId = null)
    {
        $this->Tinhte_XenTag_cacheQueriedTags(true);
        $result = parent::getTagListForEdit($contentType, $contentId, $editOthers, $userId);

        $visitor = XenForo_Visitor::getInstance();
        if (!$visitor->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF)) {
            $queriedTags = $this->Tinhte_XenTag_getQueriedTags();

            foreach (array_keys($result['editable']) as $i) {
                $isStaffTag = false;

                foreach ($queriedTags as $tag) {
                    if ($result['editable'][$i] === $tag['tag']
                        && !empty($tag['tinhte_xentag_staff'])
                    ) {
                        $isStaffTag = true;
                    }
                }

                if ($isStaffTag) {
                    $result['uneditable'][] = $result['editable'][$i];
                    unset($result['editable'][$i]);
                }
            }
        }

        $this->Tinhte_XenTag_cacheQueriedTags(false);

        return $result;
    }

    public function getTags(array $tags, &$notFound = array())
    {
        $result = parent::getTags($tags, $notFound);

        if (is_array($this->_Tinhte_XenTag_queriedTags)) {
            $this->_Tinhte_XenTag_queriedTags += $result;
        }

        return $result;
    }

    public function getTagsForContent($contentType, $contentId)
    {
        $result = parent::getTagsForContent($contentType, $contentId);

        if (is_array($this->_Tinhte_XenTag_queriedTags)) {
            $this->_Tinhte_XenTag_queriedTags += $result;
        }

        return $result;
    }

    public function Tinhte_XenTag_cacheQueriedTags($enabled)
    {
        if ($enabled) {
            $this->_Tinhte_XenTag_queriedTags = array();
        } else {
            $this->_Tinhte_XenTag_queriedTags = null;
        }
    }

    public function Tinhte_XenTag_getQueriedTags()
    {
        if (is_array($this->_Tinhte_XenTag_queriedTags)) {
            return $this->_Tinhte_XenTag_queriedTags;
        } else {
            return array();
        }
    }

    public function Tinhte_XenTag_canWatchTag(/** @noinspection PhpUnusedParameterInspection */
        array $tag, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id'])) {
            return false;
        }

        return !!XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', Tinhte_XenTag_Constants::PERM_USER_WATCH);
    }

    public function Tinhte_XenTag_getTagsByIds(array $ids)
    {
        if (count($ids) === 1) {
            $tag = $this->getTagById(reset($ids));

            if (!empty($tag)) {
                return array($tag['tag_id'] => $tag);
            }
        } else {
            return $this->fetchAllKeyed('
                SELECT *
                FROM xf_tag
                WHERE tag_id IN (' . $this->_getDb()->quote($ids) . ')
            ', 'tag_id');
        }

        return array();
    }

    public function Tinhte_XenTag_getTagsFromCache()
    {
        $tags = $this->_getDataRegistryModel()->get(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TAGS);

        if ($tags === null) {
            $tags = $this->Tinhte_XenTag_rebuildTagsCache();
        }

        return $tags;
    }

    public function Tinhte_XenTag_rebuildTagsCache()
    {
        $limit = 1000;
        $max = intval(Tinhte_XenTag_Option::get('autoTagGlobalMax'));
        if ($max > 0) {
            $limit = $max;
        }

        $tags = $this->getTagsForCloud($limit);
        $this->_getDataRegistryModel()->set(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TAGS, $tags);

        return $tags;
    }

    public function getContentIdsByTagId($tagId, $limit, $visibleOnly = true)
    {
        $contentIds = parent::getContentIdsByTagId($tagId, $limit, $visibleOnly);

        $this->Tinhte_XenTag_prioritizeContentIds($tagId, $limit, $contentIds);

        return $contentIds;
    }

    public function Tinhte_XenTag_prioritizeContentIds($tagId, $limit, array &$contentIds)
    {
        /** @var Tinhte_XenTag_Model_Search $searchModel */
        $searchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');

        $prioritizedContents = $searchModel->getPrioritizedContents();
        if (empty($prioritizedContents)) {
            // nothing to do
            return;
        }

        $dbResults = $this->_getDb()->query('
            SELECT tag_content_id, content_type, content_id
            FROM xf_tag_content
            WHERE tag_id = ?
                AND visible = 1
                AND content_type IN (' . $this->_getDb()->quote(array_keys($prioritizedContents)) . ')
            ORDER BY content_date DESC
		', $tagId);
        $prioritizedContentIds = array();
        while ($dbResult = $dbResults->fetch()) {
            $prioritizedContentIds[$dbResult['tag_content_id']] = array($dbResult['content_type'], $dbResult['content_id']);
        }
        if (empty($prioritizedContentIds)) {
            // no prioritized results could be found, do nothing
            return;
        }

        $searchModel->sortResults($prioritizedContents, $contentIds, $prioritizedContentIds);

        if (count($contentIds) > $limit) {
            $contentIds = array_slice($contentIds, 0, $limit);
        }
    }

    public function autoCompleteTag($tag, $limit = 10)
    {
        $tags = parent::autoCompleteTag($tag, $limit);

        $visitor = XenForo_Visitor::getInstance();
        if (!$visitor->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF)) {
            foreach (array_keys($tags) as $tagId) {
                if (!empty($tags[$tagId]['tinhte_xentag_staff'])) {
                    unset($tags[$tagId]);
                }
            }
        }

        return $tags;
    }


}
<?php

class Tinhte_XenTag_XenForo_Model_Tag extends XFCP_Tinhte_XenTag_XenForo_Model_Tag
{
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

    public function Tinhte_XenTag_prepareApiDataForTags(array $tags)
    {
        $data = array();

        foreach ($tags as $tagId => $tag) {
            $data[$tagId] = $tag['tag'];
        }

        // TODO: include tag data like content count / view count / etc.?

        return $data;
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


}
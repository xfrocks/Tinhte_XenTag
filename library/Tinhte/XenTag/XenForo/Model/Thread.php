<?php

class Tinhte_XenTag_XenForo_Model_Thread extends XFCP_Tinhte_XenTag_XenForo_Model_Thread
{

    const CONDITIONS_THREAD_ID = 'Tinhte_XenTag_thread_id';

    public function prepareThreadConditions(array $conditions, array &$fetchOptions)
    {
        $result = parent::prepareThreadConditions($conditions, $fetchOptions);

        $sqlConditions = array($result);

        if (isset($conditions[self::CONDITIONS_THREAD_ID])) {
            if (is_array($conditions[self::CONDITIONS_THREAD_ID])) {
                $sqlConditions[] = "thread.thread_id IN (" . $this->_getDb()->quote($conditions[self::CONDITIONS_THREAD_ID]) . ")";
            } else {
                $sqlConditions[] = "thread.thread_id = " . $this->_getDb()->quote($conditions[self::CONDITIONS_THREAD_ID]);
            }
        }

        if (count($sqlConditions) > 1) {
            // some of our conditions have been found
            return $this->getConditionsForClause($sqlConditions);
        } else {
            return $result;
        }
    }

    public function prepareApiDataForThread(array $thread, array $forum, array $firstPosts)
    {
        $data = parent::prepareApiDataForThread($thread, $forum, $firstPosts);

        $tags = Tinhte_XenTag_Helper::unserialize($thread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]);

        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
        $data['thread_tags'] = $tagModel->Tinhte_XenTag_prepareApiDataForTags($tags);

        return $data;
    }

}

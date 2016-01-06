<?php

class Tinhte_XenTag_XenForo_Model_Thread extends XFCP_Tinhte_XenTag_XenForo_Model_Thread
{

    const CONDITIONS_TAG_ID = 'Tinhte_XenTag_tag_id';
    const CONDITIONS_THREAD_ID = 'Tinhte_XenTag_thread_id';

    public function prepareThreadConditions(array $conditions, array &$fetchOptions)
    {
        $result = parent::prepareThreadConditions($conditions, $fetchOptions);

        $sqlConditions = array($result);

        if (!empty($conditions[self::CONDITIONS_TAG_ID])) {
            $fetchOptions[self::CONDITIONS_TAG_ID] = true;
            $sqlConditions[] = "tag_content.tag_id = " . intval($conditions[self::CONDITIONS_TAG_ID]);
        }

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

    public function prepareThreadFetchOptions(array $fetchOptions)
    {
        $result = parent::prepareThreadFetchOptions($fetchOptions);

        if (!empty($fetchOptions[self::CONDITIONS_TAG_ID])) {
            $result['joinTables'] .= '
                LEFT JOIN `xf_tag_content` AS tag_content
                ON (tag_content.content_type = "thread" AND tag_content.content_id = thread.thread_id)';
        }

        return $result;
    }

    public function updateThreadViews()
    {
        parent::updateThreadViews();

        if (Tinhte_XenTag_Option::get('logView')) {
            /** @var Tinhte_XenTag_Model_TagView $tagViewModel */
            $tagViewModel = $this->getModelFromCache('Tinhte_XenTag_Model_TagView');
            $tagViewModel->updateTagViews();
        }
    }


}

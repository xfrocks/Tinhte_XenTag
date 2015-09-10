<?php

class Tinhte_XenTag_Deferred_UpgradeFrom134 extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        if (XenForo_Application::$versionId < 1050000) {
            // only upgrade if XenForo version is 1.5+
            return false;
        }

        $data = array_merge(array(
            'position' => 0,
            'batch' => 100
        ), $data);
        $data['batch'] = max(1, $data['batch']);

        $db = XenForo_Application::getDb();

        if ($data['position'] == 0
            && (!$db->fetchOne('SHOW TABLES LIKE \'xf_tinhte_xentag_tag\'')
                || !$db->fetchOne('SHOW TABLES LIKE \'xf_tinhte_xentag_tagged_content\''))
        ) {
            // looks like the add-on does not have old data, nothing to do here
            return false;
        }

        /** @var XenForo_Model_Tag $tagModel */
        $tagModel = XenForo_Model::create('XenForo_Model_Tag');

        $xenTagIds = $db->fetchCol($db->limit('
			SELECT tag_id
			FROM `xf_tinhte_xentag_tag`
			WHERE tag_id > ?
			ORDER BY tag_id
		', $data['batch']), $data['position']);
        if (empty($xenTagIds)) {
            return true;
        }

        foreach ($xenTagIds AS $xenTagId) {
            $data['position'] = $xenTagId;

            $ourTag = $db->fetchRow('SELECT * FROM `xf_tinhte_xentag_tag` WHERE tag_id = ?', $xenTagId);

            $coreTag = $tagModel->getTag($ourTag['tag_text']);

            /** @var Tinhte_XenTag_XenForo_DataWriter_Tag $coreDw */
            $coreDw = XenForo_DataWriter::create('XenForo_DataWriter_Tag');
            if (!empty($coreTag)) {
                $coreDw->setExistingData($coreTag, true);
            } else {
                $coreDw->set('tag', $tagModel->normalizeTag($ourTag['tag_text']), array(
                    'runVerificationCallback' => false,
                ));
            }
            $coreDw->set('tinhte_xentag_staff', !empty($ourTag['is_staff']));
            $coreDw->set('tinhte_xentag_title', $ourTag['tag_title']);
            $coreDw->set('tinhte_xentag_description', $ourTag['tag_description']);

            if ($coreDw->hasChanges()) {
                $coreDw->save();
            }

            $tagId = $coreDw->get('tag_id');

            // bring all tagged mapping to XenForo, regardless of content types
            $db->query('
                INSERT IGNORE INTO xf_tag_content
                    (content_type, content_id, tag_id, add_user_id, add_date, content_date, visible)
                SELECT IF(content_type = "tinhte_xentag_resource", "resource",
                        IF(content_type = "tinhte_xentag_page", "node",
                        IF(content_type = "tinhte_xentag_forum", "node", content_type))),
                    content_id, ' . intval($tagId) . ', tagged_user_id, tagged_date, tagged_date, 1
                FROM `xf_tinhte_xentag_tagged_content`
                WHERE tag_id = ?
            ', $xenTagId);

            $tagModel->recalculateTagUsage($tagId);
            XenForo_Application::defer('XenForo_Deferred_TagRecache', array(
                'tagId' => $tagId
            ), 'tagUpdate' . $tagId, true);
        }

        $actionPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('tags');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }

}

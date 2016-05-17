<?php

class Tinhte_XenTag_Deferred_UpgradeFrom3020203 extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'position' => 0,
            'batch' => 100
        ), $data);
        $data['batch'] = max(1, $data['batch']);

        $db = XenForo_Application::getDb();

        /** @var XenForo_Model_Tag $tagModel */
        $tagModel = XenForo_Model::create('XenForo_Model_Tag');

        $tags = $tagModel->getTagsInRange($data['position'], $data['batch']);
        if (empty($tags)) {
            return true;
        }

        foreach ($tags as $tag) {
            $data['position'] = $tag['tag_id'];

            if ($tag['tinhte_xentag_create_date'] > 0) {
                continue;
            }

            $firstTaggedContent = $db->fetchRow('
                SELECT *
                FROM `xf_tag_content`
                WHERE tag_id = ?
                ORDER BY tag_content_id ASC 
                LIMIT 1
            ', $tag['tag_id']);
            if (empty($firstTaggedContent)) {
                continue;
            }

            /** @var Tinhte_XenTag_XenForo_DataWriter_Tag $dw */
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Tag');
            $dw->setExistingData($tag, true);
            $dw->set('tinhte_xentag_create_date', $firstTaggedContent['add_date']);
            $dw->save();
        }

        $actionPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('tags');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }

}

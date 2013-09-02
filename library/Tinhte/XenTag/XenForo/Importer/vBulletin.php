<?php

class Tinhte_XenTag_XenForo_Importer_vBulletin extends XFCP_Tinhte_XenTag_XenForo_Importer_vBulletin
{

	public function getSteps()
	{
		$steps = parent::getSteps();

		$steps = array_merge($steps, array('tinhteXenTagTags' => array(
				'title' => 'Import Tags',
				'depends' => array('threads')
			), ));

		return $steps;
	}

	public function stepTinhteXenTagTags($start, array $options)
	{
		$options = array_merge(array(
			'limit' => 500,
			'max' => false
		), $options);

		$sDb = $this->_sourceDb;
		$prefix = $this->_prefix;

		/* @var $model XenForo_Model_Import */
		$model = $this->_importModel;

		if ($options['max'] === false)
		{
			$options['max'] = $sDb->fetchOne('
				SELECT MAX(threadid)
				FROM ' . $prefix . 'thread
			');
		}

		$tableTagExists = !!$sDb->fetchOne('SHOW TABLES LIKE \'' . $prefix . 'tag\'');
		$tableTagContentExists = !!$sDb->fetchOne('SHOW TABLES LIKE \'' . $prefix . 'tagcontent\'');
		$tableTagThreadExists = !!$sDb->fetchOne('SHOW TABLES LIKE \'' . $prefix . 'tagthread\'');
		// vb37 and lower

		if (!$tableTagExists)
			return true;
		if (!$tableTagContentExists AND !$tableTagThreadExists)
			return true;

		if ($tableTagContentExists)
		{
			$tags = $sDb->fetchAll('
					SELECT tag.*, tagcontent.*
					FROM ' . $prefix . 'tagcontent AS tagcontent
					INNER JOIN ' . $prefix . 'tag AS tag ON (tag.tagid = tagcontent.tagid)
					WHERE
						tagcontent.contenttypeid = 2
						AND tagcontent.contentid > ' . $sDb->quote($start) . '
						AND tagcontent.contentid < ' . $sDb->quote($start + $options['limit']) . '
				');
		}
		else
		{
			$tags = $sDb->fetchAll('
					SELECT tag.*, tagthread.threadid AS contentid
					FROM ' . $prefix . 'tagthread AS tagthread
					INNER JOIN ' . $prefix . 'tag AS tag ON (tag.tagid = tagthread.tagid)
					WHERE
						tagthread.threadid > ' . $sDb->quote($start) . '
						AND tagthread.threadid < ' . $sDb->quote($start + $options['limit']) . '
				');
		}

		$next = 0;
		$total = 0;

		if (!$tags)
		{
			// added second condition to make sure all threads are processed
			if ($start + $options['limit'] > $options['max'])
			{
				return true;
			}
			else
			{
				$next = $start + $options['limit'];
			}
		}

		$threadIdMap = $model->getThreadIdsMapFromArray($tags, 'contentid');

		$threadTags = array();
		foreach (array_keys($tags) as $key)
		{
			$threadId = $tags[$key]['contentid'];

			if (!isset($threadTags[$threadId]))
				$threadTags[$threadId] = array();

			$threadTags[$threadId][] = $this->_convertToUtf8($tags[$key]['tagtext'], true);

			unset($tags[$key]);
			// free memory asap
		}

		XenForo_Db::beginTransaction();

		foreach ($threadTags as $threadId => $tags)
		{
			$next = max($next, $threadId);

			$newThreadId = $this->_mapLookUp($threadIdMap, $threadId);
			if (empty($newThreadId))
			{
				// new thread not found? Hmm
				continue;
			}

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$dw->setImportMode(true);
			$dw->setExistingData($newThreadId);
			$dw->Tinhte_XenTag_setTags($tags);
			$dw->save();

			$dw->Tinhte_XenTag_updateTagsInDatabase();
			// manually call this because _postSave() won't be called in import mode

			$total++;
		}

		XenForo_Db::commit();

		$this->_session->incrementStepImportTotal($total);

		return array(
			$next,
			$options,
			$this->_getProgressOutput($next, $options['max'])
		);
	}

}

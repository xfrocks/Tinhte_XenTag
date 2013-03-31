<?php

class Tinhte_XenTag_XenForo_DataWriter_Forum extends XFCP_Tinhte_XenTag_XenForo_DataWriter_Forum {
	
	protected function _getFields() {
		$fields = parent::_getFields();
		
		$fields['xf_forum'][Tinhte_XenTag_Constants::FIELD_FORUM_OPTIONS] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}'
		);
		
		return $fields;
	}
	
	protected function _preSave() {
		// checks for our controller and call it first
		if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE])) {
			$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERADMIN_FORUM_SAVE]->Tinhte_XenTag_actionSave($this);
		}
		
		return parent::_preSave();
	}
	
}
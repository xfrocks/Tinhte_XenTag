<?php

$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_SEARCH_SOURCEHANDLER_LOADED] = true;

class Tinhte_XenTag_XenForo_Search_SourceHandler extends XFCP_Tinhte_XenTag_XenForo_Search_SourceHandler {
	
	protected static $_extraMetaData = false;
	
	public function processConstraints(array $constraints, XenForo_Search_DataHandler_Abstract $typeHandler = null) {
		$processed = parent::processConstraints($constraints, $typeHandler);
		
		foreach ($constraints AS $constraint => $constraintInfo) {
			if ($constraint == Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS) {
				$processed[$constraint] = array(
					'metadata' => array(Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS, Tinhte_XenTag_Helper::getSafeTagsTextArrayForSearch($constraintInfo)),
				);
			}
		}
		
		return $processed;
	}
	
	public function insertIntoIndex($contentType, $contentId, $title, $message, $itemDate, $userId, $discussionId = 0, array $metadata = array()) {
		// looks for our customized meta data and put it in place
		if (!empty(self::$_extraMetaData)) {
			$metadata = XenForo_Application::mapMerge($metadata, self::$_extraMetaData);
			self::$_extraMetaData = false; // reset immediately
		}
		
		return parent::insertIntoIndex($contentType, $contentId, $title, $message, $itemDate, $userId, $discussionId, $metadata);
	}
	
	public function getMetadataKey($keyName, $value) {
		$result = parent::getMetadataKey($keyName, $value);
		
		if ($keyName == Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS && is_array($result)) {
			// we do this because if user wants more than one tags,
			// it usually the case that he/she wants contents with all
			// tags attached, not contents with one of the tags (if it's
			// what he/she wants, redo the search may work better)
			// sondh@2012-08-17 updated this method
			// to limit the implode to our metadata keys only
			// to reduce unexpected behaviors
			$result = implode(' ', $result);
		}
		
		return $result;
	}
	
	public static function setExtraMetaData(array $metaData) {
		self::$_extraMetaData = $metaData;
	}
	
	public static function clearExtraMetaData() {
		self::$_extraMetaData = false;
	}
}
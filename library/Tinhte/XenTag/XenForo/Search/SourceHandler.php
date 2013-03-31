<?php
class Tinhte_XenTag_XenForo_Search_SourceHandler extends XFCP_Tinhte_XenTag_XenForo_Search_SourceHandler {
	
	protected static $_extraMetaData = false;
	
	public function processConstraints(array $constraints, XenForo_Search_DataHandler_Abstract $typeHandler = null) {
		$processed = parent::processConstraints($constraints, $typeHandler);
		
		foreach ($constraints AS $constraint => $constraintInfo) {
			if ($constraint == Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS) {
				$processed[$constraint] = array(
					'metadata' => array(Tinhte_XenTag_Constants::SEARCH_METADATA_TAGS, $constraintInfo),
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
		// TODO: bug report?
		$result = parent::getMetadataKey($keyName, $value);
		
		if (is_array($result)) {
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
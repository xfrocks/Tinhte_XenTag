<?php

class Tinhte_XenTag_Constants
{
	const GLOBALS_CONTROLLERADMIN_FORUM_SAVE = 'Tinhte_XenTag_XenForo_ControllerAdmin_Forum::actionSave';
	const GLOBALS_CONTROLLERADMIN_PAGE_SAVE = 'Tinhte_XenTag_XenForo_ControllerAdmin_Page::actionSave';
	const GLOBALS_CONTROLLERPUBLIC_FORUM_ADD_THREAD = 'Tinhte_XenTag_XenForo_ControllerPublic_Forum::actionAddThread';
	const GLOBALS_CONTROLLERPUBLIC_POST_SAVE = 'Tinhte_XenTag_XenForo_ControllerPublic_Post::actionSave';
	const GLOBALS_CONTROLLERPUBLIC_SEARCH_SEARCH = 'Tinhte_XenTag_XenForo_ControllerPublic_Search::actionSearch';
	const GLOBALS_CONTROLLERPUBLIC_THREAD_SAVE = 'Tinhte_XenTag_XenForo_ControllerPublic_Thread::actionSave';
	const GLOBALS_CONTROLLERPUBLIC_RESOURCE_SAVE = 'Tinhte_XenTag_XenResource_ControllerPublic_Resource::actionSave';

	const URI_PARAM_TAG_TEXT = 't';

	const CONTENT_TYPE_PAGE = 'tinhte_xentag_page';
	const CONTENT_TYPE_FORUM = 'tinhte_xentag_forum';
	const CONTENT_TYPE_RESOURCE = 'tinhte_xentag_resource';

	const FORM_TAGS_ARRAY = 'tinhte_xentag_tags';
	const FORM_TAGS_TEXT = 'tinhte_xentag_tags_text';
	const FORM_INCLUDED = 'tinhte_xentag_included';
	// used in search bar form
	const FORM_TAGS_TEXT_NO_INCLUDED = 'tinhte_xentag_tags_text_no_include';
	

	const FIELD_THREAD_TAGS = 'tinhte_xentag_tags';
	const FIELD_FORUM_OPTIONS = 'tinhte_xentag_options';
	const FIELD_PAGE_TAGS = 'tinhte_xentag_tags';
	const FIELD_FORUM_TAGS = 'tinhte_xentag_tags';
	const FIELD_RESOURCE_TAGS = 'tinhte_xentag_tags';

	const SEARCH_TYPE_TAG = 'tinhte_xentag_tag';
	// something cool?
	const SEARCH_SEARCH_ID = 'x';
	const SEARCH_CONSTRAINT_TAGS = 'tags';
	// not sure why we need 2 of these?
	const SEARCH_METADATA_TAGS = 'tags';
	const SEARCH_INPUT_TAGS = 'tinhte_xentag_tags';

	const PERM_USER_TAG = 'Tinhte_XenTag_tag';
	const PERM_USER_TAG_ALL = 'Tinhte_XenTag_tagAll';
	const PERM_USER_CREATE_NEW = 'Tinhte_XenTag_createNew';
	const PERM_USER_EDIT = 'Tinhte_XenTag_edit';
	const PERM_USER_RESOURCE_TAG = 'Tinhte_XenTag_resourceTag';
	const PERM_USER_RESOURCE_TAG_ALL = 'Tinhte_XenTag_resourceAll';
	const PERM_USER_IS_STAFF = 'Tinhte_XenTag_isStaff';
	const PERM_ADMIN_MANAGE = 'Tinhte_XenTag';

	const DATA_REGISTRY_KEY = 'Tinhte_XenTag_tags';

	const REGEX_SEPARATOR = '(,|،)';
}

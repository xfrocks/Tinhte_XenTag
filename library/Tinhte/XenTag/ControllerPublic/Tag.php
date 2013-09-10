<?php

class Tinhte_XenTag_ControllerPublic_Tag extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$tagText = $this->_input->filterSingle(Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT, XenForo_Input::STRING);
		if (!empty($tagText))
		{
			return $this->responseReroute(__CLASS__, 'view');
		}

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('tags'));

		$tagModel = $this->_getTagModel();

		$conditions = array();
		$fetchOptions = array(
			'order' => 'content_count',
			'direction' => 'desc',
			'limit' => Tinhte_XenTag_Option::get('cloudMax'),
		);

		$tags = $tagModel->getAllTag($conditions, $fetchOptions);
		$tagModel->calculateCloudLevel($tags);

		$viewParams = array('tags' => $tags);

		return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_List', 'tinhte_xentag_tag_list', $viewParams);
	}

	public function actionSearch()
	{
		$tags = $this->_getTagModel()->processInput($this->_input);

		if (empty($tags))
		{
			// no tag?!
			return $this->_getNoResultsResponse($tags);
		}
		else
		if (count($tags) == 1)
		{
			// search for one tag only, redirect to view action
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('tags', array('tag_text' => $tags[0])));
		}
		else
		{
			$search = $this->_doSearch($tags);
		}

		if ($search instanceof XenForo_ControllerResponse_Message)
		{
			return $search;
		}
		elseif (!is_array($search))
		{
			return $this->_getNoResultsResponse($tagText);
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('search', $search), '');
	}

	public function actionView()
	{
		$tagText = $this->_input->filterSingle(Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT, XenForo_Input::STRING);
		if (empty($tagText))
		{
			return $this->responseNoPermission();
		}

		$tagModel = $this->_getTagModel();

		/* @var $searchModel XenForo_Model_Search */
		$searchModel = $this->getModelFromCache('XenForo_Model_Search');

		/* @var $threadModel XenForo_Model_Thread */
		$threadModel = $this->getModelFromCache('XenForo_Model_Thread');

		$tag = $tagModel->getTagByText($tagText);
		if (empty($tag))
		{
			return $this->_getNoResultsResponse($tagText);
		}

		$tagLink = $tagModel->getTagLink($tag);
		if (!empty($tagLink))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL, $tagLink);
		}

		if (!empty($tag['is_staff']) AND !XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF))
		{
			// no permission to view this tag
			return $this->responseNoPermission();
		}

		$searchId = $this->_input->filterSingle(Tinhte_XenTag_Constants::SEARCH_SEARCH_ID, XenForo_Input::UINT);
		if (empty($searchId))
		{
			$search = $this->_doSearch($tagText);
		}
		else
		{
			$search = $searchModel->getSearchById($searchId);

			if (empty($search) || $search['user_id'] != XenForo_Visitor::getUserId() || $search['search_type'] != Tinhte_XenTag_Constants::SEARCH_TYPE_TAG)
			{
				$search = $this->_doSearch($tagText);
			}
		}

		if ($search instanceof XenForo_ControllerResponse_Message)
		{
			return $search;
		}
		elseif (!is_array($search))
		{
			return $this->_getNoResultsResponse($tagText);
		}

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = XenForo_Application::get('options')->discussionsPerPage;

		if (Tinhte_XenTag_Option::get('searchForceUseCache') == true)
		{
			// force use cache, we can force redirect to correct link
			$this->canonicalizePageNumber($page, $perPage, $search['result_count'], 'tags', $tag);

			$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('tags', $tag, array('page' => $page)));
		}

		$pageResultIds = $searchModel->sliceSearchResultsToPage($search, $page, $perPage);
		$results = $searchModel->getSearchResultsForDisplay($pageResultIds);
		if (empty($results))
		{
			return $this->_getNoResultsResponse($tagText);
		}

		$resultStartOffset = ($page - 1) * $perPage + 1;
		$resultEndOffset = ($page - 1) * $perPage + count($results['results']);

		$ignoredNames = array();
		foreach ($results['results'] AS $result)
		{
			$content = $result['content'];
			if (!empty($content['isIgnored']) && !empty($content['user_id']) && !empty($content['username']))
			{
				$ignoredNames[$content['user_id']] = $content['username'];
			}
		}

		$linkParams = array();
		if (Tinhte_XenTag_Option::get('searchForceUseCache') == false)
		{
			// no force use cache, we need the search id in page links
			$linkParams[Tinhte_XenTag_Constants::SEARCH_SEARCH_ID] = $search['search_id'];
		}

		$viewParams = array(
			'tag' => $tag,
			'search' => $search,
			'results' => $results,

			'resultStartOffset' => $resultStartOffset,
			'resultEndOffset' => $resultEndOffset,

			'ignoredNames' => $ignoredNames,

			'page' => $page,
			'perPage' => $perPage,
			'totalResults' => $search['result_count'],
			'nextPage' => ($resultEndOffset < $search['result_count'] ? ($page + 1) : 0),
			'linkParams' => $linkParams,

			// since 1.4
			'canEdit' => XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_EDIT),
		);

		return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_View', 'tinhte_xentag_tag_view', $viewParams);
	}

	protected function _doSearch($tagText)
	{
		$visitorUserId = XenForo_Visitor::getUserId();

		/* @var $searchModel XenForo_Model_Search */
		$searchModel = $this->getModelFromCache('XenForo_Model_Search');

		/* @var $tagSearchModel Tinhte_XenTag_Model_Search */
		$tagSearchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');

		$input = array(
			'type' => Tinhte_XenTag_Constants::SEARCH_TYPE_TAG,
			'keywords' => '',
			Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS => $tagText,
			'order' => 'date',
			'group_discussion' => 0,
		);
		$constraints = $searchModel->getGeneralConstraintsFromInput($input, $errors);
		if ($errors)
		{
			return $this->responseError($errors);
		}

		$forceRefresh = $this->_input->filterSingle('force_refresh', XenForo_Input::UINT) > 0;
		if ($forceRefresh)
		{
			if (XenForo_Visitor::getInstance()->get('isTrusted'))
			{
				// good, this is a trusted user (admin or mod)
			}
			else
			{
				// do not accept force refresh request from not-trusted users
				$forceRefresh = false;
			}
		}

		if ($forceRefresh == false)
		{
			// force to use cache to have a nice and clean url
			$search = $searchModel->getExistingSearch($input['type'], $input['keywords'], $constraints, $input['order'], $input['group_discussion'], $visitorUserId, Tinhte_XenTag_Option::get('searchForceUseCache'));
		}
		else
		{
			// skip getting existing results, this will cause a real search to be made
			$search = false;
		}

		if (empty($search))
		{
			$searcher = new XenForo_Search_Searcher($searchModel);

			$typeHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_General');
			$results = $searcher->searchType($typeHandler, $input['keywords'], $constraints, $input['order']);

			if (empty($results))
			{
				return $this->_getNoResultsResponse($tagText);
			}
			else
			{
				$tagSearchModel->prioritizeResults($results, $searcher, $input['keywords'], $constraints, $input['order']);
			}

			$warnings = $searcher->getErrors() + $searcher->getWarnings();

			$search = $searchModel->insertSearch($results, $input['type'], $input['keywords'], $constraints, $input['order'], $input['group_discussion'], array(), $warnings, $visitorUserId);
		}

		return $search;
	}

	public function actionFind()
	{
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		if (!empty($q))
		{
			$tags = $this->_getTagModel()->getAllTag(array('tag_text_like' => array(
					$q,
					'r'
				)), array('limit' => 10));

			$tags = array_merge($tags, $this->_getTagModel()->getAllTag(array('tag_text_like' => array(
					' ' . $q,
					'lr'
				)), array('limit' => 10)));
		}
		else
		{
			$tags = array();
		}

		$viewParams = array('tags' => $tags);

		return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_Find', '', $viewParams);
	}

	protected function _getNoResultsResponse($tagText)
	{
		return $this->responseMessage(new XenForo_Phrase('tinhte_xentag_no_contents_has_been_found'), array(
			'navigation' => array( array(
					'href' => XenForo_Link::buildPublicLink('tags'),
					'value' => new XenForo_Phrase('tinhte_xentag_tags'),
				), ),
			'title' => $tagText,
		));
	}

	public function actionEdit()
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_EDIT))
		{
			return $this->responseNoPermission();
		}

		$tagText = $this->_input->filterSingle(Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT, XenForo_Input::STRING);
		if (empty($tagText))
		{
			return $this->responseNoPermission();
		}

		$tagModel = $this->_getTagModel();
		$tag = $tagModel->getTagByText($tagText);
		if (empty($tag))
		{
			return $this->responseNoPermission();
		}

		if ($this->isConfirmedPost())
		{
			$dwInput = $this->_input->filter(array('tag_description' => XenForo_Input::STRING, ));

			$dw = XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
			$dw->setExistingData($tag['tag_id']);
			$dw->bulkSet($dwInput);
			$dw->save();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('tags', $tag));
		}
		else
		{
			$viewParams = array('tag' => $tag, );

			return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_Edit', 'tinhte_xentag_tag_edit', $viewParams);
		}
	}

	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel()
	{
		return $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
		$output = array();
		foreach ($activities AS $key => $activity)
		{
			switch ($activity['controller_action'])
			{
				case 'View':
					if (!empty($activity['params'][Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT]))
					{
						$output[$key] = array(
							new XenForo_Phrase('tinhte_xentag_viewing_tag'),
							$activity['params'][Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT],
							XenForo_Link::buildPublicLink('canonical:tags', array('tag_text' => $activity['params'][Tinhte_XenTag_Constants::URI_PARAM_TAG_TEXT])),
							''
						);
					}
					else
					{
						$output[$key] = new XenForo_Phrase('tinhte_xentag_viewing_tags');
					}
					break;
				default:
					$output[$key] = new XenForo_Phrase('tinhte_xentag_viewing_tags');
					break;
			}
		}

		return $output;
	}

}

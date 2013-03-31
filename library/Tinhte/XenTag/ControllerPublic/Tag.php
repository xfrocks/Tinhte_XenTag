<?php

class Tinhte_XenTag_ControllerPublic_Tag extends XenForo_ControllerPublic_Abstract {
	public function actionIndex() {
		$tagText = $this->_input->filterSingle('tag_text', XenForo_Input::STRING);
		if (!empty($tagText)) {
			return $this->responseReroute(__CLASS__, 'view');
		}
		
		$tagModel = $this->_getTagModel();
		
		$conditions = array();
		$fetchOptions = array(
			'order' => 'content_count',
			'direction' => 'desc',
			'limit' => Tinhte_XenTag_Option::get('cloudMax'),
		);
		
		$tags = $tagModel->getAllTag($conditions, $fetchOptions);
		$tagModel->calculateCloudLevel($tags);
		
		$viewParams = array(
			'tags' => $tags,
		);
		
		return $this->responseView(
			'Tinhte_XenTag_ViewPublic_Tag_List',
			'tinhte_xentag_tag_list',
			$viewParams
		);
	}
	
	public function actionSearch() {
		$tags = $this->_getTagModel()->processInput($this->_input);
		
		if (empty($tags)) {
			// no tag?!
			return $this->_getNoResultsResponse($tags);
		} else if (count($tags) == 1) {
			// search for one tag only, redirect to view action
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink(Tinhte_XenTag_Option::get('routePrefix'), array('tag_text' => $tags[0]))
			);
		} else {
			$search = $this->_doSearch($tags);
		}
		
		if ($search instanceof XenForo_ControllerResponse_Message) {
			return $search;
		} elseif (!is_array($search)) {
			return $this->_getNoResultsResponse($tagText);
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('search', $search),
			''
		);
	}
	
	public function actionView() {
		$tagText = $this->_input->filterSingle('tag_text', XenForo_Input::STRING);
		if (empty($tagText)) {
			return $this->responseNoPermission();
		}
		
		$tagModel = $this->_getTagModel();
		
		/* @var $searchModel XenForo_Model_Search */
		$searchModel = $this->getModelFromCache('XenForo_Model_Search');
		
		/* @var $threadModel XenForo_Model_Thread */
		$threadModel = $this->getModelFromCache('XenForo_Model_Thread');
		
		$tag = $tagModel->getTagByText($tagText);
		if (empty($tag)) {
			return $this->_getNoResultsResponse($tagText);
		}
		
		$searchId = $this->_input->filterSingle(Tinhte_XenTag_Constants::SEARCH_SEARCH_ID, XenForo_Input::UINT);
		if (empty($searchId)) {
			$search = $this->_doSearch($tagText);
		} else {
			$search = $searchModel->getSearchById($searchId);
		
			if (empty($search)
				|| $search['user_id'] != XenForo_Visitor::getUserId()
				|| $search['search_type'] != Tinhte_XenTag_Constants::SEARCH_TYPE_TAG
			)
			{
				$search = $this->_doSearch($tagText);
			}
		}
		
		if ($search instanceof XenForo_ControllerResponse_Message) {
			return $search;
		} elseif (!is_array($search)) {
			return $this->_getNoResultsResponse($tagText);
		}
		
		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = XenForo_Application::get('options')->discussionsPerPage;
		
		if (Tinhte_XenTag_Option::get('searchForceUseCache') == true) {
			// force use cache, we can force redirect to correct link 
			$this->canonicalizePageNumber($page, $perPage, $search['result_count'], Tinhte_XenTag_Option::get('routePrefix'), $tag);

			$this->canonicalizeRequestUrl(
				XenForo_Link::buildPublicLink(Tinhte_XenTag_Option::get('routePrefix'), $tag, array('page' => $page))
			);
		}
		
		$pageResultIds = $searchModel->sliceSearchResultsToPage($search, $page, $perPage);
		$results = $searchModel->getSearchResultsForDisplay($pageResultIds);
		if (empty($results))
		{
			return $this->_getNoResultsResponse($tagText);
		}
		
		$threadStartOffset = ($page - 1) * $perPage + 1;
		$threadEndOffset = ($page - 1) * $perPage + count($results['results']);
		
		$threads = array();
		$inlineModOptions = array();
		foreach ($results['results'] AS $result) {
			$thread = $result['content'];

			$thread['forum'] = array(
				'node_id' => $thread['node_id'],
				'title' => $thread['node_title']
			);

			$threadModOptions = $threadModel->addInlineModOptionToThread($thread, $thread, $thread['permissions']);
			$inlineModOptions += $threadModOptions;

			$threads[$result[XenForo_Model_Search::CONTENT_ID]] = $thread;
		}
		
		$linkParams = array();
		if (Tinhte_XenTag_Option::get('searchForceUseCache') == false) {
			// no force use cache, we need the search id in page links
			$linkParams[Tinhte_XenTag_Constants::SEARCH_SEARCH_ID] = $search['search_id'];
		}
		
		$viewParams = array(
			'tag' => $tag,
			'search' => $search,
			'threads' => $threads,
			'inlineModOptions' => $inlineModOptions,

			'threadStartOffset' => $threadStartOffset,
			'threadEndOffset' => $threadEndOffset,

			'ignoredNames' => $this->_getIgnoredContentUserNames($threads),

			'page' => $page,
			'perPage' => $perPage,
			'totalThreads' => $search['result_count'],
			'nextPage' => ($threadEndOffset < $search['result_count'] ? ($page + 1) : 0),
			'linkParams' => $linkParams,
		);

		return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_View', 'tinhte_xentag_tag_view', $viewParams);
	}
	
	protected function _doSearch($tagText) {
		$visitorUserId = XenForo_Visitor::getUserId();
		
		/* @var $searchModel XenForo_Model_Search */
		$searchModel = $this->getModelFromCache('XenForo_Model_Search');
		
		$input = array(
			'type' => Tinhte_XenTag_Constants::SEARCH_TYPE_TAG,
			'keywords' => '',
			Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS => $tagText,
			'order' => 'date',
			'group_discussion' => 0,
		);
		$constraints = $searchModel->getGeneralConstraintsFromInput($input, $errors);
		if ($errors) {
			return $this->responseError($errors);
		}
		
		$constraints['content'] = 'thread'; // limit to threads only
		
		$search = $searchModel->getExistingSearch(
			$input['type'], $input['keywords'], $constraints, $input['order'], $input['group_discussion'], $visitorUserId,
			Tinhte_XenTag_Option::get('searchForceUseCache') /* force to use cache to have a nice and clean url */
		);
		
		if (empty($search)) {
			$searcher = new XenForo_Search_Searcher($searchModel);
			$results = $searcher->searchGeneral($input['keywords'], $constraints, $input['order']);
			
			if (empty($results)) {
				return $this->_getNoResultsResponse($tagText);
			}
			
			$warnings = $searcher->getErrors() + $searcher->getWarnings();
			
			$search = $searchModel->insertSearch(
				$results, $input['type'], $input['keywords'], $constraints, $input['order'], $input['group_discussion'], array(),
				$warnings, $visitorUserId
			);
		}
		
		return $search;
	}
	
	public function actionFind() {
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		if (!empty($q)) {
			$tags = $this->_getTagModel()->getAllTag(
				array('tag_text_like' => array($q , 'r')),
				array('limit' => 10)
			);
		} else {
			$tags = array();
		}

		$viewParams = array(
			'tags' => $tags
		);

		return $this->responseView(
			'Tinhte_XenTag_ViewPublic_Tag_Find',
			'',
			$viewParams
		);
	}
	
	protected function _getNoResultsResponse($tagText) {
		return $this->responseMessage(new XenForo_Phrase('tinhte_xentag_no_contents_has_been_found'));
	}
	
	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel() {
		return $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
	}
}
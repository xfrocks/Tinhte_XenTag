<?php

class Tinhte_XenTag_bdApi_ControllerApi_Search extends XFCP_Tinhte_XenTag_bdApi_ControllerApi_Search
{
    public function actionGetIndex()
    {
        $response = parent::actionGetIndex();

        if ($response instanceof XenForo_ControllerResponse_View
            && !empty($response->params['links'])
        ) {
            $response->params['links']['tagged'] = XenForo_Link::buildApiLink('search/tagged');
        }

        return $response;
    }

    public function actionGetTagged()
    {
        return $this->responseError(new XenForo_Phrase('bdapi_slash_search_only_accepts_post_requests'), 400);
    }

    public function actionPostTagged()
    {
        $tagText = $this->_input->filterSingle('tag', XenForo_Input::STRING);
        if (empty($tagText)) {
            return $this->responseError(new XenForo_Phrase('tinhte_xentag_tag_not_found'), 404);
        }

        /** @var Tinhte_XenTag_Model_Tag $tagModel */
        $tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
        $tag = $tagModel->getTagByText($tagText);
        if (empty($tag)) {
            return $this->responseError(new XenForo_Phrase('tinhte_xentag_tag_not_found'), 404);
        }

        $tagModel->logTagView($tag['tag_id']);

        /* @var $searchModel XenForo_Model_Search */
        $searchModel = $this->getModelFromCache('XenForo_Model_Search');
        $input = array(
            'type' => Tinhte_XenTag_Constants::SEARCH_TYPE_TAG,
            'keywords' => '',
            Tinhte_XenTag_Constants::SEARCH_INPUT_TAGS => $tagText,
            'order' => 'date',
            'group_discussion' => false,
        );
        $constraints = $searchModel->getGeneralConstraintsFromInput($input, $errors);
        if ($errors) {
            return $this->responseError($errors);
        }

        $searcher = new XenForo_Search_Searcher($searchModel);
        $typeHandler = XenForo_Search_DataHandler_Abstract::create('Tinhte_XenTag_Search_DataHandler_General');
        $results = $searcher->searchType($typeHandler, $input['keywords'], $constraints, $input['order']);

        if (!empty($results)) {
            /* @var $tagSearchModel Tinhte_XenTag_Model_Search */
            $tagSearchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');
            $tagSearchModel->prioritizeResults($results, $searcher, $input['keywords'], $constraints, $input['order']);
        }

        $search = $searchModel->insertSearch($results, $input['type'], $input['keywords'], $constraints, $input['order'], $input['group_discussion']);

        $this->_request->setParam('search_id', $search['search_id']);
        return $this->responseReroute(__CLASS__, 'get-results');
    }

}
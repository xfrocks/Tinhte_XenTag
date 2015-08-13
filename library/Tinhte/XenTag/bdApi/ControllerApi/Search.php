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

    public function actionGetResults()
    {
        $response = parent::actionGetResults();

        if ($response instanceof XenForo_ControllerResponse_View
            && !empty($response->params['_search'])
            && $response->params['_search']['search_type'] === Tinhte_XenTag_Constants::SEARCH_TYPE
        ) {
            $constraints = @json_decode($response->params['_search']['search_constraints'], true);
            if (!empty($constraints[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS])) {
                /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
                $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
                $tags = $tagModel->Tinhte_XenTag_getTagsByIds(explode(' ', $constraints[Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS]));

                $response->params['search_tags'] = $tagModel->Tinhte_XenTag_prepareApiDataForTags($tags);
            }
        }

        return $response;
    }

    public function actionPostTagged()
    {
        $tagText = $this->_input->filterSingle('tag', XenForo_Input::STRING);
        if (empty($tagText)) {
            return $this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404);
        }

        /** @var XenForo_Model_Tag $tagModel */
        $tagModel = $this->getModelFromCache('XenForo_Model_Tag');
        $tag = $tagModel->getTag($tagText);
        if (empty($tag)) {
            return $this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404);
        }

        /* @var $searchModel XenForo_Model_Search */
        $searchModel = $this->getModelFromCache('XenForo_Model_Search');
        $tagConstraint = $tag['tag_id'];
        $constraints = array(
            Tinhte_XenTag_Constants::SEARCH_CONSTRAINT_TAGS => $tagConstraint
        );

        $searcher = new XenForo_Search_Searcher($searchModel);
        $searchQuery = '';
        $order = 'date';
        $results = $searcher->searchGeneral($searchQuery, $constraints, $order);

        if (!empty($results)) {
            /* @var $tagSearchModel Tinhte_XenTag_Model_Search */
            $tagSearchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');
            $tagSearchModel->prioritizeResults($results, $searcher, $searchQuery, $constraints, $order);
        }

        $search = $searchModel->insertSearch($results, Tinhte_XenTag_Constants::SEARCH_TYPE, $searchQuery, $constraints, $order, false);

        $this->_request->setParam('search_id', $search['search_id']);
        return $this->responseReroute(__CLASS__, 'get-results');
    }

}
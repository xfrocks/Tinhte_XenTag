<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Tag extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Tag
{
    public function actionIndex()
    {
        if ($tagText = $this->_input->filterSingle('t', XenForo_Input::STRING)) {
            // we have to use the `t` (text) parameter because XenForo allows the tag_url to be changed
            // so there is no reliable way to guess the correct url from the tag text within various
            // contexts that tag links are generated
            // TODO: double check to avoid conflict with some XenForo parameter (none as of 2015-08-26)
            $tag = $this->_getTagModel()->getTag($tagText);
            if (!empty($tag)) {
                return $this->responseRedirect(
                    XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    XenForo_Link::buildPublicLink('tags', $tag)
                );
            }
        }

        return parent::actionIndex();
    }

    public function actionTag()
    {
        $response = parent::actionTag();

        if ($response instanceof XenForo_ControllerResponse_View
            && !empty($response->params['tag'])
        ) {
            /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
            $tagModel = $this->_getTagModel();
            $response->params['Tinhte_XenTag_canWatch'] = $tagModel->Tinhte_XenTag_canWatchTag($response->params['tag']);

            if ($response->params['Tinhte_XenTag_canWatch']) {
                /** @var Tinhte_XenTag_Model_TagWatch $tagWatchModel */
                $tagWatchModel = $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
                $response->params['Tinhte_XenTag_tagIsWatched'] = $tagWatchModel->getUserTagWatchByUserIdAndTagIds(
                    XenForo_Visitor::getUserId(),
                    array($response->params['tag']['tag_id'])
                );
            }
        }

        return $response;
    }

    public function actionWatchConfirm()
    {
        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $this->_getTagModel();

        $tagUrl = $this->_input->filterSingle('tag_url', XenForo_Input::STRING);
        $tag = $tagModel->getTagByUrl($tagUrl);
        if (!$tag) {
            return $this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404);
        }

        if (!$tagModel->Tinhte_XenTag_canWatchTag($tag, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        /** @var Tinhte_XenTag_Model_TagWatch $tagWatchModel */
        $tagWatchModel = $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
        $tagWatch = $tagWatchModel->getUserTagWatchByIds(XenForo_Visitor::getUserId(), $tag['tag_id']);

        $viewParams = array(
            'tag' => $tag,
            'tagWatch' => $tagWatch,
        );

        return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_WatchConfirm', 'tinhte_xentag_tag_watch', $viewParams);
    }

    public function actionWatch()
    {
        $this->_assertPostOnly();

        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $this->_getTagModel();

        $tagUrl = $this->_input->filterSingle('tag_url', XenForo_Input::STRING);
        $tag = $tagModel->getTagByUrl($tagUrl);
        if (!$tag) {
            return $this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404);
        }

        if (!$tagModel->Tinhte_XenTag_canWatchTag($tag, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        if ($this->_input->filterSingle('stop', XenForo_Input::STRING)) {
            $sendAlert = null;
            $sendEmail = null;
            $linkPhrase = new XenForo_Phrase('tinhte_xentag_watch_tag');
        } else {
            $sendAlert = $this->_input->filterSingle('send_alert', XenForo_Input::BOOLEAN);
            $sendEmail = $this->_input->filterSingle('send_email', XenForo_Input::BOOLEAN);
            $linkPhrase = new XenForo_Phrase('tinhte_xentag_unwatch_tag');
        }

        /** @var Tinhte_XenTag_Model_TagWatch $tagWatchModel */
        $tagWatchModel = $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
        $tagWatchModel->setTagWatchState(XenForo_Visitor::getUserId(), $tag['tag_id'], $sendAlert, $sendEmail);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('tags', $tag),
            null,
            array('linkPhrase' => $linkPhrase)
        );
    }
}
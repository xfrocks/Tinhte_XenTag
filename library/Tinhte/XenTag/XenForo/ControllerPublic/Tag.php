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

        if ($tagUrl = $this->_input->filterSingle('tag_url', XenForo_Input::STRING)) {
            if (strpos($tagUrl, ' ') !== false) {
                // older versions support spaces in the tag url (e.g. /tags/foo+bar)
                // we have to check for them here and make a 301 redirect to avoid SEO impact
                $tag = $this->_getTagModel()->getTag($tagUrl);
                if (!empty($tag)) {
                    return $this->responseRedirect(
                        XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                        XenForo_Link::buildPublicLink('tags', $tag)
                    );
                }
            }
        }

        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
            $tagModel = $this->_getTagModel();
            $trendingTags = $tagModel->Tinhte_XenTag_getTrendingFromCache();
            $trendingTagsLevels = $tagModel->getTagCloudLevels($trendingTags);

            $response->params['Tinhte_XenTag_trendingTags'] = $trendingTags;
            $response->params['Tinhte_XenTag_trendingTagsLevels'] = $trendingTagsLevels;
        }

        return $response;
    }

    public function actionTag()
    {
        if ($this->_input->filterSingle('preview', XenForo_Input::BOOLEAN)) {
            return $this->responseReroute('XenForo_ControllerPublic_Tag', 'preview');
        }

        $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_TAG_TAG] = $this;

        $response = parent::actionTag();

        if ($response instanceof XenForo_ControllerResponse_View
            && !empty($response->params['tag'])
        ) {
            /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
            $tagModel = $this->_getTagModel();
            $response->params['Tinhte_XenTag_canWatch'] = $tagModel->Tinhte_XenTag_canWatchTag($response->params['tag']);
            $response->params['Tinhte_XenTag_canEdit'] = $tagModel->Tinhte_XenTag_canEditTag($response->params['tag']);

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

    public function Tinhte_XenTag_actionTag($tagUrl, $tag)
    {
        if (empty($tag)) {
            return;
        }

        if ($this->_input->filterSingle('tag_url', XenForo_Input::STRING) !== $tagUrl
            || $this->_noRedirect()
        ) {
            return;
        }

        if (!empty($tag['tinhte_xentag_url'])) {
            throw $this->responseException($this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                $tag['tinhte_xentag_url']
            ));
        }
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

    public function actionEdit()
    {
        /** @var Tinhte_XenTag_XenForo_Model_Tag $tagModel */
        $tagModel = $this->_getTagModel();

        $tagUrl = $this->_input->filterSingle('tag_url', XenForo_Input::STRING);
        $tag = $tagModel->getTagByUrl($tagUrl);
        if (!$tag) {
            return $this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404);
        }

        if (!$tagModel->Tinhte_XenTag_canEditTag($tag, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        if ($this->isConfirmedPost()) {
            $dwData = $this->_input->filter(array(
                'tinhte_xentag_title' => XenForo_Input::STRING,
                'tinhte_xentag_description' => XenForo_Input::STRING,
                'tinhte_xentag_url' => XenForo_Input::STRING,
            ));

            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Tag');
            $dw->setExistingData($tag, true);
            $dw->bulkSet($dwData);
            $dw->save();

            $extraParams = array();
            if (!!$dw->get('tinhte_xentag_url')) {
                $extraParams['_xfNoRedirect'] = 1;
            }

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('tags', $tag, $extraParams)
            );
        }

        $viewParams = array(
            'tag' => $tag,
        );

        return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_Edit', 'tinhte_xentag_tag_edit', $viewParams);
    }

    public function actionPreview()
    {
        $tagModel = $this->_getTagModel();

        $tagUrl = $this->_input->filterSingle('tag_url', XenForo_Input::STRING);
        $tag = $tagModel->getTagByUrl($tagUrl);
        if (!$tag) {
            return $this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404);
        }

        $viewParams = array(
            'tag' => $tag,
        );
        return $this->responseView('Tinhte_XenTag_ViewPublic_Tag_Preview', 'tinhte_xentag_tag_preview', $viewParams);
    }
}
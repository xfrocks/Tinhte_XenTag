<?php

class Tinhte_XenTag_bdApi_ControllerApi_Tag extends XFCP_Tinhte_XenTag_bdApi_ControllerApi_Tag
{
    public function actionGetFollowers()
    {
        $tag = $this->_Tinhte_XenTag_assertTagValidAndWatchable();

        $users = array();

        if ($this->_Tinhte_XenTag_getTagModel()->Tinhte_XenTag_canWatchTag($tag)) {
            $visitor = XenForo_Visitor::getInstance();
            $tagWatchModel = $this->_Tinhte_XenTag_getTagWatchModel();
            $tagWatch = $tagWatchModel->getUserTagWatchByIds($visitor['user_id'], $tag['tag_id']);

            if (!empty($tagWatch)) {
                $user = array(
                    'user_id' => $visitor['user_id'],
                    'username' => $visitor['username'],
                );

                $user = $tagWatchModel->prepareApiDataForTagWatch($user, $tagWatch);

                $users[] = $user;
            }
        }

        $data = array('users' => $this->_filterDataMany($users));

        return $this->responseData('Tinhte_XenTag_ViewApi_Tag_Followers', $data);
    }

    public function actionPostFollowers()
    {
        $sendAlert = $this->_input->filterSingle('alert', XenForo_Input::UINT, array('default' => 1));
        $sendEmail = $this->_input->filterSingle('email', XenForo_Input::UINT);

        $tag = $this->_Tinhte_XenTag_assertTagValidAndWatchable();

        if (!$this->_Tinhte_XenTag_getTagModel()->Tinhte_XenTag_canWatchTag($tag, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        $this->_Tinhte_XenTag_getTagWatchModel()->setTagWatchState(XenForo_Visitor::getUserId(),
            $tag['tag_id'], $sendAlert, $sendEmail);

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    public function actionDeleteFollowers()
    {
        $tag = $this->_Tinhte_XenTag_assertTagValidAndWatchable();

        $this->_Tinhte_XenTag_getTagWatchModel()->setTagWatchState(XenForo_Visitor::getUserId(),
            $tag['tag_id'], null, null);

        return $this->responseMessage(new XenForo_Phrase('changes_saved'));
    }

    protected function _Tinhte_XenTag_assertTagValidAndWatchable()
    {
        $tag = null;

        $tagId = $this->_input->filterSingle('tag_id', XenForo_Input::UINT);
        if (!empty($tagId)) {
            $tag = $this->_Tinhte_XenTag_getTagModel()->getTagById($tagId);
        } else {
            $tagText = $this->_input->filterSingle('t', XenForo_Input::STRING);
            if (!empty($tagText)) {
                $tag = $this->_Tinhte_XenTag_getTagModel()->getTag($tagText);
            }
        }

        if (!$tag) {
            throw $this->responseException($this->responseError(new XenForo_Phrase('requested_tag_not_found'), 404));
        }

        return $tag;
    }

    /**
     * @return Tinhte_XenTag_XenForo_Model_Tag
     */
    protected function _Tinhte_XenTag_getTagModel()
    {
        return $this->getModelFromCache('XenForo_Model_Tag');
    }

    /**
     * @return Tinhte_XenTag_Model_TagWatch
     */
    protected function _Tinhte_XenTag_getTagWatchModel()
    {
        return $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
    }
}
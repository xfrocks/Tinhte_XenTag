<?php

class Tinhte_XenTag_XenForo_ControllerPublic_Misc extends XFCP_Tinhte_XenTag_XenForo_ControllerPublic_Misc
{
    public function actionTagAutoComplete()
    {
        $response = parent::actionTagAutoComplete();

        if ($response instanceof XenForo_ControllerResponse_View
            && !empty($response->jsonParams['results'])
            && !empty($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_STAFF_TAGS_DURING_AC])
        ) {
            $tagsRef =& $response->jsonParams['results'];
            foreach ($tagsRef as &$tagRef) {
                if (!is_string($tagRef)) {
                    continue;
                }

                $isStaffTag = false;
                foreach ($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_STAFF_TAGS_DURING_AC] as $staffTag) {
                    if ($staffTag['tag'] === $tagRef) {
                        $isStaffTag = true;
                        break;
                    }
                }

                if ($isStaffTag) {
                    $tagRef = new XenForo_Phrase('tinhte_xentag_auto_complete_x_is_staff_tag',
                        array('tag' => $tagRef));
                }
            }
        }

        return $response;
    }

}
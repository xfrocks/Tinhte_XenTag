<?php

class Tinhte_XenTag_XenResource_ControllerPublic_Resource extends XFCP_Tinhte_XenTag_XenResource_ControllerPublic_Resource
{

	protected function _getResourceAddOrEditResponse(array $resource, array $category, array $attachments = array())
	{
		$response = parent::_getResourceAddOrEditResponse($resource, $category, $attachments);

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			/* @var $tagModel Tinhte_XenTag_Model_Tag */
			$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

			$response->params['Tinhte_XenTag_canTag'] = $tagModel->canTagResource($resource);
		}

		return $response;
	}

	public function actionSave()
	{
		// register this controller and let's the parent work its job
		// we will get called again from
		// Tinhte_XenTag_XenResource_DataWriter_Resource::_preSave()
		$GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_RESOURCE_SAVE] = $this;

		return parent::actionSave();
	}

	public function Tinhte_XenTag_actionSave(XenResource_DataWriter_Resource $dw)
	{
		/* @var $tagModel Tinhte_XenTag_Model_Tag */
		$tagModel = $this->getModelFromCache('Tinhte_XenTag_Model_Tag');

		$resource = $dw->getMergedData();

		if ($tagModel->canTagResource($resource))
		{
			$tags = $tagModel->processInput($this->_input);

			if ($tags !== false)
			{
				$dw->Tinhte_XenTag_setTags($tags);
			}
		}

		// just to be safe...
		unset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_RESOURCE_SAVE]);
	}

}

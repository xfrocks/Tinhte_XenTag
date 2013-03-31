<?php

/* Start auto-generated lines of code. Change made will be overwriten... */

class Tinhte_XenTag_ControllerAdmin_Tag_Generated extends XenForo_ControllerAdmin_Abstract {

	public function actionIndex() {
		$model = $this->_getTagModel();
		$allTag = $model->getAllTag();
		
		$viewParams = array(
			'allTag' => $allTag
		);
		
		return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_List', 'tinhte_xentag_tag_list', $viewParams);
	}
	
	public function actionAdd() {
		$viewParams = array(
			'tag' => array(),
			
		);
		
		return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_Edit', 'tinhte_xentag_tag_edit', $viewParams);
	}
	
	public function actionEdit() {
		$id = $this->_input->filterSingle('tag_id', XenForo_Input::UINT);
		$tag = $this->_getTagOrError($id);
		
		$viewParams = array(
			'tag' => $tag,
			
		);
		
		return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_Edit', 'tinhte_xentag_tag_edit', $viewParams);
	}
	
	public function actionSave() {
		$this->_assertPostOnly();
		
		$id = $this->_input->filterSingle('tag_id', XenForo_Input::UINT);

		$dwInput = $this->_input->filter(array('tag_text' => 'string', 'created_date' => 'uint', 'created_user_id' => 'uint'));
		
		$dw = $this->_getTagDataWriter();
		if ($id) {
			$dw->setExistingData($id);
		}
		$dw->bulkSet($dwInput);
		

		
		$this->_prepareDwBeforeSaving($dw);
		
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('xentag-tags')
		);
	}
	
	public function actionDelete() {
		$id = $this->_input->filterSingle('tag_id', XenForo_Input::UINT);
		$tag = $this->_getTagOrError($id);
		
		if ($this->isConfirmedPost()) {
			$dw = $this->_getTagDataWriter();
			$dw->setExistingData($id);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('xentag-tags')
			);
		} else {
			$viewParams = array(
				'tag' => $tag
			);

			return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_Delete', 'tinhte_xentag_tag_delete', $viewParams);
		}
	}
	
	
	protected function _getTagOrError($id, array $fetchOptions = array()) {
		$info = $this->_getTagModel()->getTagById($id, $fetchOptions);
		
		if (empty($info)) {
			throw $this->responseException($this->responseError(new XenForo_Phrase('tinhte_xentag_tag_not_found'), 404));
		}
		
		return $info;
	}
	
	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel() {
		return $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
	}
	
	/**
	 * @return Tinhte_XenTag_DataWriter_Tag
	 */
	protected function _getTagDataWriter() {
		return XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
	}
	
	protected function _prepareDwBeforeSaving(Tinhte_XenTag_DataWriter_Tag $dw) {
		// this method should be overriden if datawriter requires special treatments
	}
}

/* End auto-generated lines of code. Feel free to make changes below */

class Tinhte_XenTag_ControllerAdmin_Tag extends Tinhte_XenTag_ControllerAdmin_Tag_Generated {
	// customized actions and whatelse should go here
}
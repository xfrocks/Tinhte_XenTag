<?php

class Tinhte_XenTag_ControllerAdmin_Tag extends XenForo_ControllerAdmin_Abstract
{

	protected function _preDispatch($action)
	{
		$this->assertAdminPermission(Tinhte_XenTag_Constants::PERM_ADMIN_MANAGE);
	}

	public function actionIndex()
	{
		$model = $this->_getTagModel();

		$conditions = array();
		$fetchOptions = array('order' => 'tag_text', );

		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = Tinhte_XenTag_Option::get('perPage');
		$fetchOptions['page'] = $page;
		$fetchOptions['limit'] = $perPage;

		$filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
		if ($filter && isset($filter['value']))
		{
			$conditions['tag_text_like'] = array(
				$filter['value'],
				empty($filter['prefix']) ? 'lr' : 'r'
			);
			$filterView = true;
		}
		else
		{
			$filterView = false;
		}

		$total = $model->countAllTag($conditions, $fetchOptions);
		$tags = $model->getAllTag($conditions, $fetchOptions);

		$viewParams = array(
			'tags' => $tags,

			'page' => $page,
			'perPage' => $perPage,
			'total' => $total,

			'filterView' => $filterView,
			'filterMore' => ($filterView && $total > $perPage)
		);

		return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_List', 'tinhte_xentag_tag_list', $viewParams);
	}

	public function actionDelete()
	{
		$id = $this->_input->filterSingle('tag_id', XenForo_Input::UINT);
		$tag = $this->_getTagOrError($id);

		if ($this->isConfirmedPost())
		{
			XenForo_Db::beginTransaction();

			$dw = $this->_getTagDataWriter();
			$dw->setExistingData($id);
			$dw->delete();

			XenForo_Db::commit();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('xentag-tags'));
		}
		else
		{
			$viewParams = array('tag' => $tag);

			return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_Delete', 'tinhte_xentag_tag_delete', $viewParams);
		}
	}

	public function actionDeleteEmptyTags()
	{
		if ($this->isConfirmedPost())
		{
			$this->_getTagModel()->deleteEmptyTags();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('xentag-tags'));
		}
		else
		{
			return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_DeleteEmptyTags', 'tinhte_xentag_delete_empty_tags');
		}
	}

	public function actionEdit()
	{
		$id = $this->_input->filterSingle('tag_id', XenForo_Input::UINT);
		$tag = $this->_getTagOrError($id);

		if ($this->isConfirmedPost())
		{
			$dwInput = $this->_input->filter(array(
				'tag_text' => XenForo_Input::STRING,
				'tag_description' => XenForo_Input::STRING,
				'is_staff' => XenForo_Input::UINT,
			));

			$dw = $this->_getTagDataWriter();
			$dw->setExistingData($id);
			$dw->bulkSet($dwInput);

			// process link target_type
			// since 1.8
			$link = $this->_input->filterSingle('link', XenForo_Input::STRING);
			if (!empty($link))
			{
				$existingLink = $this->_getTagModel()->getTagLink($tag);
				if ($link != $existingLink)
				{
					$dw->bulkSet(array(
						'target_type' => 'link',
						'target_id' => 0,
						'target_data' => array('link' => $link),
					));
				}
			}
			else
			{
				$dw->bulkSet(array(
					'target_type' => '',
					'target_id' => 0,
					'target_data' => array(),
				));
			}

			$dw->save();

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('xentag-tags') . '#' . XenForo_Template_Helper_Core::callHelper('listitemid', array($tag['tag_id'])));
		}
		else
		{
			$viewParams = array(
				'tag' => $tag,
				'tagLink' => $this->_getTagModel()->getTagLink($tag),
			);

			return $this->responseView('Tinhte_XenTag_ViewAdmin_Tag_Edit', 'tinhte_xentag_tag_edit', $viewParams);
		}
	}

	protected function _getTagOrError($id, array $fetchOptions = array())
	{
		$info = $this->_getTagModel()->getTagById($id, $fetchOptions);

		if (empty($info))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('tinhte_xentag_tag_not_found'), 404));
		}

		return $info;
	}

	/**
	 * @return Tinhte_XenTag_Model_Tag
	 */
	protected function _getTagModel()
	{
		return $this->getModelFromCache('Tinhte_XenTag_Model_Tag');
	}

	/**
	 * @return Tinhte_XenTag_DataWriter_Tag
	 */
	protected function _getTagDataWriter()
	{
		return XenForo_DataWriter::create('Tinhte_XenTag_DataWriter_Tag');
	}

}

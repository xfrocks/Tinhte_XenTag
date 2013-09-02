<?php

class Tinhte_XenTag_XenForo_Model_ThreadRedirect extends XFCP_Tinhte_XenTag_XenForo_Model_ThreadRedirect
{

	public function createRedirectThread($targetUrl, array $newThread, $redirectKey = '', $expiryDate = 0)
	{
		// we have to manually fix our field in the thread array
		// because if it's empty, the data writer will complain (in a very bad way)
		// this one could be a bug in XenForo...
		if (empty($newThread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS]))
		{
			$newThread[Tinhte_XenTag_Constants::FIELD_THREAD_TAGS] = false;
		}

		return parent::createRedirectThread($targetUrl, $newThread, $redirectKey, $expiryDate);
	}

}

<?php

class Tinhte_XenTag_XenForo_Model_Post extends XFCP_Tinhte_XenTag_XenForo_Model_Post
{

	protected static $_threads = array(
		/* $postId => array(
		 * 	'thread_id' => 1
		 *  'node_id' => 2
		 *  ...
		 * ) */
	);

	public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
		$post = parent::preparePost($post, $thread, $forum, $nodePermissions, $viewingUser);

		// keep the thread info of a post so we can use it later...
		self::$_threads[$post['post_id']] = $thread;

		return $post;
	}

	public function Tinhte_XenTag_getThread($postId)
	{
		if (isset(self::$_threads[$postId]))
		{
			return self::$_threads[$postId];
		}
		else
		{
			return false;
		}
	}

}

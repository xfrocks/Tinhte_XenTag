<?php

class Tinhte_XenTag_XenForo_Html_Renderer_BbCode extends XFCP_Tinhte_XenTag_XenForo_Html_Renderer_BbCode
{
	public function preFilter($html)
	{
		$offset = 0;
		while (true)
		{
			if (preg_match('/<span class="Tinhte_XenTag_HashTag"[^>]*>(#.+?)<\/span>/', $html, $matches, PREG_OFFSET_CAPTURE, $offset))
			{
				$thisText = $matches[0][0];
				$thisOffset = $matches[0][1];
				$hashtag = $matches[1][0];

				$replacement = sprintf('[HASHTAG]%s[/HASHTAG]', strip_tags($hashtag));

				$html = substr_replace($html, $replacement, $thisOffset, strlen($thisText));

				$offset = $thisOffset + 1;
			}
			else
			{
				break;
			}
		}

		return $html;
	}

}

<?php

class Tinhte_XenTag_BbCode_Formatter_AutoHashtag extends XFCP_Tinhte_XenTag_BbCode_Formatter_AutoHashtag
{
	protected $_Tinhte_XenTag_autoHashtagTexts = array();

	public function Tinhte_XenTag_getAutoHashtagTexts()
	{
		return $this->_Tinhte_XenTag_autoHashtagTexts;
	}

	public function filterString($string, array $rendererStates)
	{
		$isString = true;

		if (is_array($string))
		{
			// array is our way of marking tag originals prepend/append text
			$isString = false;
			$string = reset($string);
		}

		if ($isString)
		{
			// checks if the string is an URI of some kind
			$isString = !(Zend_Uri::check($string));
		}

		if ($isString)
		{
			// checks if the parent tag has some strange requirements
			$tagDataStack = $rendererStates['tagDataStack'];
			$lastTagDataStack = array_pop($tagDataStack);

			if ($lastTagDataStack['tag'] === 'hashtag')
			{
				// well, this is a hashtag already...
				$isString = false;
			}
			else
			{
				$parentTagInfo = $this->_parent__getTagRule($lastTagDataStack['tag']);

				if (!empty($parentTagInfo['plainChildren']))
				{
					// parent tag asks for plain children, we should do nothing
					$isString = false;
				}
				elseif (!empty($parentTagInfo['stopSmilies']) OR !empty($parentTagInfo['stopLineBreakConversion']))
				{
					// parent tag asks for some functionalities disabled, we should disable ourself
					// too
					$isString = false;
				}
			}
		}

		if ($isString)
		{
			$offset = 0;
			while (true)
			{
				$pos = strpos($string, '#', $offset);

				if ($pos === false)
				{
					break;
				}

				if (preg_match('/[^#a-zA-Z0-9]/', $string, $matches, PREG_OFFSET_CAPTURE, $pos))
				{
					$nonTagTextPos = $matches[0][1];
				}
				else
				{
					// get all of the remaining characters
					$nonTagTextPos = strlen($string);
				}

				$tagText = trim(substr($string, $pos + 1, $nonTagTextPos - 1 - $pos));

				if (!empty($tagText))
				{
					$this->_Tinhte_XenTag_autoHashtagTexts[Tinhte_XenTag_Helper::getSafeTagTextForSearch($tagText)] = $tagText;

					// add bb code wrapping
					$replacement = sprintf('[HASHTAG]#%s[/HASHTAG]', $tagText);

					$string = substr_replace($string, $replacement, $pos, $nonTagTextPos - $pos);
					$pos += strlen($replacement) - 1;
				}

				$offset = $pos + 1;
			}
		}

		return parent::filterString($string, $rendererStates);
	}

	public function renderTagUnparsed(array $tag, array $rendererStates)
	{
		if (!empty($tag['original']) && is_array($tag['original']))
		{
			list($prepend, $append) = $tag['original'];

			$prepend = array($prepend);
			$append = array($append);

			$tag['original'] = array(
				$prepend,
				$append
			);
		}

		return parent::renderTagUnparsed($tag, $rendererStates);
	}

	public function renderTree(array $tree, array $extraStates = array())
	{
		$this->_Tinhte_XenTag_autoHashtagTexts = array();

		return parent::renderTree($tree, $extraStates);
	}

	protected function _getTagRule($tagName)
	{
		if ($tagName === 'hashtag')
		{
			return parent::_getTagRule($tagName);
		}

		return false;
	}

	private function _parent__getTagRule($tagName)
	{
		return parent::_getTagRule($tagName);
	}

}

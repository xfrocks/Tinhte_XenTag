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
				$pos = utf8_strpos($string, '#', $offset);

				if ($pos === false)
				{
					break;
				}
				$offset = $pos + 1;

				if ($pos > 0)
				{
					$beforeTagText = utf8_substr($string, $pos - 1, 1);
					if (!preg_match(Tinhte_XenTag_Integration::REGEX_VALID_CHARACTER_AROUND, $beforeTagText))
					{
						// the before character of tag text is not a valid character, dismiss the found
						// tag text
						continue;
					}
				}

				$stringForPregMatch = utf8_substr($string, $pos + 1);
				if (preg_match('/[^a-zA-Z0-9]/', $stringForPregMatch, $matches, PREG_OFFSET_CAPTURE))
				{
					$nonTagTextPos = $matches[0][1];
				}
				else
				{
					// get all of the remaining characters
					$nonTagTextPos = utf8_strlen($stringForPregMatch);
				}
				$nonTagTextPos += $pos + 1;

				$tagText = utf8_trim(utf8_substr($string, $pos + 1, $nonTagTextPos - 1 - $pos));

				if (utf8_strlen($tagText) < Tinhte_XenTag_Option::get('tagMinLength'))
				{
					// too short
					continue;
				}

				$afterTagText = utf8_substr($string, $nonTagTextPos, 1);
				if (!empty($afterTagText))
				{
					if (!preg_match(Tinhte_XenTag_Integration::REGEX_VALID_CHARACTER_AROUND, $afterTagText))
					{
						// the after character of tag text is not a valid character, dismiss the found
						// tag text
						$tagText = '';
					}
				}

				if (!empty($tagText))
				{
					$this->_Tinhte_XenTag_autoHashtagTexts[Tinhte_XenTag_Helper::getSafeTagTextForSearch($tagText)] = $tagText;

					// add bb code wrapping
					$replacement = sprintf('[HASHTAG]#%s[/HASHTAG]', $tagText);

					$string = utf8_substr_replace($string, $replacement, $pos, $nonTagTextPos - $pos);
					$pos += utf8_strlen($replacement) - 1;
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
		else
		{
			$tagRule = parent::_getTagRule($tagName);

			if (!empty($tagRule['plainChildren']) OR !empty($tagRule['stopSmilies']) OR !empty($tagRule['stopLineBreakConversion']))
			{
				return array(
					'plainChildren' => true,
					'callback' => array(
						$this,
						'renderTagUnparsed'
					)
				);
			}
		}

		return false;
	}

	private function _parent__getTagRule($tagName)
	{
		return parent::_getTagRule($tagName);
	}

}

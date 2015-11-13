<?php

/**
 * Integration class with helper method to integrate other systems with XenTag.
 * Other developers should only use methods in this class when they need to
 * integrate something. For adventurous people, they can of course dig into
 * other scripts to provide further integration. However, XenTag itself only
 * uses below methods to integrate with XenForo.
 *
 * @author sondh
 *
 */
class Tinhte_XenTag_Integration
{
    /**
     * @return XenForo_Model_Tag
     */
    public static function getTagModel()
    {
        static $model = null;

        if ($model === null) {
            $model = XenForo_Model::create('XenForo_Model_Tag');
        }

        return $model;
    }

    /**************************************************
     * auto tagging
     **************************************************/

    const REGEX_VALID_CHARACTER_AROUND = '/[\s\(\)\.,!\?:;@\\\\\[\]{}"&<>]/u';

    /**
     * Inserts tag links into an HTML-formatted text.
     *
     * @param string $html
     * @param array $tags
     * @param array $options
     * @return string
     */
    public static function autoTag($html, array $tags, array &$options = array())
    {
        if (empty($tags)) {
            return $html;
        }

        $html = strval($html);
        $htmlNullified = utf8_strtolower($html);
        $htmlNullified = preg_replace_callback('#<a[^>]+>.+?</a>#', array(__CLASS__, '_autoTag_nullifyHtmlCallback'), $htmlNullified);
        $htmlNullified = preg_replace_callback('#<[^>]+>#', array(__CLASS__, '_autoTag_nullifyHtmlCallback'), $htmlNullified);

        // prepare the options
        $onceOnly = empty($options['onceOnly']) ? false : true;
        $options['autoTagged'] = array();
        // reset this

        // sort tags with the longest one first
        // since 1.0.3
        usort($tags, array(
            __CLASS__,
            '_autoTag_sortTagsByLength'
        ));

        foreach ($tags as $tag) {
            $offset = 0;
            $tagText = utf8_strtolower($tag['tag']);
            $tagLength = utf8_strlen($tagText);

            while (true) {
                $pos = utf8_strpos($htmlNullified, $tagText, $offset);

                if ($pos !== false) {
                    // the tag has been found
                    if (self::_autoTag_hasValidCharacterAround($html, $pos, $tagText)) {
                        // and it has good surrounding characters
                        // start replacing
                        $displayText = utf8_substr($html, $pos, $tagLength);

                        $template = new XenForo_Template_Public('tinhte_xentag_bb_code_tag_tag');
                        $template->setParam('tag', $tag);
                        $template->setParam('displayText', $displayText);
                        $replacement = $template->render();

                        if (strlen($replacement) === 0) {
                            // in case template system hasn't been initialized
                            $replacement = sprintf('<a href="%s">%s</a>', XenForo_Link::buildPublicLink('tags', $tag), $displayText);
                        }

                        $html = utf8_substr_replace($html, $replacement, $pos, $tagLength);
                        $htmlNullified = utf8_substr_replace($htmlNullified, str_repeat('_', utf8_strlen($replacement)), $pos, $tagLength);

                        // sondh@2012-09-20
                        // keep track of the auto tagged tags
                        $options['autoTagged'][$tagText][$pos] = $replacement;

                        $offset = $pos + utf8_strlen($replacement);

                        if ($onceOnly) {
                            // auto link only once per tag
                            // break the loop now
                            break;
                            // while (true)
                        }
                    } else {
                        $offset = $pos + $tagLength;
                    }
                } else {
                    // no match has been found, stop working with this tag
                    break;
                    // while (true)
                }
            }
        }

        return $html;
    }

    protected static function _autoTag_hasValidCharacterAround($html, $position, $tagText)
    {
        $pos = $position + utf8_strlen($tagText);
        $htmlLength = utf8_strlen($html);

        if ($pos >= $htmlLength) {
            // the found position is at the end of the html
            // no character afterward so... it's valid
        } else {
            if (!preg_match(self::REGEX_VALID_CHARACTER_AROUND, utf8_substr($html, $pos, 1))) {
                return false;
            }
        }

        // sondh@2012-09-12
        // check for the previous character too
        $pos = $position - 1;
        if ($pos < 0) {
            // the found position is at the start of the html
        } else {
            if (!preg_match(self::REGEX_VALID_CHARACTER_AROUND, utf8_substr($html, $pos, 1))) {
                return false;
            }
        }

        return true;
    }

    protected static function _autoTag_nullifyHtmlCallback($matches)
    {
        return str_repeat(' ', utf8_strlen($matches[0]));
    }

    protected static function _autoTag_sortTagsByLength(array $tag1, array $tag2)
    {
        return utf8_strlen($tag1['tag']) < utf8_strlen($tag2['tag']);
    }

    /**************************************************
     * emails & alerts
     **************************************************/

    protected static $_emailed = array();
    protected static $_alerted = array();

    /**
     * @param $contentType
     * @param $contentId
     * @return array
     */
    public static function getNoEmailAndAlert($contentType, $contentId)
    {
        if (!empty(self::$_emailed[$contentType][$contentId])) {
            $noEmail = self::$_emailed[$contentType][$contentId];
        } else {
            $noEmail = array();
        }

        if (!empty(self::$_alerted[$contentType][$contentId])) {
            $noAlert = self::$_alerted[$contentType][$contentId];
        } else {
            $noAlert = array();
        }

        return array(
            $noEmail,
            $noAlert
        );
    }

    /**
     * @param $contentType
     * @param $contentId
     * @param $emailed
     * @param $alerted
     */
    public static function updateNoEmailAndAlert($contentType, $contentId, $emailed, $alerted)
    {
        if (empty(self::$_emailed[$contentType][$contentId])) {
            self::$_emailed[$contentType][$contentId] = array();
        }
        $noEmail = &self::$_emailed[$contentType][$contentId];

        if (empty(self::$_alerted[$contentType][$contentId])) {
            self::$_alerted[$contentType][$contentId] = array();
        }
        $noAlert = &self::$_alerted[$contentType][$contentId];

        foreach ($emailed as $userId) {
            $noEmail[] = $userId;
        }

        foreach ($alerted as $userId) {
            $noAlert[] = $userId;
        }
    }

    /**************************************************
     * hash tag
     **************************************************/

    /**
     * @param $bbCode
     * @param bool $editBbCode
     * @return array
     * @throws Exception
     */
    public static function parseHashtags(&$bbCode, $editBbCode = false)
    {
        static $_declaredHashtagPick = false;
        static $_declaredAutoHashtag = false;
        static $_formatters = array();

        $bbCodeFormatterClass = XenForo_Application::resolveDynamicClass('XenForo_BbCode_Formatter_Base', 'bb_code');
        if (!$_declaredHashtagPick) {
            eval('class XFCP_Tinhte_XenTag_BbCode_Formatter_HashtagPick extends ' . $bbCodeFormatterClass . ' {}');
            $_declaredHashtagPick = true;
        }
        $bbCodeFormatterClass = 'Tinhte_XenTag_BbCode_Formatter_HashtagPick';

        if ($editBbCode) {
            if (!$_declaredAutoHashtag) {
                eval('class XFCP_Tinhte_XenTag_BbCode_Formatter_AutoHashtag extends ' . $bbCodeFormatterClass . ' {}');
                $_declaredAutoHashtag = true;
            }
            $bbCodeFormatterClass = 'Tinhte_XenTag_BbCode_Formatter_AutoHashtag';
        }

        if (!isset($_formatters[$bbCodeFormatterClass])) {
            $_formatters[$bbCodeFormatterClass] = new $bbCodeFormatterClass();
        }
        $bbCodeFormatter = $_formatters[$bbCodeFormatterClass];

        if (XenForo_Application::$versionId > 1020000) {
            $bbCodeParser = XenForo_BbCode_Parser::create($bbCodeFormatter);
        } else {
            $bbCodeParser = new XenForo_BbCode_Parser($bbCodeFormatter);
        }

        $bbCodeEdited = $bbCodeParser->render($bbCode);

        /** @var Tinhte_XenTag_BbCode_Formatter_HashtagPick $bbCodeFormatter */
        $tagTexts = $bbCodeFormatter->Tinhte_XenTag_getTagTexts();
        if ($editBbCode) {
            /** @var Tinhte_XenTag_BbCode_Formatter_AutoHashtag $bbCodeFormatter */
            $tagTexts = array_merge($tagTexts, $bbCodeFormatter->Tinhte_XenTag_getAutoHashtagTexts());
            $bbCode = $bbCodeEdited;
        }
        $tagTexts = array_values($tagTexts);

        return $tagTexts;
    }
}

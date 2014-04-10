/** @param {jQuery} $ jQuery Object */! function($, window, document, _undefined)
{
	var parent__initAutoComplete = XenForo.BbCodeWysiwygEditor.prototype.initAutoComplete;

	var shouldWork = function()
	{
		if (window.Tinhte_XenTag_maximumHashtags === _undefined)
		{
			// not defined?
			return false;
		}

		if (window.Tinhte_XenTag_maximumHashtags === 0)
		{
			// no permission
			return false;
		}

		return true;
	};

	XenForo.BbCodeWysiwygEditor.prototype.initAutoComplete = function()
	{
		if (!shouldWork())
		{
			console.log('bye bye');
			return parent__initAutoComplete.call(this);
		}

		var api = this.api;
		var $ed = api.$editor;
		var doc = $ed[0].ownerDocument;
		var self = this;

		var hashtagInsert = function(hashtag)
		{
			api.focus();

			var focus = api.getFocus();
			var $focus = $(focus[0]);
			var testText;

			if (focus[0].nodeType == 3)
			{
				// text node
				testText = $focus.text().substring(0, focus[1]);
			}
			else
			{
				focus[0] = $focus.contents().get(focus[1] - 1);
				$focus = $(focus[0]);
				testText = $focus.text();
			}

			var lastAt = testText.lastIndexOf('#');
			if (lastAt != -1)
			{
				api.setSelection(focus[0], lastAt, focus[0], testText.length);
				api.insertHtml('<span class="Tinhte_XenTag_HashTag" style="color: blue; text-decoration: underline">#' + XenForo.htmlspecialchars(hashtag) + '</span>&nbsp;');
			}

			api.focus();
		};

		var hashtagVisible = false;
		var hashtagResults = new XenForo.AutoCompleteResults(
		{
			onInsert: hashtagInsert
		});

		var hideCallback = function()
		{
			setTimeout(function()
			{
				hashtagResults.hideResults();
			}, 200);
		};

		var hashtagFindText = function()
		{
			var focus = api.getFocus(), origin = api.getOrigin();

			if (!focus || !origin || focus[0] != origin[0] || focus[1] != origin[1])
			{
				return false;
			}

			var $focus = $(focus[0]);
			var testText = focus[0].nodeType == 3 ? $focus.text().substring(0, focus[1]) : $($focus.contents().get(focus[1] - 1)).text();
			var lastAt = testText.lastIndexOf('#');

			if (lastAt != -1 && (lastAt == 0 || testText.substr(lastAt - 1, 1).match(/(\s|[\](,]|--)/)))
			{
				var afterAt = testText.substr(lastAt + 1);
				if (!afterAt.match(/\s/) || afterAt.length <= 8)
				{
					return afterAt;
				}
			}

			return false;
		};

		var hashtagLastLookup = false;
		var hashtagLoadTimer = 0;
		var hashtagTriggerResults = function(text)
		{
			if (hashtagLastLookup && hashtagLastLookup == name)
			{
				return;
			}

			hashtagHideResults();
			hashtagLastLookup = text;
			if (text.length > 2)
			{
				hashtagLoadTimer = setTimeout(hashtagLookup, 200);
			}
		};

		var hashtagXhr = false;
		var hashtagLookup = function()
		{
			if (hashtagXhr)
			{
				hashtagXhr.abort();
			}

			hashtagXhr = XenForo.ajax('index.php?tags/find',
			{
				q: hashtagLastLookup
			}, hashtagShowResults,
			{
				global: false,
				error: false
			});
		};

		var hashtagShowResults = function(ajaxData)
		{
			hashtagXhr = false;

			var $iframe = api.$box.find('iframe');
			var offset = $iframe.offset();
			var focus = api.getFocus()[0];
			var $focus = focus.nodeType == 3 ? $(focus).parent() : $(focus);
			var focusOffset = $focus.offset();

			var css =
			{
				top: offset.top + focusOffset.top + $focus.height() - api.$editor.scrollTop(),
				left: offset.left
			};

			if (XenForo.isRTL())
			{
				css.right = $('html').width() - offset.left - $iframe.outerWidth();
				css.left = 'auto';
			}

			hashtagResults.showResults(hashtagLastLookup, ajaxData.results, $iframe, css);
		};

		var hashtagHideResults = function()
		{
			hashtagResults.hideResults();

			if (hashtagLoadTimer)
			{
				clearTimeout(hashtagLoadTimer);
				hashtagLoadTimer = 0;
			}
		};

		$(doc.defaultView || doc.parentWindow).on('scroll', hideCallback);
		$ed.on('click blur', hideCallback);

		$ed.on('keydown', function(e)
		{
			var prevent = true;

			if (!hashtagResults.isVisible())
			{
				return;
			}

			switch (e.keyCode)
			{
				case 40:
					// down
					hashtagResults.selectResult(1);
					break;
				case 38:
					// up
					hashtagResults.selectResult(-1);
					break;
				case 27:
					// esc
					hashtagResults.hideResults();
					break;
				case 13:
					// enter
					hashtagResults.insertSelectedResult();
					break;

				default:
					prevent = false;
			}

			if (prevent)
			{
				e.stopPropagation();
				e.stopImmediatePropagation();
				e.preventDefault();
			}
		});

		// keydown handler to process deletions
		$ed.on('keydown', function(e)
		{
			var prevent = false;

			switch (e.keyCode)
			{
				case 8:
					// backspace
					var focus = api.getFocus();
					var $focus = $(focus[0]);
					var testText;

					if (focus[0].nodeType == 3)
					{
						// text node
						testText = $focus.text().substring(0, focus[1]);
					}
					else
					{
						focus[0] = $focus.contents().get(focus[1] - 1);
						$focus = $(focus[0]);
						testText = $focus.text();
					}

					if (testText.substr(0, 1) == '#' && $focus.parent().hasClass('Tinhte_XenTag_HashTag'))
					{
						$focus.parent().remove();

						prevent = true;
					}
					break;
			}

			if (prevent)
			{
				e.stopPropagation();
				e.stopImmediatePropagation();
				e.preventDefault();
			}
		});

		$ed.on('keyup', function(e)
		{
			var text = hashtagFindText();

			if (text)
			{
				hashtagTriggerResults(text);
			}
			else
			{
				hashtagHideResults();
			}
		});

		return parent__initAutoComplete.call(this);
	};

}(jQuery, this, document);

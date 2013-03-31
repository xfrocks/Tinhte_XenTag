/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	
	// idea from https://github.com/levycarneiro/tag-it
	XenForo.Tinhte_XenTag_TagsEditor = function($ul) { this.__construct($ul); };
	XenForo.Tinhte_XenTag_TagsEditor.prototype = {
		__construct: function($ul) {
			this.$ul = $ul;
			this.$input = $ul.find('.Tinhte_XenTag_TagNewInput');
			this.varName = $ul.data('varname');
			
			this.regex = /[,]+/gi;
			
			// add the class (display purpose)
			$ul.find('li').each(function(i) {
				var $this = $(this);
				if ($this.find('input.textCtrl').length > 0) {
					$this.addClass('Tinhte_XenTag_TagNew');
				} else {
					$this.addClass('Tinhte_XenTag_OtherControls');
				}
			});
			$ul.addClass('textCtrl');
			
			$ul.click($.context(this, 'ulClick'));
			this.$input.keypress($.context(this, 'inputKeypress'));
			
			// process old tags automatically
			var tags = this.$input.val().split(',');
			for (var i in tags) {
				var tag = this.validateInput(tags[i]);
				this.createTag(tag);
			}
			this.$input.val('');
		},
		
		ulClick: function(e) {
			var $target = $(e.target);
			
			if ($target.hasClass('delete')) {
				$target.parents('.Tinhte_XenTag_Tag').remove();
			} else {
				this.$input.focus();
			}
		},
		
		inputKeypress: function(e) {
			var code = event.which;
			
			switch (code) {
			case 8: // backspace
				if (this.$input.val() == '') {
					// input is empty and backspace is pressed
					// removes the last tag
					this.$ul.find('.Tinhte_XenTag_Tag:last').remove();
				}
				break;
			case 13: // enter
			case 44: // comma
				// creates tag with input value
				e.preventDefault();
				
				var value = this.validateInput(this.$input.val());
				
				// creates tag
				this.createTag(value);
				
				// clears the input
				this.$input.val('');
				
				break;
			}
		},
		
		validateInput: function(value) {
			value = value.replace(this.regex, '');
			value = value.trim();
			
			return value;
		},
		
		isNew: function(value) {
			var isNew = true;
			
			this.$ul.find('.Tinhte_XenTag_Tag').each(function(i) {
				var tagValue = $(this).find('input').val();
				if (value == tagValue) {
					isNew = false;
				}
			});
			
			return isNew;
		},
		
		createTag: function(value) {
			if (value != '' && this.isNew(value)) {
				var html = '';
				html += '<li class="Tinhte_XenTag_Tag">';
				html += value;
				html += '<a class="delete">x</a>';
				html += '<input type="hidden" name="' + this.varName + '" value="' + value + '" />';
				html += '</li>';
				
				$(html).insertBefore(this.$input.parents('.Tinhte_XenTag_TagNew'));
			}
		}
	};
	
	// *********************************************************************
	
	XenForo.Tinhte_XenTag_TagsInlineEditor = function($element) { this.__construct($element); };
	XenForo.Tinhte_XenTag_TagsInlineEditor.prototype = {
		__construct: function($element) {
			this.$element = $element;
			this.$trigger = $element.find('.Tinhte_XenTag_Trigger');
			
			this.$trigger.click($.context(this, 'triggerClick'));
		},
		
		triggerClick: function(e) {
			e.preventDefault();
			
			XenForo.ajax(
				this.$trigger.attr('href'),
				{
					_Tinhte_XenTag_TagsInlineEditor: 1
				},
				$.context(this, 'ajaxSuccessForTrigger')
			);
		},
		
		ajaxSuccessForTrigger: function(ajaxData) {
			if (XenForo.hasResponseError(ajaxData) || !XenForo.hasTemplateHtml(ajaxData)) {
				return false;
			}
			
			var $element = this.$element;
			var $templateHtml = $(ajaxData.templateHtml);
			var $saveClick = $.context(this, 'saveClick');
			var $cancelClick = $.context(this, 'cancelClick');
			this.$form = $templateHtml;
			
			new XenForo.ExtLoader(ajaxData, function() {
				$templateHtml.addClass('Tinhte_XenTag_TagsInlineEditorForm');
				$templateHtml.xfInsert('insertAfter', $element, 'show');
				
				$templateHtml.find('.button.primary').click($saveClick);
				$templateHtml.find('.button.cancel').click($cancelClick);
				$templateHtml.find('input[type=text]').focus();
				
				$element.hide();
			});
		},
		
		saveClick: function(e) {
			e.preventDefault();
			
			var serialized = this.$form.serializeArray();
			var action = this.$form.attr('action');

			XenForo.ajax(
					action,
				serialized,
				$.context(this, 'ajaxSuccessForSave')
			);
		},
		
		cancelClick: function(e) {
			e.preventDefault();
			
			this.$element.show();
			this.$form.empty().xfRemove();
		},
		
		ajaxSuccessForSave: function(ajaxData) {
			if (XenForo.hasResponseError(ajaxData) || !XenForo.hasTemplateHtml(ajaxData)) {
				return false;
			}
			
			var $element = this.$element;
			var $form = this.$form;
			var $templateHtml = $(ajaxData.templateHtml);

			new XenForo.ExtLoader(ajaxData, function() {
				$templateHtml.xfInsert('insertAfter', $element, 'show');
				
				$element.empty().xfRemove();
				$form.empty().xfRemove();
			});
		}
	};
	
	XenForo.register('ul.Tinhte_XenTag_TagsEditor', 'XenForo.Tinhte_XenTag_TagsEditor');
	XenForo.register('.Tinhte_XenTag_TagsInlineEditor', 'XenForo.Tinhte_XenTag_TagsInlineEditor');

}
(jQuery, this, document);
Ext.ns('Garp.dataTypes');
Garp.dataTypes.Image.on('init', function(){
	
	this.iconCls = 'icon-img';
	
	// Thumbnail column:
	this.insertColumn(0, {
		header: '<span class="hidden">' + __('Image') + '</span>',
		dataIndex: 'id',
		width: 84,
		fixed: true,
		renderer: Garp.renderers.imageRelationRenderer,
		hidden: false
	});
	
	this.addListener('loaddata', function(rec, formPanel){
		function updateUI(){
			formPanel.preview.update(Garp.renderers.imagePreviewRenderer(rec.get('filename'), null, rec));
			formPanel.download.update({
				filename: rec.get('filename')
			});
		}
		if (formPanel.rendered) {
			updateUI();
		} else {
			formPanel.on('show', updateUI, null, {
				single: true
			});
		}
		// if we're in a relateCreateWindow, set it's height again, otherwise it might not fit.
		if (typeof formPanel.center == 'function' && rec.get('filename')) {
			formPanel.setHeight(440);
			formPanel.center();
		}
	}, true);
	
	
	// Remove these fields, cause we are about to change the order and appearance of them... 
	this.removeField('caption');
	this.removeField('filename');
	this.removeField('id');
	
	// ...include them again:
	this.insertField(0, {
		xtype: 'fieldset',
		style: 'margin: 0;padding:0;',
		items: [{
			name: 'id',
			hideFieldLabel: true,
			disabled: true,
			xtype: 'numberfield',
			hidden: true,
			ref: '../../../../_id'
		}, {
			name: 'filename',
			fieldLabel: __('Filename'),
			xtype: 'uploadfield',
			allowBlank: false,
			emptyText: __('Drag image here, or click browse button'),
			uploadURL: BASE + 'g/content/upload/type/image',
			ref: '../../../../filename',
			
			listeners: {
				'change': function(field, val){
					if (this.refOwner._id.getValue()) {
						var url = BASE + 'admin?' +
						Ext.urlEncode({
							model: Garp.currentModel,
							id: this.refOwner._id.getValue()
						});
						if (DEBUG) {
							url += '#DEBUG';
						}
						
						// because images won't reload if their ID is 
						// still the same, we need to reload the page 
						this.refOwner.formcontent.on('loaddata', function(){
							document.location.href = url;
						});
						this.refOwner.fireEvent('save-all');
					} else {
						field.originalValue = ''; // mark dirty
						this.refOwner.preview.update(Garp.renderers.uploadedImagePreviewRenderer(val));
						this.refOwner.download.update({
							filename: val
						});
						this.refOwner.fireEvent('save-all');
					}
					return true;
				}
			}
		}, {
			xtype: 'box',
			cls: 'garp-notification-boxcomponent',
			html: __('Only {1} and {2} files with a maximum of {3} MB are accepted', 'jpg, png', 'gif', '20'),
			fieldLabel: ' '
		}, {
			name: 'caption',
			xtype: 'textfield',
			fieldLabel: __('Caption')
		}, {
			xtype: 'box',
			ref: '../../../../preview',
			fieldLabel: __('Preview'),
			cls: 'preview',
			html: ''
		}, {
			xtype: 'box',
			hidden: false,
			ref: '../../../../download',
			fieldLabel: ' ',
			hideFieldLabel: false,
			tpl: new Ext.XTemplate('<tpl if="filename">', '<a href="' + IMAGES_CDN + '{filename}" target="_blank">' + __('View original file') + '</a>', '</tpl>')
		}]
	});
	
	
	// Wysiwyg Editor
	this.Wysiwyg = Ext.extend(Garp.WysiwygAbstract, {
	
		model: 'Image',
		
		idProperty: 'id',
		
		settingsMenu: true,
		
		margin: 0,
		
		getData: function(){
			return {
				id: this._data.id,
				caption: this._data.caption
			};
		},
		
		// override: we don't need filtering for images:
		filterHtml: function(){
			return true;
		},
		
		
		setCaption: function(text){
			this._data.caption = text;
			this.el.child('.caption').update(text);
			this.el.child('.caption').setDisplayed( text ? true : false);
		},
		
		showCaptionEditor: function(){
			if (!this.captionEditor) {
				this.captionEditor = new Ext.Editor({
					alignment: 'tl',
					autoSize: true,
					field: {
						selectOnFocus: true,
						xtype: 'textfield',
						width: '100%',
						anchor: '99%'
					}
				});
			}
			this.el.child('.caption').setDisplayed(true);
			this.el.child('.caption').setStyle('position', 'static');
			this.captionEditor.startEdit(this.el.child('.caption'), this._data.caption);
			this.captionEditor.on('complete', function(f, v){
				this.setCaption(v);
				this.el.child('.caption').setStyle('position','absolute');
			}, this);
		},
		
		getMenuOptions: function(){
			return [{
				group: '',
				text: 'Add / remove caption',
				handler: this.showCaptionEditor
			}];
		},
		
		
		
		/**
		 * After pick:
		 */
		pickerHandler: function(sel, afterInitCb){
			this._data = {
				id: sel.data.id,
				caption: sel.data.caption
			};
			var args = Array.prototype.slice.call(arguments);
			args.shift();
			afterInitCb.call(this, args);
		},
		
		/**
		 * 
		 * @param {Object} afterInitCb
		 */
		beforeInit: function(afterInitCb){
			var args = arguments;
			// Do we need to present a dialog or not?
			if(this._data && this._data[this.idProperty]){
				afterInitCb.call(this, args);
				return;
			}
			var picker = new Garp.ModelPickerWindow({
				model: this.model,
				listeners: {
					select: function(sel){
						if (sel.selected) {
							this.pickerHandler(sel.selected, afterInitCb);
						} else {
							this.destroy();
						}
						picker.close();
					},
					scope: this
				}
			});
			picker.show();
		},
		
		/**
		 * Sets content height based on width (maintains aspect ratio)
		 * @param {Number} new width
		 * @returns height
		 */
		resizeContent: function(nw){
			var i = this._data;
			var aspct = i.height / i.width;
			var nHeight = (nw * aspct) - this.margin;
			this.contentEditableEl.setHeight(nHeight);
			this.contentEditableEl.child('.img').setHeight(nHeight);
			return nHeight;
		},
		
		
		setContent: function(){
			this.contentEditableEl = this.el.child('.contenteditable');
			this.contentEditableEl.update('');
			this.contentEditableEl.dom.setAttribute('contenteditable', false);
			
			var i = new Image();
			var scope = this;
			var path = IMAGES_CDN + 'scaled/cms_preview/' + this._data[this.idProperty];
			i.onerror = function(){
				scope.contentEditableEl.setStyle({
					position: 'relative',
					padding: 0
				});
				scope.contentEditableEl.update('<div class="img">' + __('Image not found') + '</div>');
			};
			i.onload = function(){
				
				Ext.apply(scope._data, {
					width: i.width,
					height: i.height
				});
				
				scope.contentEditableEl.setStyle({
					position: 'relative',
					padding: 0
				});
				
				scope.contentEditableEl.update('<div class="img"></div><p class="caption"></p>');
				scope.contentEditableEl.child('.img').setStyle({
					backgroundImage: 'url("' + path + '")'
				});
				var captionEl = scope.contentEditableEl.child('.caption');
				if (scope._data.caption) {
					captionEl.update(scope._data.caption);
					captionEl.show();
					captionEl.on('click', scope.showCaptionEditor, scope);
				} else {
					captionEl.hide();
				}
				
				scope.resizeContent(scope.contentEditableEl.getWidth());
				if (scope.ownerCt) {
					scope.ownerCt.doLayout();
				}
			};
			i.src = path;
			if (i.complete) {
				i.onload();
			}
		},
			
		/**
		 * init!
		 * @param {Object} ct
		 */
		initComponent: function(ct){
			
			this.html += '<div class="contenteditable"></div>'; 
		
			this.addClass('wysiwyg-image');
			this.addClass('wysiwyg-box');
			if (this.col) {
				this.addClass(this.col);
			}
			this.on('user-resize', function(w, nw){
				this.setHeight(this.resizeContent(nw));
			});
			this.on('afterrender', this.setContent, this);
			Garp.dataTypes.Image.Wysiwyg.superclass.initComponent.call(this, arguments);
		}
	});
});

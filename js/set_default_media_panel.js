(function() {
	
	link = poparams.link;
	align = poparams.align;
	size = poparams.size;
	
    var _AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay;
    wp.media.view.Settings.AttachmentDisplay = _AttachmentDisplay.extend({
        render: function() {
            _AttachmentDisplay.prototype.render.apply(this, arguments);
            
            this.$el.find('select.link-to').val(link);
            this.model.set('link', link);           
            this.updateLinkTo();
            
            this.$el.find('select.alignment').val(align);
            this.model.set('align', align); 
            
            this.$el.find('select.size').val(size);
            this.model.set('size', size);
        }
    });
})();
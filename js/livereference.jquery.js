/*
	plugin:	livereference jQuery plugin
	version: 2012-04-18
	author: Aleksandar Radovanovic
	examples and documentation at: http://livereference.org
*/
(function($) {
	var pluginName = 'livereference'; 	// plugin name 
	var methods = {
		init: function(options) {       
				options = $.extend({					// Default arguments
				parent: 'body',						// don't touch this
				css: pluginName,						// default class to link plugin to - no need to change
															// configurable options
				sCitationStyle: 'vancouver',		// citation style			
				sAjaxSource: '../livereference.php',				// script which provide data
				sWidgetsSource: '../widgets.xml',		// widgets source
				sEndNoteSource: 'endnote.xml',	// end note references
				sTSVSource: '../livereference.tsv',		// TSV as a data source
				xOffset: 10,							// offset from te click for the text display
    			yOffset: 10,
    			boxWidth: 'auto',						// width of the reference display box
    			waitMessage: 'please wait..'
			}, $.fn[pluginName].options, options);
            
			return this.each(function() {	// Iterate current(s) element(s)
				var	$this = $(this),
						data = $this.data(pluginName);
				if (data) return;				// If the plugin has already been initialized
					$element = $('<div />', {						// create div for contennt
						class: options.css,
						html: options.waitMessage					// set the content
					}).appendTo(options.parent).hide().width(options.boxWidth)
					.bind ('click', function() {$(this).stop(true, true).fadeOut();});	// click closes it

				var data = {};											// Stock properties
				data[pluginName] = $element; 						// Stock $element & define data
				data['options'] = options;
				$this
					.data(pluginName, data)
					.attr('title', '')								// Remove default title
					.bind('click.'+pluginName, methods.showhide);	// bind event to plugin NameSpace
			});					
		},
		showhide: function(event) {
			event.stopPropagation();
			var	$this = $(this),
					data = $this.data(pluginName);
			if (data[pluginName].is(":visible")) {					// if visible then hide, if not then show
				data[pluginName].stop(true, true).fadeOut();	
			} else {
				data[pluginName]											// display reference
					.css({
						top: (event.pageY - data[pluginName].outerHeight() - data['options'].yOffset),
						left: (event.pageX + data['options'].xOffset)
					})
					.stop(true, true).fadeIn();		
				if (data[pluginName].html() == data['options'].waitMessage) { // ajax call if box does not conatin reference
					$.get(data['options'].sAjaxSource, {id: $this.attr('data-lrerf'), lf: data['options'].sTSVSource,  endnote: data['options'].sEndNoteSource, widgets: data['options'].sWidgetsSource, cstyle: data['options'].sCitationStyle}, function(reftext) {
						data[pluginName].html(reftext);	
						data[pluginName].stop(true, true).fadeOut();			
						data[pluginName]
							.css({
								top: (event.pageY - data[pluginName].outerHeight() - data['options'].yOffset),
								left: (event.pageX + data['options'].xOffset)
							})
							.stop(true, true).fadeIn();
					}); //ajax
				} // end if				
			}
		},
		destroy: function() {
			return this.each(function() {
			var $this = $(this),
				data = $this.data(pluginName);
			$this
				.unbind('.'+pluginName)			// Remove this object event(s) using namespace
				.removeData(pluginName);		// Clear DOM data
			data[pluginName].remove();			// Clear element
			});
		}
	};
    
	$.fn[pluginName] = function(method) {
		if (methods[method]) return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		else if (typeof method === 'object' || !method) return methods.init.apply(this, arguments);
		else $.error('Method ' + method + ' does not exist on jQuery.'+pluginName);
	};

})(jQuery);




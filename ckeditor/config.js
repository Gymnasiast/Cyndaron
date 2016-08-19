/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	config.language = 'nl';
	config.uiColor = geefInstelling('artikelkleur');
	config.contentsCss = '/stijl.css';
	config.allowedContent = true;
	config.disallowedContent = 'table[cellspacing,cellpadding,border]';
};

<?php
/**ou-specific
 *
 * @file JimeThemePlugin.inc.php
 *
 * @copyright Copyright 2010 The Open University.
 *
 * @class JimeThemePlugin
 * @ingroup plugins_generic_JIME
 * @brief JIME theme plugin
 *
 * Fonts: http://www.open.ac.uk/webstandards/v2/fonts.php
 * Icons: http://www.open.ac.uk/webstandards/v2/icons.php
 * (Loosely based on OJS 'classicBlue.css'.)
 */
import('classes.plugins.ThemePlugin');


class JimeThemePlugin extends ThemePlugin {
	/**Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'JimeThemePlugin';
	}

	function getDisplayName() {
		return '** JIME Theme';
	}

	function getDescription() {
		return 'JIME layout';
	}

	function getStylesheetFilename() {
		return 'jime.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}

  /** Add a Favicon, and basic mobile support ("viewport").
  * ('activate' may be better for themes than 'display'.)
  */
  function activate(&$templateMgr) {
    parent::activate($templateMgr);

    $additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
		$additionalHeadData .= '<link rel="shortcut icon" href="'.Request::getBaseUrl().'/plugins/themes/jime/favicon.ico" />'.PHP_EOL;
		$additionalHeadData .= '<meta name="viewport" content="width=device-width; initial-scale=0.9; maximum-scale=3.0;"/>'.PHP_EOL;
		$templateMgr->assign('additionalHeadData', $additionalHeadData);
		return true;
  }

	function __register($category, $path) {

		return TRUE; #Must return TRUE - handled!
	}

}

/* CSS:
   Legacy JIME - try to remove some legacy links ("TiledWin UI"/"Overlap.Win. UI" etc. / *.html).  onmouseout=window.status... / target=_top *-/
img[src="../../resources/icons/3-frame-icon.gif"], [src="../../resources/icons/2-frame-icon.gif"],
/* suppFiles - jime.css isn't included :( *-/
img[src="../../../resources/icons/prev.gif"], [src="../../../resources/icons/contents.gif"], [src="../../../resources/icons/next.gif"]
    { position:absolute; top:-999px; }
img:after{ content:attr(__src) }
*/

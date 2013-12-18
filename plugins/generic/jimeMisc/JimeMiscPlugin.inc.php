<?php
/**
 * @file JimeMiscPlugin.inc.php
 *
 * @author    N.D.Freear, 3 August 2010.
 * @modified by A..J.Brasher,  29 November 2013. 
 * @copyright Copyright 2010 The Open University.
 * @class JimeMiscPlugin
 *
 * @ingroup  plugins_generic_JIME
 *
 * @brief Jime Misc plugin class
 *        Rewrites URLs in Smarty, adds the JIME footer, adds sharing tool links, ImportTidy, etc.
 */
import('lib.pkp.classes.plugins.GenericPlugin');
require_once("ImportTidy.inc.php");


class JimeMiscPlugin extends GenericPlugin {

  protected $tidy = NULL;

	/** Get the symbolic name of this plugin
	 * @return string
	 */
	/** Not neede dfor lazy load plugins  - see 
	function getName() {
		return 'JimeMisc';
	}
	**/

	/** Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return 'JIME Miscellany'; #Locale::translate('plugins.generic.webfeed.displayName');
	}

	/** Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return "Rewrites URLs in Smarty, adds the OU/JIME footer, adds sharing tool links, ImportTidy, etc....";
	}


	function register($category, $path) {
		if (parent::register($category, $path)) {
			#if ($this->getEnabled()) {

        $import_tidy  = Config::getVar('jime', 'import_tidy');
        if ($import_tidy) {
            ImportTidy::checkTidy();
            HookRegistry::register('ArticleFileManager::postCopy',  array('ImportTidy', 'postCopy')); #Static.
        }

        // Should use $this by reference i.e. '&$this' (see http://pkp.sfu.ca/ojs/docs/technicalreference/2.1/pluginsSamplePlugin.html#pluginsHookRegistrationAndCallback )
		HookRegistry::register('TemplateManager::display',array(&$this, 'display'));
		
		// JimEBlockPlugin not need so  do not need  linebelow
		//HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));

        HookRegistry::register('Templates::Common::Footer::PageFooter', array(&$this, 'insertFooter'));
        HookRegistry::register('Templates::Article::Footer::PageFooter', array(&$this, 'insertFooter'));
        HookRegistry::register('Templates::Article::Interstitial::PageFooter', array(&$this, 'insertFooter'));
        HookRegistry::register('Templates::Article::PdfInterstitial::PageFooter', array(&$this, 'insertFooter'));

        //Search: .tpl "call_hook"
        HookRegistry::register('Templates::RT::Footer::FrameFooter', array(&$this, 'insertRTFrameFooter'));
        #HookRegistry::register('Templates::Common::Header::Navbar::CurrentJournal', array(&$this, 'insertNavbar')); #<LI>
			#}
			#$this->addLocaleData();
			return true;
		}
		return false;
	}

	/** JimeBlockPlugin is not used so do not need this 
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks': 
			  $this->import('JimeBlockPlugin');
				$blockPlugin = new JimeBlockPlugin();
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] =& $blockPlugin;
			break;
			
		}
		return false;
	}
**************/
	
  /** The 'display' template hook is the right time to replace the 
   *  default 'smartyUrl' function with our own.
   */
  function display() {
      $templateManager =& TemplateManager::getManager();

		  $rewrite_urls = Config::getVar('jime', 'jime_urls');
		  if ($rewrite_urls) {
		      $templateManager->register_function('url', array(&$this, 'smartyUrl'));
      }
  }

  /** Rewrite 'article/view' URLs for Smarty, use default function for the rest.
   *
   * (Useful classes: PKPTemplateManager, PKPPlugin, PKPRequest, OJSPageRouter, OJSApplication.)
   */
  function smartyUrl($params, &$smarty) {
    #$paramList = array('context', 'page', 'op', 'path', 'anchor', 'escape');
    $page   = $params['page'];
    $op     = $params['op'];
    $public_id = null;

    ##$restful_urls = Config::getVar('general', 'restful_urls');

    // The special case - 'article/view'
    if ('article'==$page && 'view'==$op) { #|| 'viewArticle'==$op.
        $default = false;

        $path = $params['path'];
        $galley_id = 'z';

        $HOW = 0;

        if (is_string($path) && FALSE !== strpos($path, '-')) {
          $public_id = $path;

          $HOW = 1;
        }
        else {
          if (is_array($path)) {
            $public_id = $path[0];
            $galley_id = $path[1];

            #Eg. 'article/view/7/0'
            if (0 === $galley_id) { #Or, $public_id ! contain '-'.
              $article_id = $public_id;
              $public_id  = NULL;
            } else {
              # HTML or PDF galley.

              $default = true;
              //{journal}/article/view/{$public_id}/{$galley_id}
              return Request::getBaseUrl()."/article/$public_id/$galley_id"; #--how$HOW--".__CLASS__;
            }
            $HOW = 2;
          }
          elseif (is_numeric($path)) {
            $article_id = $path;

            $HOW = 3;
          }
          else { #ERROR?
            return Request::getBaseUrl()."/$article_id#Error__".__CLASS__;
          }
        }
        #if (!$default) {
        if (!$public_id) {
            # Get public ID from database.
            $templateMgr =& TemplateManager::getManager();
			      $currentJournal = $templateMgr->get_template_vars('currentJournal');
			      $journal_id = $currentJournal->getJournalId();

            $publishedArticleDAO =& DAORegistry::getDAO('PublishedArticleDAO');
            $publishedArticle = $publishedArticleDAO->getPublishedArticleByArticleId($article_id, $journal_id);
            if ($publishedArticle) {
              $public_id = $publishedArticle->getPublicArticleId();
            } else {
              #ERROR.
              $path = var_export($path, true);
              $public_id = str_replace(array("\r\n", "\n", "\r", " ", "\t"), '_', $path);
          }
          #}
          $path = str_replace('-', '/', $public_id); #str_replace(array('book', 'conf'), ..)

          return Request::getBaseUrl()."/$path"; #$galley_id--how$HOW--".__CLASS__;
        }
    }
    #ELSE: Use the default function.
    $templateManager =& TemplateManager::getManager();

    return $templateManager->smartyUrl($params, $smarty);

    #http://stickleback.open.ac.uk/openjournal_2/jime/article/viewArticle/7
    # : array ( page = article, op = view, path = array ( 0 = 7, 1 = 0, ), )
    #<frame src="JimeMiscPlugin--array-( 'op'-=>-'viewArticle', 'path'-=>-'7',-)" frameborder="0"/>
    #<meta name="DC.Identifier.URI" content="JimeMiscPlugin array-( 'page'=>'article', 'op'=>'view', 'path'=>'1996-1', )"/>
  }


  function insertNavbar($hookName, $params) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager();
			$currentJournal = $templateMgr->get_template_vars('currentJournal');

      #$output .= ;
    return false;
  }

  /** Add sharing tools and OU / CC license logos to main pages.
   *  (Here or in DB: ojs_jime.journal_settings.journalPageFooter.)
   */
	function insertFooter($hookName, $params) {
		#if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager();
			$currentJournal = $templateMgr->get_template_vars('currentJournal');

      $output .= $templateMgr->fetch($this->getTemplatePath() .'jimeShareFooter.tpl');
			$output .= $templateMgr->fetch($this->getTemplatePath() .'jimeOUFooter.tpl');
		#}
		return false;
	}

  /** Add sharing tools to the Reading Tools side-frame.
   */
  function insertRTFrameFooter($hookName, $params) {
      $smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager();
			$currentJournal = $templateMgr->get_template_vars('currentJournal');

      $output .= $templateMgr->fetch($this->getTemplatePath() . 'jimeShareFooter.tpl');

      return false;
  }
}


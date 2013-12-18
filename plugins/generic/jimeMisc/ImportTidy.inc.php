<?php
/**
 * @file ImportTidy.inc.php
 *
 * @author    N.D.Freear, 18-24 August 2010.
 * @copyright Copyright 2010 The Open University.
 * @class     ImportTidy
 *
 * @ingroup   plugins_generic_JIME
 *
 * @brief Import Tidy class
 *        Use php-tidy to clean up esp. HTML from Word - make a copy of the original for posterity.
 */
import('file.FileManager');

// Constants for logging.
define('X_FILE_WRITE_MODE',    0666);
define('X_FOPEN_WRITE_CREATE', 'ab');
// This define is a few larger than ARTICLE_LOG_ARTICLE_IMPORT.
define('X_ARTICLE_LOG_ARTICLE_TIDY', 0x10000011);


class ImportTidy {

  const  WRAP_CLASS= 'jime-paper-0';
  const  LOG_FILE  = '/logs/import_tidy.log';
  const  ERR_FILE  = '/logs/tidy_error.log';

  /** Utility function to give early warning, if "php-tidy" isn't available.
  */
  public static function checkTidy() {
      $do_tidy = Config::getVar('jime', 'import_tidy');
      if (!function_exists('tidy_repair_string')) {
          $error = "ERROR. plugins.generic.jime.".__CLASS__." requires 'php-tidy'. Aborting.";
          self::logEvent($error);
          if ($do_tidy) {
              die($error.' <a href="/">Home</a>.'); #Not clean, but effective!
          }
          return FALSE;
      }
      return TRUE;
  }

  /** THE callback, for our 'ArticleManager::postCopy' hook.
  */
  public static function postCopy($hookName, $args) {
    // The url and articleId are only for logging.
    list($url, $filePath, $mimeType, $type, $articleId) = $args; #&$result.

    $do_tidy = Config::getVar('jime', 'import_tidy');

    ##$success = self::logEvent("post AID=$articleId: $filePath, $mimeType, $type, $url");

    // Don't try to process PDFs, etc.
    if (!$do_tidy || ARTICLE_FILE_PUBLIC != $type || 'text/html' != $mimeType) {
        return FALSE; // Unhandled.
    }

    #$fileName = basename($filePath);

    // Copy this file to an "original file", in the same directory for now.
    $origFilePath = str_replace('-PB.html', '-PB.ORIG.html', $filePath);

    if (! FileManager::copyFile($filePath, $origFilePath)) {
			self::logEvent(__CLASS__."::copy failed, to $origFilePath");
			return FALSE;
		}

    /*** TIDY - replace existing file. ***/
    self::checkTidy();
    $message = self::doTidy($filePath, $filePath);

    $message = str_replace('__AID__', "AID=$articleId", $message);
    $success = self::logEvent($message, $articleId);

    return TRUE; // Handled.
  }

  /** Read file, run tidy, post-tidy, write file.
  */
  protected static function doTidy($source, $dest) {
    // Don't get Tidy to re-encode.
    // http://tidy.sourceforge.net/docs/quickref.html
    $tidy_config = array(
        'word-2000'=> true,
        'bare'     => true,
        'clean'    => true,
        'drop-proprietary-attributes' => true,
        'output-xhtml'=> true,
        'alt-text' => '',
        'drop-font-tags' => true,
        #'logical-emphasis'=>true, #HTML5 allows <i><b>.
        #'anchor-as-name' => false, #Not available.
        'hide-comments'  => true,
        'error-file'=> Config::getVar('files', 'files_dir'). self::ERR_FILE,
        #'add-xml-decl'=> true, #Doesn't always add encoding :(!
    );

    $bytes_in= filesize($source); #strlen()
    $html_in = file_get_contents($source);

    $charset_in = $generator = null;
    if (preg_match("#<meta.*charset=(.*?)\"#i", $html_in, $matches)) {
        $charset_in = trim($matches[1]);
    }
    if (preg_match("#<meta.*?Generator .*?\"(.*?)\"#i", $html_in, $matches)) {
        $generator = $matches[1];
    }

    $html_out = self::preTidy($html_in);

    // *** Run TIDY (don't get Tidy to re-encode.) ***
    $html_out = tidy_repair_string($html_out, $tidy_config);

    $html_out = self::downHeadings($html_out);

    $html_out = self::postTidy($html_out); #$this-

    $bytes_out = file_put_contents($dest, $html_out);

    $dest_name = str_replace(Config::getVar('files', 'files_dir'), '', $dest);
    $message = " OK [__AID__; BI=$bytes_in; BO=$bytes_out; ENC=$charset_in; GEN=$generator] $dest_name"; #$source

    // A crude check for HTML/XML sample code! Eg. <ABC>
    if (preg_match("#\&lt;\w+\&gt;#", $html_out, $matches)) {
        $message .= " [XML sample code detected]";
    }

    return $message; #$bytes_out;
  }

  /** Prevent tidy stripping the [if !supportLists] Word-HTML, iet-it-bugs:1179.
  */
  protected static function preTidy($html_out) {
    //<![if !supportLists]><span lang="EN-US">1<span style="font: 7pt &quot;Times New Roman&quot;;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //</span></span><![endif]>
    $html_out = preg_replace("#<\!\[if !supportLists\]>.*?([\d.]+)<.*?\!\[endif\]>#ms", "<i>$1</i> ", $html_out);

    return $html_out;
  }

  /** Convert <H1> headings to <H2>, H2s to H3... except for the paper-title.
  */
  protected static function downHeadings($html_out) {
    $title = NULL;
    if (preg_match("#<title>([^>]+)<\/title>#ms", $html_out, $matches)) {
        $title = $matches[1];
        $title = trim(str_replace("JIME", '', $title), ':- ');
        $title = trim(str_replace("Apple Learning Interchange -", '', $title));
    }
    #$title_pattern = str_replace(array("\r\n","\n","\r",' '), '\s', $title);

    // Protect the paper's title.
    $html_out = preg_replace("#<(h1|p|strong|b)([^>]*)>$title<\/(h1|p|strong|b)>#ms", '___MY_H1=$2___', $html_out);

    // Now down-grade, 'smallest' first.
    foreach (array(4, 3, 2, 1) as $h_in) {
        $h_out = $h_in + 1;
        $html_out = preg_replace("#<h$h_in([^>]*)>([^>]+)<\/h$h_in>#", "<h$h_out$1>$2</h$h_out>", $html_out);
    }

    // Re-instate paper-title.
    $html_out = preg_replace("#___MY_H1=(.*?)___#", '<h1$1>'.$title.'</h1>', $html_out);

    return $html_out;
  }

  /** After Tidy call, fix encoding and convert to minimal HTML5 (eg. remove <body>).
  *   Set a 'wrapper-class' for easier styling.
  */
  protected static function postTidy($html_out) {

    $wrapper_div = '<div id="jime" class="'.self::WRAP_CLASS.'" lang="en">';

    $fix_entities = array( #JIME:2009-5: "one that uses"
        '&#210;' => '&ldquo;',
        '&#211;' => '&rdquo;',
        '&#212;' => '&lsquo;',
        '&#213;' => '&rsquo;',
    );

    // Fix broken entities (esp. for Mac/Word 10), and do UTF-8 encoding here.
    $html_out = str_replace(array_keys($fix_entities), $fix_entities, $html_out);
    $html_out = utf8_encode($html_out);

    // Now convert to minimal HTML (Was, HTML5, which doesn't mandate <head>, <body>).
    $html_out = preg_replace("#<!DOCTYPE[^>]+>#msi", '', $html_out);
    #(<!DOCTYPE html><html><meta charset="utf-8"/>)
    $html_out = preg_replace("#<html[^>]+>#", "$wrapper_div", $html_out);
    $html_out = str_replace ("</html>", '</div><!--/'.self::WRAP_CLASS.'-->', $html_out);
    $html_out = str_replace(array('<head>', '</head>', '<body>', '</body>'), '', $html_out);
    $html_out = preg_replace("#<title>[^>]+\/title>#", '', $html_out);

    // Remove <link>, <meta> tags.
    $html_out = preg_replace("#<link.+?\/>#ms", '', $html_out);
    $html_out = preg_replace("#<meta[^>]+>#ms", '', $html_out);
    $html_out = preg_replace("#<hr[^>]+\/>#", '<hr />', $html_out);
    $html_out = preg_replace("#<br \/>\s<br \/>\s<br \/>#ms", '', $html_out);

    // Remove Tidy's body/link CSS.
    $html_out = preg_replace("#(body|:link|:visited)\s*\{[^\}]+\}#ms", '', $html_out);
    #$html_out = preg_replace("#<style.*?\/style>#ms", '', $html_out);

    $html_out = preg_replace("#<img([^>]+)[hv]space=\s{0,2}\"\d{1,2}\" [hv]space=\s{0,2}\"\d{1,2}\"#ms", '<img$1', $html_out);
    $html_out = str_replace(array('<p><o:p></o:p></p>', '<o:p></o:p>'), '', $html_out);

    // Remove "Tiled/ Overlap window interface" links.
    $html_out = preg_replace("#<a[^>]+onmouseover[^>]+><img[^>]+icon[^>]+><\/a>#ms", '', $html_out);
    // "Commentaries" - replace with placeholder.
    $html_out = preg_replace("#<p[^>]+><(b|strong)>Commentaries:.*?<\/p>#ms", PHP_EOL.'<div class="jime-commentary"> </div>'.PHP_EOL, $html_out);

    // Anchors (name is obsolete in HTML5).
    $html_out = str_replace(array('<a name=""></a>', '<br clear="all" />'), '', $html_out);
    $html_out = preg_replace("#name=\s{0,2}\"[^>]+\" id=\s{0,2}\"([^>]+)\">#ms", 'id="$1">', $html_out);
    $html_out = preg_replace("#name=\s{0,2}\"([^>]+)\">#ms", 'id="$1">', $html_out);
    #$html_out = preg_replace("#target=[^>]+\"#", '', $html_out); #'xref' to 'top'?

    // Tables. Possibly too much?!
    $html_out = str_replace(array('border="0"', 'border="1"'), '', $html_out);
    $html_out = str_replace(array('cellspacing="0"','cellpadding="0"','valign="top"'), '', $html_out);
    $html_out = preg_replace('#cellspacing="\d" cellpadding="\d"#', '', $html_out);
    $html_out = preg_replace('#<td(.*?) width=\"(\d{1,2})%\"#', '<td$1 style="width:$2%"', $html_out);
    $html_out = preg_replace('#<td(.*?) width=\"(\d{1,3})\"#',  '<td$1 style="width:$2px"', $html_out);

    // Hmm, first table may be citation 'block' (above title), or affiliation block (below title) :(.
    $html_out = preg_replace("#<table.*?".">#",'<table class="head">', $html_out, 1);
    ##$html_out = preg_replace("#<p(.*?)".">#",  '<p $1 class="first">', $html_out, 1);

    return $html_out;
  }


  /** Log messages to log-file, and the 'article_event_log' DB table. See, CI Log.php
  */
  protected static function logEvent($msg, $articleId = FALSE) {

    $filepath =  Config::getVar('files', 'files_dir'). self::LOG_FILE;

    $success = true;
  	if (! $fp = @fopen($filepath, X_FOPEN_WRITE_CREATE)) {
			  echo __CLASS__."::LOG error. $filepath ";
			  $success = FALSE;
		}

		$message = date('Y-m-d H:i:s'). ' --> '.$msg.PHP_EOL;

		flock($fp, LOCK_EX);	
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, X_FILE_WRITE_MODE); 		

    if (FALSE===$articleId) return TRUE;

// Log the import in the article event log.
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent(
			$articleId,
			X_ARTICLE_LOG_ARTICLE_TIDY, #ARTICLE_LOG_ARTICLE_IMPORT,
			ARTICLE_LOG_TYPE_DEFAULT,
			0,
			"plugins.generic.jime.".__CLASS__.".$msg", #'log.imported',
			array('articleId' => $articleId)
		);

		return $success;
  }
}

/* Config.inc.php
[jime]
import_tidy = On


  MyPlugin::register(...) {
    HookRegistry::register('ArticleFileManager::postCopy', array('ImportTidy', 'postCopy'));
  }


  ArticleFileManager::handleCopy(...) {
    ...
#ou-specific. My new hook.
    $handled = HookRegistry::call('ArticleFileManager::postCopy', array(&$url, $dir.$newFileName, &$mimeType, &$type, &$articleId)); #&$result.
#ou-specific ends.

		$articleFile->setFileSize(filesize($dir.$newFileName));
...
*/


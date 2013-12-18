{**
 * JIME sharing tools, for footers.
 *
 * Note, used in Reading-Tool frame context, article footer, and non-article pages.
 *}


{if $article && $article->getSuppFiles()} {*if $journalRt->getSupplementaryFiles() &&*}
  <p class="supp_warn">
   This article contains <a class="popup rt supp" title="New window" target="RT"
   href="{url page="rt" op="suppFiles" path=$articleId|to_array:$galleyId}"
     >{translate key="rt.suppFiles"}</a>
  </p>
{/if}
  <ul id="footer_share">
      <li ><a class="audio" href="http://www.open.ac.uk/browsealoud/">{*<!--img alt=""
       src="http://upload.wikimedia.org/wikipedia/commons/3/3e/RadioSabalera.gif" /-->*}
       Listen to this page</a></li>
         {*<small>(test, *.open.ac.uk? Techdis toolbar doesn't work with frames?)</small>*}

    {if $article}
      <li ><a class="link perma" href="{url page="article" op="view" path=$articleId}" target="_parent" rel="bookmark">{*<!--img alt=""
       src="http://upload.wikimedia.org/wikipedia/commons/1/18/Icons-mini-page_bookmark.gif" /-->*}
       Bookmark: {$article->getLocalizedTitle()|strip_unsafe_html|truncate:20:"...":true}</a> {*<small>(Demo only)</small>*}</li>
    {/if}
      <li ><a class="rss feed" href="{url context="jime" page="feed" op="atom"}" title="Atom feed">{*<!--img
       src="http://www.feedburner.com/fb/lib/images/icons/feed-icon-12x12-orange.gif" alt=""/-->*}
       Subscribe to Feed</a></li>

      <li class="addthis"><!-- AddThis Button BEGIN -->
      <a class="addthis_button" href="http://www.addthis.com/bookmark.php?v=250&amp;username=xa-4c59437f7856cf70"><img
      alt="Bookmark and Share on CiteULike, Delicious, Facebook, Twitter, email..." src="http://s7.addthis.com/static/btn/v2/lg-share-en.gif"
      style="border:0" /></a><script type="text/javascript">//<![CDATA[
  var addthis_share ={literal} { {/literal}
    {if $article}
      url  : '{url page="article" op="view" path=$articleId}',
      title: '{$article->getLocalizedTitle()|escape}',
      description:'JIME {if $issue}, {$issue->getIssueIdentification()|strip_unsafe_html|nl2br}{/if} {$article->getAuthorString(true)|escape}'
    {/if}
 {literal} };
  var addthis_config= { {/literal}
      ui_cobrand: 'JIME',
      __services_expanded:'email,print,facebook,twitter,delicious,citeulike',
      __ui_508_compliant: true
 {literal} }; {/literal}
  //]]></script><script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#username=xa-4c59437f7856cf70"></script>
  <!-- AddThis Button END --></li>
  </ul>


{**<!--
  http://www.jmir.org/ojs/plugins/generic/webFeed/templates/images/atom10_logo.gif
  http://www.addthis.com/help/client-api

  Techdis toolbar - Frame problem?
  <p><a href=
"javascript:(function(){d=document;lf=d.createElement('script');lf.type='text/javascript';lf.id='ToolbarStarter';lf.text='var%20StudyBarNoSandbox=true';d.getElementsByTagName('head')[0].appendChild(lf);jf=d.createElement('script');jf.src='http://access.ecs.soton.ac.uk/ToolBar/channels/toolbar-stable/JTToolbar.user.js';jf.type='text/javascript';jf.id='ToolBar';d.getElementsByTagName('head')[0].appendChild(jf);})();"
  ><img alt="Start JISC Techdis Toolbar Lite" title="Start JISC Techdis Toolbar" src="http://access.ecs.soton.ac.uk/ToolBar/content/toolbar/toolbarlauncher.png" /></a>
  </p>

  <script type="text/javascript"src=
"http://w.sharethis.com/widget/?tabs=web%2Cemail&amp;charset=utf-8&amp;services=facebook%2Cdigg%2Cdelicious%2Cstumbleupon%2Ctechnorati%2Creddit%2Cpropeller%2Cblinklist%2Cmixx%2Cnewsvine
&amp;style=rotate&amp;publisher=b75b680c-5302-486c-b473-6b72a55c3ad6"></script><small>(Demo only - 'publisher_id' not valid. Addthis, Citeulike, Zotero?)</small></li>
      
  <span id="sharethis_0">
  <a st_page="home" href="javascript:void(0)" title="ShareThis via email, AIM, social bookmarking and networking sites, etc." class="stbutton stico_rotate">
  <span st_page="home" class="stbuttontext">ShareThis</span></a>
  </span>

<p>
{if $journal}{$journal->getLocalizedInitials()|escape}{/if}{if $issue}, {$issue->getIssueIdentification()|strip_unsafe_html|nl2br}{/if} {$article->getAuthorString(true)|escape}
</p>
-->*}

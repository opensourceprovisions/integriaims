<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;
$id_user = $config["id_user"];

if (check_login() != 0) {
	audit_db("Noauth", $config["REMOTE_ADDR"], "No authenticated access", 
		"Trying to access monthly report");
	require ("general/noaccess.php");
	
	exit;
}

if (! give_acl ($config['id_user'], $id_grupo, "WR")) {
 	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access agenda of group ".$id_grupo);
	include ("general/noaccess.php");
	
	exit;
}

require_once("include/wiki/lionwiki_lib.php");

$translation_strings = array();
$translation_strings['title_text'] = __('Admin Pages');
$translation_strings['delete_text'] = __('Delete');
$translation_strings['correct_text'] = __('Correct delete page ');
$translation_strings['incorrect_text'] = __('Incorrect delete page ');

$conf_plugin_dir = 'include/wiki/plugins/';
$conf_var_dir = 'var/';
if (isset($config['wiki_plugin_dir']))
	$conf_plugin_dir = $config['wiki_plugin_dir'];
if (isset($config['conf_var_dir']))
	$conf_var_dir = $config['conf_var_dir'];

$conf['wiki_title'] = 'Wiki';
$conf['self'] = 'index.php?sec=wiki&sec2=operation/wiki/wiki' . '&';
$conf['plugin_dir'] = $conf_plugin_dir;
$conf['var_dir'] = $conf_var_dir;
$conf['custom_style'] = file_get_contents ($config["homedir"]."/include/styles/wiki.css");
$conf['fallback_template'] = $conf['custom_style']. '
 
<div id="wiki_view">
	<table width="100%" cellpadding="0">
		<tr><td style="border-bottom: 1px solid #ccc;" colspan="3"><h2>{PAGE_TITLE}</h2></td></tr>
		<tr>
			<td colspan="3">
				{<div style="color:#F25A5A;font-weight:bold;"> ERROR </div>}
				{CONTENT} {<div style="background: #EBEBED"> plugin:TAG_LIST </div>}
				{plugin:TOOLBAR_TEXTAREA}
				{CONTENT_FORM} {RENAME_INPUT <br/><br/>} {CONTENT_TEXTAREA}
				{EDIT_SUMMARY_TEXT} {EDIT_SUMMARY_INPUT} {CONTENT_SUBMIT} {CONTENT_PREVIEW}</p>{/CONTENT_FORM}
			</td>
		</tr>
		<tr><td colspan="3"><hr/></td></tr>
		<tr>
			<td> {LAST_CHANGED_TEXT}: {LAST_CHANGED}</td>
			<td></td>
			<td></td>
		</tr>
	</table>
</div>';
$action = get_parameter('action');
if ($action == 'syntax') {
	show_syntax();
}
else {
	// Main call to render wiki
	lionwiki_show($conf);
}

function show_syntax() {
	?>
	<style type="text/css">
	pre {
		background-color: #FFFFEF;
		border: 1px dashed #808080;
		color: #000000;
		font-size: 0.8em;
		margin: auto;
		padding: 3px;
		word-wrap: break-word;
	}
	ul {
		list-style: disc outside none;
	}
	ul ul, ol ul {
		list-style-type: circle;
	}
	ul ul ul, ol ul ul {
		list-style-type: square;
	}
	ol {
		list-style: decimal outside none;
	}
	</style>
	<h2>Syntax</h2>
	<h4>Headings</h4>
	Example:
	<pre>!Heading H2
!!Heading H3
!!!Heading H4</pre>
	<h2>Heading H2</h2>
	<h3>Heading H3</h3>
	<h4>Heading H4</h4>
	More exclamation marks you use, the smaller the heading will be (up to 5 exclamation marks). Exclamation marks has to be exactly at the beginning of the line.
	
	
	<h4>Lists</h4>
	<h2>Unordered list</h2>
	<pre>* Fruit
** Apple
*** Granny smith
** Orange
* Vegetables
** Garlic
** Onion</pre>
	<ul><li> Fruit</li><ul><li> Apple</li><ul><li> Granny smith</li></ul><li> Orange</li></ul><li> Vegetables</li><ul><li> Garlic</li><li> Onion</li></ul></ul>
	
	<h2>Ordered list</h2>
	<pre># First item 
## First subitem 
### First subsubitem
## Second subitem</pre>
	<ol><li> First item </li><ol><li> First subitem </li><ol><li> First subsubitem</li></ol><li> Second subitem </li></ol></ol>
	
	
	<h4>Text styles</h4>
	'''Bold''' → <b>Bold</b> (three apostrophes)<br />
	''Italic'' → <i>Italic</i> (two apostrophes)<br />
	'''''Bold and italic''''' → <b><i>Bold and italic</i></b> (five apostrophes)<br />
	'—Strikethrough—' → <strike>Strikethrough</strike><br />
	'__Underlined__' → <u>Underlined</u><br />
	{small}Small text{/small} → <small>Small text</small><br />
	x{sup}10{/sup} → x<sup>10</sup><br />
	x{sub}i{/sub} → x<sub>i</sub><br />
	
	<h4>CSS styles, classes, IDs</h4>
	Since LionWiki 3.1
	<pre>{.first.second#id color: blue; font-size: large}Styled text span with ID and two classes{/}</pre>
	
	<h4>Styled text span with ID and two classes</h4>
	Classes begins with dot, ID with hash sign. Everything after first space is considered to be CSS style, so there can't be any space between or inside classes and ID. Few other possibilities:
	<pre>{. display: block;}Text{/} - neither class, nor id, just style
{.citation}Text{/if} - just class</pre>
	The enclosing element is &lt;span&gt;.
	
	<h4>Emails, links, images</h4>
	Links to other pages can be created like this:<br />
	[Main page] → Main page<br />
	or<br />
	[Main project page|Main page] → Main project page<br />
	or<br />
	[Features header on Main page|Main page#Features] → Features header on Main page<br />
	Emails and web links are automatically recognized:<br />
	http://lionwiki.0o.cz → http://lionwiki.0o.cz<br />
	lionwiki@example.com → lionwiki@example.com<br />
	You can use also relative links, but they have to start with ./ (otherwise it will be interpreted as Wiki page). So if you want to link some HTML page in the same directory, you use:<br />
	[Interesting page|./SomeWebPage.html] → Interesting page<br />
	Or to use a relative path to a file on the same server but in a higher directory, you can use:<br />
	[Interesting File In Parent Directory|./../SomeWebPage.html] → Interesting File In Parent Directory<br />
	
	<h4>Images</h4>
	Image handling was changed a lot in the 3.2 release, see older version of this page if you use older version.<br />
	
	If you need a way to upload images (or other files), check Upload plugin.<br />
	[http://lionwiki.0o.cz/lion.jpg] → <br/>
	
	You can use your image as your link: [http://lionwiki.0o.cz/lion.jpg|link=http://lionwiki.0o.cz/] → <br/>
	You can also place your image to the left or right, possibly combined with other features: [http://lionwiki.0o.cz/lion.jpg|link=http://lionwiki.0o.cz/|center|title=Big Lion|alt=Alt text]<br />
	
	More complex operations with images can be done with ImageExt plugin.<br />
	
	<h4>Subpages</h4>
	Subpages are useful when you need to insert some common content into multiple pages, example could be a navigational menu (by the way, we have Menu plugin.<br />
	
	Syntax: {include:page_to_be_included}.<br />
	
	You can use the same syntax to include page in template (i.e. not in page content), but in this case, whole content of this subpage must be in HTML (you can, but not have to enclose it in {html} and {/html}).<br />
	
	<h4>Multilingual content</h4>
	Let's say you have page named "Biography" in German and you would like to make French variant. Rename page Biography to Biography.de and create page named Biography.fr and insert French translation there. Then visitors whose browser identifies primary language as French will see Biography.fr. It's recommended to create a redirect from page Biography to default language variant. The obvious limitation is that page name has to stay the same in every language variant. That's not such a big problem with "Biography", but it can be worse with other names.<br />
	
	This is recommended way to create multilingual content, there are more ways to do it.<br />
	
	<h4>Redirects</h4>
	If you want to redirect from some page to other, put {redirect:page} at the very beginning of the page. It's not straightforward to edit page containing redirect because every visit will cause redirecting. The only way to edit such page is from search results (as it provides direct edit links), or possibly by manually altering URL.<br />
	
	<h2>Other</h2>
	
	<h4>Table of contents</h4>
	Automatically generated table of contents can be inserted with {TOC} (see demo on the right). It can be used in both pages and templates.<br />
	
	<h4>Characters</h4>
	Some sequences of characters are automatically replaced:<br />
	
	Arrows : &lt;-- : ←, --&gt; : →, &lt;--&gt; : ↔<br />
	Dash : -- : —<br />
	Copyrights : (c) or (C) : ©, (r) or (R) : ®<br />
	
	<h2>Code</h2>
	
	Code syntax is useful when you need to keep original spacing and layout of text, especially for programming source code.<br />
	
	{{import sys<br />
	if len(sys.argv) == 2:<br />
	print "Hello",sys.argv[1]}}<br />
	does:<br />
	<pre>import sys
if len(sys.argv) == 2:
   print "Hello",sys.argv[1]</pre>
	We also have a plugin for syntax highlighting.<br />
	
	<h4>Horizontal line</h4>
	<hr />
	by ----<br />
	<h4>Suppressing wiki syntax</h4>
	By using ^ character before syntax keyword or using {{something}}. If you still don't know how, take a look on wiki code of this page, there are plenty of examples. If you want to insert ^ character, you have to double it, i.e. ^^
	
	<h4>HTML code</h4>
	Do you want to insert youtube video to your site? Or any other HTML code? Just insert it between {html}some html code{/html}. This does not have to work if config value $NO_HTML is set to true. Note that it is serious security risk if you allow users to post HTML code in your wiki.
	
	<h4>HTML entities</h4>
	HTML entities are automatically recognized and left without modification.
	
	<h4>Newline</h4>
	LionWiki joins adjacent newlines together, if you need more than one newline, use {br}.
	
	<h4>Internal comments</h4>
	You can insert internal comments not intended to be displayed using HTML-like syntax &lt;!-- text not intended to be displayed --&gt;
	<?php
}
?>


<script>
$(document).ready (function () {
	$("input[class='submit']").addClass("sub search");
});
</script>

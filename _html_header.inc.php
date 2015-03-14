<?php
/**
 * This is the HTML header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Hit, $Session, $Skin;
global $app_name, $app_version, $xmlsrv_url;
global $baseurl, $baseurlroot, $cururl, $io_charset;
$cururl = preg_replace('/%3F.+$/', '', urlencode(url_absolute(regenerate_url('', '', '', '&'), $baseurlroot)));

global $content_type;
function parse_accept()
{
	$ret = array();
	$acc = @$_SERVER['HTTP_ACCEPT'];
	$arr = explode(',', $acc);
	for ($i = 0; $i < count($arr); $i++)
	{
		$ar2 = explode(';q=', $arr[$i]);
		$type = trim($ar2[0]);

		if ($type == 'text/*' && empty($ret['text/html']))
			$type = 'text/html';
		else if ($type == 'application/*' && empty($ret['application/xhtml+xml']))
			$type = 'application/xhtml+xml';
		else if ($type == '*/*' || $type == '*')
		{
			if (!empty($ret['text/html']) && empty($ret['application/xhtml+xml']))
				$type = 'application/xhtml+xml';
			elseif (empty($ret['text/html']))
				$type = 'text/html';
			else
				continue;
		}

		@$qual = $ar2[1];
		if (empty($qual))
			$qual = 1;

		$ret[$type] = $qual;
	}

	return $ret;
}

function supports_xhtml()
{
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator') !== FALSE)
	{
		global $use_strict;
		$use_strict = FALSE;
		return TRUE;
	}

	$types = parse_accept();
	if (!empty($types['application/xhtml+xml']))
	{
		if (!empty($types['text/html']))
			return $types['application/xhtml+xml'] >= $types['text/html'];
		else
			return $types['application/xhtml+xml'] > 0;
	}
	else
		return FALSE;
}

function supports_link_toolbar()
{
	$ua = $_SERVER['HTTP_USER_AGENT'];
	/* Note: Opera > 12.x is based on WebKit and doesn't have a link toolbar,
	 * but its user-agent is OPR instead of Opera, so we're OK. */
	$ret = preg_match('/Iceape|Opera|SeaMonkey/', $ua); // Graphical browsers
	$ret = $ret || preg_match('/^(Lynx|[Ll]inks)/', $ua); // Text browsers
	$ret = $ret || preg_match('/UdiWWW|i?C[Aa][Bb]|Emacs_W3/', $ua); // Ancient browsers
	return $ret;
}

if (supports_xhtml())
{
	$content_type = 'application/xhtml+xml';
	skin_content_header($content_type);
	echo '<?xml version="1.0" encoding="' . $io_charset . '"?' . '>';
	echo "\n";
}
else
{
	$content_type = 'text/html';
	skin_content_header($content_type);
}

$locale = preg_replace('/(\w{2,3})-.*$/', '$1', locale_lang(false));

function get_full_url($part)
{
	global $Blog;
	global $baseurl;
	return $baseurl . $Blog->siteurl . "/$part";
}

/* If you can think of a better way to do this, you're my hero. */
function get_item($dir)
{
	if (!function_exists('is_valid_query'))
	{
		function is_valid_query($result)
		{
			return ($result !== FALSE && is_array($result) && count($result) > 0);
		}
	}

	global $DB, $Item;
	if (!$Item) return;
	$categorytablename = $Item->main_Chapter->dbtablename;
	$itemtablename = $Item->dbtablename;
	if (!$categorytablename || !$itemtablename) return;
	$row = 0;
	// Here we iterate through the items until we can get an item with a category associated with the current blog
	while (true) {
		/* I do feel dirty about direct DB access (not exactly future-proof),
		 * but I see no alternative. */
		$item_data = $DB->get_row("SELECT post_datestart, post_ID, post_main_cat_ID, post_title, post_urltitle FROM $itemtablename ORDER BY UNIX_TIMESTAMP(post_datestart) $dir", ARRAY_A, $row);
		if (!is_valid_query($item_data))
			return NULL;

		$cat_data = $DB->get_row("SELECT cat_parent_ID, cat_name, cat_blog_ID FROM $categorytablename WHERE cat_ID = " . $item_data['post_main_cat_ID'], ARRAY_A, 0);
		if (!is_valid_query($cat_data))
			return NULL;
		else if ($cat_data['cat_blog_ID'] == $Item->blog_ID)
		{
			if ($item_data['post_urltitle'] != $Item->urltitle)
			{
				$pathinfo = $DB->get_row('SELECT cset_value from T_coll_settings WHERE cset_coll_ID = ' . $Item->blog_ID . ' AND cset_name = \'single_links\'', ARRAY_A, 0);
				$item_data['post_datestart'] = strtotime($item_data['post_datestart']);
				$cat_data['cat_name'] = strtolower($cat_data['cat_name']);

				switch ($pathinfo['cset_value'])
				{
					case 'param_num':
						$item_data['post_urltitle'] = '?p=' . $item_data['post_ID']; 
						break;
					case 'param_title':
						$item_data['post_urltitle'] = '?title=' . $item_data['post_urltitle'];
					case 'short':
						// Do nothing
						break;
					case 'y':
						$item_data['post_urltitle'] = strftime('%Y/', $item_data['post_datestart']) . $item_data['post_urltitle'];
						break;
					case 'ym':
						$item_data['post_urltitle'] = strftime('%Y/%m/', $item_data['post_datestart']) . $item_data['post_urltitle'];
						break;
					case 'ymd':
						$item_data['post_urltitle'] = strftime('%Y/%m/%d/', $item_data['post_datestart']) . $item_data['post_urltitle'];
						break;
					case 'subchap':
						$item_data['post_urltitle'] = $cat_data['cat_name'] . '/' . $item_data['post_urltitle']; 
						break;
					case 'chapters':
						if (isset($cat_data['cat_parent_ID']))
						{
							$parent_cat = $DB->get_row("SELECT cat_name FROM $categorytablename WHERE cat_ID = " . $cat_data['cat_parent_ID'], ARRAY_A, 0);
							$item_data['post_urltitle'] = $cat_data['cat_name'] . '/' . strtolower($parent_cat['cat_name']) .' /' . $item_data['post_urltitle']; 
						}
						else
							$item_data['post_urltitle'] = $cat_data['cat_name'] . '/' . $item_data['post_urltitle']; 
						break;
				}

				return $item_data;
			}
			else
				return NULL;
		}

		$row++;
	}
}

global $first_item, $last_item;
$first_item = get_item('ASC');
$last_item = get_item('DESC');

function get_prevnext_item($which)
{
	global $MainList;
	if ($MainList)
	{
		return $MainList->get_prevnext_Item($which);
	}
	return NULL;
}
/* b2evolution's idea of prev and next seem backwards to me */
global $next_item, $prev_item;
$next_item = get_prevnext_item('prev');
$prev_item = get_prevnext_item('next');

if (!supports_xhtml())
{
	$dtd = '<!DOCTYPE html>';	
	$langattrs ="lang=\"$locale\"";
	$htmlelem = "<html $langattrs>";
}
else
{
	global $use_strict;
	if ($use_strict)
	{
		for ($i = 0; $i < 23; $i++)
			$space .= ' ';
		$dtd = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.1//EN"' . "\n" .
			$space . '"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-2.dtd">';

		$langattrs ="xml:lang=\"$locale\" lang=\"$locale\"";
	}
	else
	{
		for ($i = 0; $i < 8; $i++)
			$space .= ' ';
		$dtd = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"' . "\n" .
			$space . '"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';

		$langattrs="xml:lang=\"$locale\"";
	}

	$htmlelem = "<html xmlns=\"http://www.w3.org/1999/xhtml\" $langattrs>";
}


$params = array_merge( array(
	'auto_pilot'    => 'seo_title',
	'body_class'    => NULL,
	'generator_tag' => '<meta name="generator" content="' . $app_name . ' '.$app_version.'" /><!-- ' . $Skin->T_('Please leave this for stats') . " -->\n",
	'html_tag'      => "$dtd\n$htmlelem\n",
), $params );


echo $params['html_tag'];
?>

<head>
<?php
	if (!supports_xhtml()) echo "<meta charset=\"$io_charset\">\n"; /* Charset for static pages */
	skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */
	$Plugins->trigger_event( 'SkinBeginHtmlHead' );
?>
  <title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title($params);
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
<?php
		skin_description_tag();
		skin_keywords_tag();
		skin_opengraph_tags();
		robots_tag();

		echo $params['generator_tag'];

	if (supports_xhtml() || supports_link_toolbar())
	{
?>
  <link rel="bookmark" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>#content" title="<?php echo $Skin->T_('Main Content'); ?>" />
  <link rel="bookmark" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>#menu" title="<?php echo $Skin->T_('Menu'); ?>" />

<?php
if ('single' == $disp)
		{
?>
  <link rel="bookmark" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>?show=comments&amp;redir=no#comments" title="<?php echo T_('Comments') ?>" />
<?php
		}
?>
  <link rel="top" href="<?php echo $baseurl; ?>" title="<?php echo T_('Go back to home page'); ?>" />

<?php
if ('posts' != $disp)
{
?>
	<link rel="up" href="<?php echo $baseurl . $Blog->siteurl ?>" title="<?php echo htmlspecialchars($Blog->name); ?>" />
<?php
}
else
{
	global $DB;
	$DB->query('SELECT blog_locale, blog_name, blog_siteurl FROM ' . $Blog->dbtablename . ' WHERE blog_ID <> ' . $Blog->ID . ' AND blog_in_bloglist = 1');
	while ($row = $DB->get_row(NULL, ARRAY_A))
	{
		$lang = preg_replace('/^([^-]+)-?.*$/', '$1', $row['blog_locale']);
		if (supports_xhtml())
		{
			if ($use_strict)
			{
				$linklang = "lang=\"$lang\" xml:lang=\"$lang\"";
			}
			else
			{
				$linklang = "xml:lang=\"$lang\"";
			}
		}
		else
			$linklang = "lang=\"$lang\"";
		echo '<link rel="alternate" href="' . $baseurl . $row['blog_siteurl'] . '" title="' . $row['blog_name'] . '" ' . $linklang . ' hreflang="' . $lang . '" />' . "\n";
	}
}

if (NULL !== $first_item)
{
?>
	<link rel="first" href="<?php echo get_full_url($first_item['post_urltitle']) ?>" title="↑ <?php echo htmlspecialchars($first_item['post_title']); ?>" />
<?php
}
if (NULL !== $last_item)
{
?>
	<link rel="last" href="<?php echo get_full_url($last_item['post_urltitle']) ?>" title="<?php echo htmlspecialchars($last_item['post_title']) ?> ↓" />
<?php
}

if (NULL !== $prev_item)
{
?>
  <link rel="prev" href="<?php echo $prev_item->get_permanent_url(); ?>" title="← <?php echo htmlspecialchars($prev_item->title); ?>" />
<?php
}
if (NULL !== $next_item)
{
?>
  <link rel="next" href="<?php echo $next_item->get_permanent_url(); ?>" title="<?php echo htmlspecialchars($next_item->title); ?> →" />
<?php
}}
if ($Blog->get_setting('feed_content') != 'none')
{
	if (file_exists("$skins_path/_esf"))
	{
?>
  <link rel="alternate" type="text/plain" title="ESF 1.0" href="<?php echo $baseurl . $Blog->siteurl; ?>?tempskin=_esf" />
<?php
	}
	if (file_exists("$skins_path/_rss3"))
	{
?>
  <link rel="alternate" type="text/plain" title="RSS 3.0" href="<?php echo $baseurl . $Blog->siteurl; ?>?tempskin=_rss3" />
<?php
	}
	if (!file_exists("$skins_path/_esf") && !file_exists("$skins_path/_rss3"))
	{
?>
  <link rel="alternate" type="application/atom+xml" title="Atom 1.0" href="<?php $Blog->disp('atom_url', 'raw'); ?>" />
<?php
	}
}
?>
  <link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo $xmlsrv_url; ?>rsd.php?blog=<?php echo $Blog->ID; ?>" />
<?php
	require_css('style.css', 'relative', NULL, 'all');
	require_css('speech.css', 'relative', NULL, 'speech');
	require_css('visual.css', 'relative', NULL, 'handheld, print, projection, screen, tty, tv');
	require_css('smallscreen.css', 'relative', NULL, '(max-width: 640px)');
	if (!supports_xhtml() && strftime('%Y') > 2021)
		require_css('ux.css', 'relative', NULL, '(pointer: fine) and (hover: hover) and (min-width: 800px)');
	require_css('print.css', 'relative', NULL, 'print');
	include_headlines(); /* Add javascript and css files included by plugins and skin */

	$Blog->disp( 'blog_css', 'raw');
	$Blog->disp( 'user_css', 'raw');
	$Blog->disp_setting( 'head_includes', 'raw');
?>
</head>

<body<?php skin_body_attrs( array( 'class' => $params['body_class'] ) ); ?>>


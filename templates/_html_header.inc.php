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

global $Skin;
global $app_name, $app_version, $xmlsrv_url;
global $baseurl, $content_type, $io_charset;
global $first_item, $last_item, $next_item, $prev_item;

global $locale;
$locale = preg_replace('/(\w{2,3})-.*$/', '$1', locale_lang(false));

function edk_css_include()
{
	global $Skin;
	global $default_style, $edk_base, $headlines;
	$visual_media = 'handheld, print, projection, screen, tty, tv';

	/* Main CSS files */
	require_css($edk_base.'css/core.css', 'relative', NULL, 'all');
	require_css($edk_base.'css/visual.css', 'relative', NULL, $visual_media);

	/* Alternate CSS files */
	require_css($edk_base.'css/classic.css', 'relative', $Skin->T_('Classic Look'), $visual_media);
	require_css($edk_base.'css/clear.css', 'relative', $Skin->T_('Clear Look'), $visual_media);
	require_css($default_style['file'], 'relative', $default_style['name'], $visual_media);

	/* Media-specific overrides */
	require_css($edk_base.'css/print.css', 'relative', NULL, 'print');
	require_css($edk_base.'css/smallscreen.css', 'relative', NULL, '(max-width: 640px)');
	require_css($edk_base.'css/speech.css', 'relative', NULL, 'speech');

	/* Don't embed style.css, as it doesn't exist in this theme */
	unset($headlines['style.css']);

	/* In XHTML, it needs to be outputted as XML processing instructions,
	 * so do that and remove it from the headlines to include. */
	if (supports_xhtml())
	{
		foreach ($headlines as $file => $elem)
		{
			/* Only for CSS files.  For JS, etc, don't do anything. */
			if (preg_match('/\.css$/', $file))
			{
				$elem = str_replace(array('<link', ' rel="stylesheet"', ' />'), array('<?xml-stylesheet', '', '?>'), $elem);

				/* The default stylesheet shouldn't be alternate */
				if ($file != $default_style['file'])
				{
					$elem = str_replace('title=', 'alternate="yes" title=', $elem);
				}

				echo $elem . "\n";
				unset($headlines[$file]);
			}
		}
	}
}

function edk_get_meta($type, $value, $content, $extra = array())
{
	if (supports_xhtml())
	{
		if ($type != 'charset')
			$r = sprintf('<meta property="%s" content="%s" />', $value, $content);
		else
		{
			global $content_type;
			$r = sprintf('<meta property="Content-Type" content="%s;charset=%s" />', $content_type, $value);
		}
	}
	else
	{
		$r = '<meta ';
		$attrs = array(
			$type => $value,
			'content' => $content,
		);
		$attrs = array_merge($extra, $attrs);

		do
		{
			$key = key($attrs);
			$r .= $key . '="' . $attrs[$key] . '" ';
		} while(next($attrs));
		$r .= '/>';
	}

	return $r;
}

function edk_meta($type, $value, $content, $extra = array())
{
	add_headline(edk_get_meta($type, $value, $content, $extra), $value);
}

function get_full_url($part = '')
{
	global $Blog;
	global $baseurl;

	$r = $baseurl . $Blog->siteurl;
	$r .= empty($part) ? '' : '/' . $part;
	return $r;
}

global $edk_base, $skin;
$edk_base = $Blog->get_local_skins_url().$skin.'/';

global $default_style;
$default_style= array(
	'file' => $edk_base . 'css/transitional.css',
	'name' => $Skin->T_('Transitional Look'),
);

init_content_type();
skin_content_header($content_type);

if (supports_xhtml())
{
	echo '<?xml version="1.0" encoding="' . $io_charset . '"?' . '>';
	echo "\n";
	edk_css_include();
	for ($i = 0; $i < 23; $i++)
		$space .= ' ';

	$dtd = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 2.0//EN"' . "\n" .
	   $space . '"' . $edk_base . 'DTD/xhtml2.dtd">';

	$langattrs = 'xml:base="'. $edk_base . '" xml:lang="' . $locale . '"';
	$htmlelem = "<html xmlns=\"http://www.w3.org/1999/xhtml\" $langattrs>";
}
else
{
	edk_css_include();
	$dtd = '<!DOCTYPE html>';
	$langattrs ="lang=\"$locale\"";
	$htmlelem = "<html $langattrs>";
}

$params = array_merge( array(
	'auto_pilot'    => 'seo_title',
	'body_class'    => NULL,
	'generator_tag' => edk_get_meta('name', 'generator', sprintf('%s %s', $app_name, $app_version)) . '<!-- ' . $Skin->T_('Please leave this for stats') . " -->\n",
	'html_tag'      => "$dtd\n$htmlelem\n",
), $params );


echo $params['html_tag'];
?>

<head>
<?php
echo edk_get_meta('charset', $io_charset);
if (!supports_xhtml())
	skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */
	$Plugins->trigger_event( 'SkinBeginHtmlHead' );
?>

  <title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title($params);
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
<?php

	edk_meta('http-equiv', 'Default-Style', $Skin->T_('Clear Look'));
	edk_meta('name', 'author', $Blog->get_owner_User()->get('fullname'));
	edk_meta('property', 'DC.rights', get_copyright(array('display' => FALSE, 'license' =>  FALSE)));
	edk_meta('property', 'copyright', get_copyright(array('display' =>  FALSE, 'license' =>  FALSE)));
	edk_meta('property', 'license', get_license(array('display' => FALSE, 'format' =>  'text')));

		skin_description_tag();
		skin_keywords_tag();
		skin_opengraph_tags();
		robots_tag();

		echo $params['generator_tag'];

	if (supports_xhtml() || supports_link_toolbar())
	{
		$comment_args = is_text_browser() ? '?show=menu&amp;redir=no' : '';
?>
  <link rel="bookmark" href="<?php echo get_full_url(get_post_urltitle()); ?>#content" title="<?php echo $Skin->T_('Main Content'); ?>" />
  <link rel="bookmark" href="<?php echo get_full_url(get_post_urltitle()) . $comment_args; ?>#menu" title="<?php echo $Skin->T_('Menu'); ?>" />

<?php
if ('single' == $disp)
		{
?>
  <link rel="bookmark" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>?show=comments&amp;redir=no#comments" title="<?php echo __('Comments') ?>" />
<?php
		}
?>
  <link rel="top" href="<?php echo $baseurl; ?>" title="<?php echo __('Go back to home page'); ?>" />

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
			$linklang = "xml:lang=\"$lang\"";
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
	include_headlines(); /* Add javascript and css files included by plugins and the skin */

	$Blog->disp( 'blog_css', 'raw');
	$Blog->disp( 'user_css', 'raw');
	$Blog->disp_setting( 'head_includes', 'raw');
?>
</head>

<body<?php skin_body_attrs( array( 'class' => $params['body_class'] ) ); ?>>


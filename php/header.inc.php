<?php
$latest_major_version = '1.5';
$latest_minor_version = '1.5';
$latest_minor_version_locales = array('cs', 'de', 'en-US', 'es-ES', 'et', 'fr', 'nl', 'pl', 'pt-BR', 'ru', 'sk', 'sv-SE', 'uk');

function get_preferred_locale_from_array($locales)
{
  foreach ($locales as $key => $str) {
    $short = explode('-', $key);
    $short = $short[0];
    if (!isset($locales[$short]))
      $locales[$short] = $str;
  }

  if (isset($_GET['lang'])) {
    $locale = strtolower($_GET['lang']);
    if (isset($locales[$locale]))
      return $locales[$locale];
  }

  $res = 'en-US';
  foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
    $locale = explode(';', $lang);
    $locale = strtolower($locale[0]);
    if (isset($locales[$locale]))
      return $locales[$locale];
    $short = explode('-', $locale);
    $short = $short[0];
    if (isset($locales[$short]) && $res == 'en-US')
      $res = $locales[$short];
  }

  return $res;
}

function get_preferred_website_locale()
{
  if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_GET['lang']))
    return 'en-US';

  $locales = array();
  if ($dir = opendir('l10n')) {
    /* This is the correct way to loop over the directory. */
    while (FALSE !== ($locale = readdir($dir)))
      if ($locale[0] != '.')
        $locales[strtolower($locale)] = $locale;
    closedir($dir);
  }
  $locales['ct'] = 'ct';

  return get_preferred_locale_from_array($locales);
}

$website_locale = get_preferred_website_locale();

class bundle {
  private function read_properties_file_into_array($path, &$array) {
    if (!($file = @fopen($path, 'r')))
      return;

    while (($line = fgets($file, 4096)) !== FALSE) {
      $line = rtrim($line);
      if (strlen($line) == 0 || $line[0] == '#')
        continue;
      $line_array = explode('=', $line, 2);
      $array[$line_array[0]] = $line_array[1];
    }
    fclose($file);
  }

  protected $string_array;
  function __construct($module, $locale = NULL) {
    global $website_locale;
    if (!$locale)
      $locale = $website_locale;
    $array = array();
    $this->read_properties_file_into_array("l10n/en-US/$module.properties", $array);
    if ($locale == 'ct') {
      foreach ($array as $key => $str)
        $array[$key] = preg_replace('@(&?\w+)@e', "\$this->cot('\\1')", $str);
     }
    else if ($locale != 'en-US')
      $this->read_properties_file_into_array("l10n/$locale/$module.properties", $array);
    $this->string_array = $array;
  }

  function cot($str) {
    if ($str[0] == '&')
      return $str;
    if (strlen($str) < 3)
      return 'cot';
    return 'c'.str_repeat('o', strlen($str) - 2).'t';
  }

  function _($key) {
    return $this->string_array[$key];
  }
}

function __($page_content, $bundle, $replacements = NULL)
{
  $localized = preg_replace('@_\((.*?)\)@e', '$bundle->_("\1")', $page_content);
  if ($replacements)
    return strtr($localized, $replacements);
  return $localized;
}

function get_preferred_download_locale()
{
  global $latest_minor_version_locales;

  if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_GET['lang']))
    return 'en-US';

  $locales = array();
  foreach ($latest_minor_version_locales as $locale)
    $locales[strtolower($locale)] = $locale;

  return get_preferred_locale_from_array($locales);
}

function get_preferred_os($screenshot = FALSE)
{
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  if (strpos($user_agent, 'Mac') !== FALSE)
    return 'mac';
  if (strpos($user_agent, 'Linux') !== FALSE)
    return 'linux';
  if ($screenshot && strpos($user_agent, 'Windows NT 6.') !== FALSE)
    return 'winaero';
  return 'windows';
}

function download_url($version, $os, $locale)
{
  if ($os == 'src')
    $suffix = 'src.tgz';
  else {
    $os_suffix = array('windows' => 'win32.installer.exe', 'mac' => 'mac.dmg', 'linux' => 'linux-i686.tar.bz2');
    $suffix = $locale.'.'.$os_suffix[$os];
  }
  return "downloads/$version/instantbird-$version.$suffix";
}

function download_url_auto()
{
  global $latest_minor_version;
  $locale = get_preferred_download_locale();
  $os = get_preferred_os();
  return download_url($latest_minor_version, $os, $locale);
}

function download_button()
{
  global $latest_minor_version;
  $os_info = array('windows' => array('name' => 'Windows', 'size' => '19'),
                   'linux' => array('name' => 'Linux', 'size' => '23'),
                   'mac' => array('name' => 'Mac OS X', 'size' => '42'));
  $version = $latest_minor_version;
  $locale = get_preferred_download_locale();
  $os = get_preferred_os();

  $dl_bundle = new bundle('download');

  $url = download_url($version, $os, $locale);
  $language = $dl_bundle->_($locale);
  $os_name = $os_info[$os]['name'];
  $dl_size = $os_info[$os]['size'].$dl_bundle->_('MB');

  return "        <a href=\"$url\" id=\"download-button\">
          <div id=\"download-button-box\">
          <span id=\"download-title\">".$dl_bundle->_('getInstantbirdFree')."</span>
          <span id=\"download-description\">".$dl_bundle->_('version')." $version, $language<br/>
          $os_name ($dl_size)</span></div>
        </a>
";
}

function download_table($version, $locales, $dl_bundle)
{
  if (!$dl_bundle)
    $dl_bundle = new bundle('download');

  $res = '<table id="table-download">
<tr>
  <th>'.$dl_bundle->_('language').'</th>
  <th>'.$dl_bundle->_('version').'</th>
  <th><img alt="" src="theme/ico-win.png" />Windows</th>
  <th><img alt="" src="theme/ico-osx.png" />Mac&nbsp;OS&nbsp;X</th>
  <th><img alt="" src="theme/ico-tux.png" />Linux</th>
</tr>

';
  $download = $dl_bundle->_('download');
  foreach ($locales as $code) {
    $res .= "        <tr>
          <td>".$dl_bundle->_($code)."</td>
          <td>$version</td>
          <td><a href=\"".download_url($version, 'windows', $code)."\"><img alt=\"\" src=\"theme/ico-win.png\" /><span>$download</span></a></td>
          <td><a href=\"".download_url($version, 'mac', $code)."\"><img alt=\"\" src=\"theme/ico-osx.png\" /><span>$download</span></a></td>
          <td><a href=\"".download_url($version, 'linux', $code)."\"><img alt=\"\" src=\"theme/ico-tux.png\" /><span>$download</span></a></td>
        </tr>

";
  }
  return $res.'</table>
';
}

function footer()
{
  $bundle = new bundle('footer');
  return '    </div>
  </div>

  <div id="footer-shadow">
    <div id="footer">
      <p>&copy; 2007-2013 - <a href="about.html">'.$bundle->_('theInstantbirdTeam').'</a>.</p>
      <p class="small-text">'.$bundle->_('namesAndTrademarksPropertyOfTheirOwner').'</p>
    </div>
  </div>

</body>
</html>';
}

function headerLink($url, $label)
{
  return '<a href="'.$url.'">'.$label.'</a>';
}

function head()
{
  global $title;
  $header = new bundle('header');
  return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta content="text/html; charset=UTF-8" http-equiv="content-type" />
  <title>'.(isset($title) ? $title : 'Instantbird').'</title>
  <link rel="stylesheet" type="text/css" href="theme/home.css" />
  <link rel="shortcut icon" href="favicon.ico" />
  <link rel="alternate" type="application/rss+xml" title="Blog@instantbird.org" href="http://blog.instantbird.org/flux.rss" />
</head>

<body>

  <div id="shadow">
    <div id="main">
      <div id="navigation">
        <a href="." id="logo-top"><span>'.$header->_('homeLink').'</span></a>
        <span></span>'.
  headerLink('http://blog.instantbird.org/', $header->_('blogLink')).
  headerlink('https://addons.instantbird.org/', $header->_('addonsLink')).
  headerlink('faq.html', $header->_('faqLink')).
  headerlink('https://wiki.instantbird.org/', $header->_('developersLink')).
  headerlink('about.html', $header->_('aboutLink')).
'

      </div>

';
}

if (!isset($delayHeader))
  echo head();
?>

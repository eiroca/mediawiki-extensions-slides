<?php
/**
 * Extension: Slides (presentation) WikiMedia extension
 * base upon Slides.php Copyright (c) 2006 by Tels http://bloodgate.com
 *
 * @author Enrico Croce & Simona Burzio (staff@eiroca.net)
 * @copyright Copyright (c) 2009-2019 eIrOcA - Enrico Croce & Simona Burzio
 * @license GPL v2 (http://www.gnu.org/licenses/)
 * @version 1.0.0
 * @link http://www.eiroca.net
 */
$wgExtensionFunctions[] = "wfSlidesExtension";

function wfSlidesExtension() {
  global $wgParser;
  $wgParser->setHook("slides", "efSlideRender");
}
# for Special::Version:
$wgExtensionCredits['parserhook'][] = array (
    'name' => 'Slides (presentation) extension', // 
    'author' => 'eIrOcA', // 
    'url' => 'http://www.eiroca.net/', // 
    'version' => 'v1.0.0'
);

# The callback function for outputting the HTML code
function efSlideRender($sInput, $sParams, $parser = null, $frame = null) {
  if (!$parser) {
    die("Extension Slides: Mediawiki is too old, please upgrade to version 1.5 or higher.");
  }
  global $wgArticlePath;
  global $wgScript;
  # Find out whether we are currently rendering for a preview, or the final
  # XXX TODO: unreliable and unused yet
  global $action;
  if ($action == 'submit') {
    $bPreview = true;
  }
  else {
    $bPreview = false;
  }
  $conf = [ ];
  # Presentation ID
  $conf["id"] = null;
  # the presentation index page
  $conf["index"] = '';
  # prefix for presentation slides
  $conf["prefix"] = '';
  # set a new font size for the body?
  $conf["fontsize"] = '';
  # Navigation bar style
  $conf["style"] = "background: #ffffe0; border: 1px dashed black; padding: 0.2em 0.2em 0.2em 0.5em; margin: 0.5em 0 1em 0;";
  # show nav buttons (|< << >> >|)
  $conf["showbuttons"] = true;
  # hide first-level headline?
  $conf["hideheading"] = true;
  # hide left menu-column?
  $conf["hidemenu"] = true;
  # hide footer?
  $conf["hidefooter"] = true;
  # show only the current page (not the full index)
  $conf["compact"] = false;
  global $_REQUEST;
  $sReqID = $_REQUEST["_E_PID"];
  $sSmall = '85%';
  # Parse the parameters
  foreach ($sParams as $nam => $val) {
    if ($val == "false") {
      $val = false;
    }
    else if ($val == "true") {
      $val = true;
    }
    $conf[$nam] = $val;
  }
  # It's the correct presentation?
  if ($sReqID != null) {
    // This is not the wanted presentation
    if ($conf["id"] != $sReqID) {return;}
  }
  if ($conf["id"] != null) {
    $sReqID = $conf["id"];
    $_REQUEST["_E_PID"] = $sReqID;
    $param = "_E_PID=" . $sReqID;
  }
  # all the entries in the navbar
  $aLinks = array ();
  # Parse slides
  $aParams = explode("\n", $sInput);
  foreach ($aParams as $sCur) {
    $sCur = trim($sCur);
    if ($sCur == '') {
      continue;
    }
    $aLinks[] = $sCur;
  }
  # build the path for article links, "index.php?title=$1" => "index.php?title="
  $sBasePath = $wgArticlePath;
  if ($sBasePath == '') {
    $sBasePath = $wgScript . '/';
  }
  $sBasePath = preg_replace('/\$1/', '', $sBasePath);
  # the page we are currently on from the Parser member mTitle, to make it different
  $sCurrent = $parser->getTitle();
  # turn spaces into underscores
  $sCurrent = preg_replace('/ /', '_', $sCurrent);
  $sPrefix = preg_replace('/ /', '_', $conf["prefix"]);
  $sTitlePrefix = $sPrefix;
  if ($sTitlePrefix != '') {
    $iColPos = strpos(':', $sTitlePrefix);
  }
  if ($iColPos > 0) {
    $sTitlePrefix = substr($sTitlePrefix, $iColPos + 1);
  }
  # 'My presentation - Start' => 'Start'
  if ($sPrefix != '') {
    $sCurrent = preg_replace('/^' . preg_quote($sTitlePrefix) . '/', '', $sCurrent);
  }
  # "/wiki/index.php?title="  =>  "/wiki/index.php?title=My_Presentation"
  $sPath = $sBasePath . $sPrefix;
  # finally generate the HTML output
  # Format the navbar as table (would love to do that as CSS, tho)
  $output = '<table style="font-size:' . $sSmall . ';border:none;background:transparent"><tr>';
  if ($conf["index"]) {
    $output .= '<td style="vertical-align:top">' . _build_link($sBasePath, $conf["index"], $param) . ':&nbsp;</td>';
  }
  $output .= '<td>';
  # we need two passes, in the first one we find the curren topic and subtopic:
  # index of current topic
  $iLastL0 = -1;
  $iCur = -1;
  $iCurL0 = -1;
  # find the current topic
  for ($i = 0; $i < count($aLinks); $i++) {
    # convert all spaces to underscores
    $aTitle = _explode($aLinks[$i]);
    if (preg_match('/^\*/', $aTitle[0])) {
      # subtopic equals current article?
      $iPos = strlen(trim($aTitle[0])) - strlen($sCurrent);
      if ($iPos >= 0) {
        if (strpos($aTitle[0], $sCurrent) === $iPos) {
          $iCurL0 = $iLastL0;
          $iCur = $i;
          break;
        }
      }
    }
    else {
      $iLastL0 = $i;
      # topic equals current article?
      if (strcmp($sCurrent, $aTitle[0]) === 0) {
        $iCurL0 = $i;
        $iCur = $i;
        break;
      }
    }
  }
  #No current item, disable compact mode and do not hide anything
  $bIndex = ($iCur == -1);
  if (($bIndex) || ($bPreview)) {
    $conf["compact"] = false;
    $conf["hidemenu"] = false;
    $conf["hidefooter"] = false;
    $conf["hideheading"] = false;
    $conf["showbuttons"] = false;
    $iCur = 0;
  }
  else {
    $bIndex = false;
  }
  # second pass, build the output
  $bCurrent = false;
  $bSubtopic = false;
  $iFirstSub = 0;
  $sL0 = '';
  $sL1 = '';
  for ($i = 0; $i < count($aLinks); $i++) {
    $sLink = $aLinks[$i];
    if (preg_match('/^\*/', $sLink)) {
      $bSubtopic = true;
      # if we aren't in the current topic, supress the subtopic
      if (!$bCurrent) {
        continue;
      }
      # for each subtopic, count up
      $iFirstSub++;
      # remove the leading '*'
      $sLink = preg_replace('/^\*\s*/', '', $sLink);
    }
    else {
      $bSubtopic = false;
      $iFirstSub = 0;
      $bCurrent = false;
    }
    if ($i == $iCurL0) {
      $bCurrent = true;
    }
    # Article name|Navbar name|Mouseover
    $aTitle = _explode($sLink);
    $sOutL0 = '';
    $sOutL1 = '';
    if (!$bSubtopic) {
      if (($i == $iCur) && (!$bIndex)) {
        $sOutL0 = '<b>' . $aTitle[1] . '</b>';
      }
      else if ($i == $iCurL0) {
        $sOutL0 = _build_link($sPath, $aLinks[$iCurL0], $param);
      }
      else if (!$conf["compact"]) {
        $sOutL0 = _build_link($sPath, $sLink, $param);
      }
    }
    else {
      if (($i == $iCur) && (!$bIndex)) {
        $sOutL1 = '<b>' . $aTitle[1] . '</b>';
      }
      else {
        $sOutL1 = _build_link($sPath, $sLink, $param);
      }
    }
    $sL0 = _addIt($sL0, $sOutL0);
    $sL1 = _addIt($sL1, $sOutL1);
  }
  if ($sL0 != '') {
    $output .= $sL0;
  }
  if ($sL1 != '') {
    $output .= '<br /><span style="font-size: 90%">' . $sL1 . '</span>';
  }
  $output .= '</td></tr></table>';
  # generate next/prev links
  $sButtons = '';
  # only include buttons if not editing the template
  $bOnFirstPage = ($iCur == 0);
  $bOnLastPage = ($iCur == (count($aLinks) - 1));
  if ($conf["showbuttons"]) {
    if (!$bOnFirstPage) {
      $sButtons = _build_link($sPath, $aLinks[0], $param, '|&lt;', 'First page') . '&nbsp;' . _build_link($sPath, $aLinks[$iCur - 1], $param, '&lt;&lt;', 'Previous page');
    }
    if (!$bOnLastPage) {
      $sButtons .= '&nbsp;&nbsp;' . _build_link($sPath, $aLinks[$iCur + 1], $param, '&gt;&gt;', 'Next page', ' ') . '&nbsp;' . _build_link($sPath, $aLinks[count($aLinks) - 1], $param, '&gt;|', 'Last page');
    }
    if ($sButtons != '') {
      $sButtons = "<span style=\"float:right;\">$sButtons&nbsp;</span>";
    }
  }
  # generate style to suppress the different elements
  $aStyles = array ();
  $sMoreStyles = '';
  if ($conf["hidemenu"]) {
    $aStyles[] = '#p-logo,#p-navigation,#p-search,#p-tb';
    $sMoreStyles = '#column-content{ margin: 0 0 0.6em -1em}#content{margin: 2.8em 0 0 1em;}#p-actions{margin-left: 1em;}';
  }
  if ($conf["hidefooter"]) {
    $aStyles[] = '#footer';
  }
  if ($conf["hideheading"]) {
    $aStyles[] = '.firstHeading';
  }
  # maybe we need to set the fontsize
  if ($conf["fontsize"] != '') {
    $sMoreStyles .= "#bodyContent{font-size:" . $conf["fontsize"] . "}";
  }
  # do we need to set some styles?
  if ((count($aStyles) > 0) || ($sMoreStyles != '')) {
    # and we are not in preview ($bPrewview) )
    if (count($aStyles) > 0) {
      $sStyles = join(',', $aStyles) . "{display:none}";
    }
    else {
      $sStyles = '';
    }
    $sStyles = '<style type="text/css">' . "$sStyles$sMoreStyles</style>";
  }
  return "<div id=\"slides-navbar\" style=\"" . $conf["style"] . "\">$sStyles$sButtons$output</div>";
}

function _addIt(&$out, &$new) {
  return ($new == '' ? $out : ($out == '' ? $new : $out . '&nbsp;- ' . $new));
}

function _build_link($sPath, $sTheLink, $param = '', $sOptionalText = '', $sOptionalTitle = '', $sAccessKey = '') {
  # build a link from the prefix and one entry in the link-array
  $aTitle = _explode($sTheLink);
  $sLink = _escape($aTitle[0], true);
  $sText = _escape($aTitle[1], false);
  $sTitle = _escape($aTitle[2], false);
  if ($sOptionalText != '') {
    $sText = $sOptionalText;
  }
  if ($sOptionalTitle != '') {
    $sTitle = $sOptionalTitle;
  }
  # remove the leading '*' from article names
  $sLink = preg_replace('/^\*_*/', '', $sLink);
  if ($sTitle != '') {
    $sTitle = ' title="' . $sTitle . '"';
  }
  if ($sAccessKey != '') {
    $sAccessKey = ' accesskey="' . $sAccessKey . '"';
  }
  # build the link
  $url = $sPath . $sLink;
  if ($param != '') {
    if (strpos("?", $url) >= 0) {
      $url = $url . "?" . $param;
    }
    else {
      $url = $url . "&" . $param;
    }
  }
  return "<a href=\"$url\"$sTitle$sAccessKey>$sText</a>";
}

function _explode($sLink) {
  # split into the three components (page_name|display name|Mouseover text), with defaults (page_name|page name|)
  $aTitle = explode('|', $sLink);
  if (count($aTitle) == 0) {
    $aTitle[0] = $sLink;
  }
  $aTitle[0] = preg_replace('/ /', '_', $aTitle[0]);
  if (count($aTitle) < 2) {
    $aTitle[1] = preg_replace('/_/', ' ', $aTitle[0]);
  }
  if (count($aTitle) < 3) {
    $aTitle[2] = '';
  }
  return $aTitle;
}

function _escape($sName, $bFull = false) {
  # escape important HTML special chars
  $sName = preg_replace('/</', '&lt;', $sName);
  $sName = preg_replace('/>/', '&gt;', $sName);
  $sName = preg_replace('/&/', '&amp;', $sName);
  $sName = preg_replace('/"/', '&quot;', $sName);
  if ($bFull) {
    $sName = preg_replace('/=/', '%3D', $sName);
    $sName = preg_replace('/\?/', '%3F', $sName);
  }
  return $sName;
}
?>
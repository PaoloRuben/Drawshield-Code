<?php  /* Copyright 2010-2021 Karl Wilcox, Mattias Basaglia

This file is part of the DrawShield.net heraldry image creation program

    DrawShield is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

     DrawShield is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with  DrawShield.  If not, see https://www.gnu.org/licenses/.
 */

//
// Global Variables
//
$options = array();
include 'version.inc';
include 'parser/utilities.inc';
include "analyser/utilities.inc";
/**
 * @var DOMDocument $dom
 */
$dom = null;
/**
 * @var DOMXpath $xpath
 */
$xpath = null;
/**
 * @var messageStore $messages
 */
$messages = null;

$spareRoom = str_repeat('*', 1024 * 1024);
$size = 500;

//
// Argument processing
//
if (isset($argc)) {
  if ( $argc > 1 ) { // run in debug mode, probably
    $options['blazon'] = implode(' ', array_slice($argv,1));
  } else {
  if (file_exists('debug.inc')) include 'debug.inc';
  }
}

// Process arguments
$ar = null;
$size = null;

$request = array_merge($_GET, $_POST);

// For backwards compatibility we support argument in GET, but prefer POST
if (isset($_FILES['blazonfile']) && ($_FILES['blazonfile']['name'] != "")) {
    $fileName = $_FILES['blazonfile']['name'];
    $fileSize = $_FILES['blazonfile']['size'];
    $fileTmpName  = $_FILES['blazonfile']['tmp_name'];
    // $fileType = $_FILES['blazonfile']['type']; // not currently used
    if (preg_match('/.txt$/i', $fileName) && $fileSize < 1000000) {
        $options['blazon'] = file_get_contents($fileTmpName);
    }
} else {
    if (isset($request['blazon'])) $options['blazon'] = strip_tags(trim($request['blazon']));
}

if (isset($request['outputformat'])) $options['outputFormat'] = strip_tags ($request['outputformat']);
if (isset($request['saveformat'])) $options['saveFormat'] = strip_tags ($request['saveformat']);
if (isset($request['asfile'])) $options['asFile'] = ($request['asfile'] == "1");
if (isset($request['palette'])) $options['palette'] = strip_tags($request['palette']);
if (isset($request['shape'])) $options['shape'] = strip_tags($request['shape']);
if (isset($request['stage'])) $options['stage'] = strip_tags($request['stage']);
if (isset($request['filename'])) $options['filename'] = strip_tags($request['filename']);
//  if (isset($request['printable'])) $options['printable'] = ($request['printable'] == "1");
if (isset($request['effect'])) $options['effect'] = strip_tags($request['effect']);
if (isset($request['size'])) $options['size']= strip_tags ($request['size']);
if (isset($request['units'])) $options['units']= strip_tags ($request['units']);
if (isset($request['ar'])) $ar = strip_tags ($request['ar']);
if (isset($request['webcols'])) $options['useWebColours'] = $request['webcols'] == 'yes';
if (isset($request['tartancols'])) $options['useTartanColours'] = $request['tartancols'] == 'yes';
if (isset($request['whcols'])) $options['useWarhammerColours'] = $request['whcols'] == 'yes';
if (isset($request['customPalette']) && is_array($request['customPalette'])) $options['customPalette'] = $request['customPalette'];

$options['blazon'] = preg_replace("/&#?[a-z0-9]{2,8};/i","",$options['blazon']); // strip all entities.
$options['blazon'] = preg_replace("/\\x[0-9-a-f]{2}/i","",$options['blazon']); // strip all entities.

 register_shutdown_function(function()
    {
        global $options, $spareRoom;
        $spareRoom = null;
        if ((!is_null($err = error_get_last()))  
              && (!in_array($err['type'], array (E_NOTICE, E_WARNING))) // comment this line to get all
               )
        {
           error_log($err['message'] . " (" . $err['type'] . ") at " . $err['file'] . ":" . $err['line'] . ' - ' ); // . $options['blazon']);
        }
    });

// Quick response for empty blazon
if ( $options['blazon'] == '' ) {
  $dom = new DOMDocument('1.0');
  $dom->loadXML('<blazon ID="N1-0" blazonText="argent" blazonTokens="argent" creatorName="drawshield.net" ><shield ID="N1-6"><simple ID="N1-4"><field ID="N1-3"><tincture ID="N1-1" index="1" origin="given"><colour ID="N1-2" keyterm="argent" tokens="argent" linenumber="1" link="https://drawshield.net/reference/parker/a/argent.html"></colour></tincture></field></simple></shield><messages></messages></blazon>');
} else {
  // log the blazon for research... (unless told not too)
  if ( $options['logBlazon']) error_log($options['blazon']);
  include "parser/parser.inc";
  $p = new parser('english');
  $dom = $p->parse($options['blazon'],'dom');
  $p = null; // destroy parser
  if ( $options['stage'] == 'parser') { 
      $note = $dom->createComment("Debug information - parser stage.\n(Did you do SHIFT + 'Save as File' by accident?)");
      $dom->insertBefore($note,$dom->firstChild);
      header('Content-Type: text/xml; charset=utf-8');
      $dom->outputFormat = true;
      echo $dom->saveXML(); 
      exit; 
  }
  // filter blazon (if present)
  if (file_exists("/var/www/etc/filter.inc")) {
      include "/var/www/etc/filter.inc";
      $filter = new filter($dom);
      $dom = $filter->runFilter();
  }

  // Resolve references
  include "analyser/references.inc";
  $references = new references($dom);
  $dom = $references->setReferences();
  $references = null; // destroy references
  if ( $options['stage'] == 'references') { 
      $note = $dom->createComment("Debug information - references stage.\n(Did you do SHIFT + 'Save as File' by accident?)");
      $dom->insertBefore($note,$dom->firstChild);
      header('Content-Type: text/xml; charset=utf-8');
      $dom->outputFormat = true;
      echo $dom->saveXML(); 
      exit; 
  }
}
// Make the blazonML searchable
$xpath = new DOMXPath($dom);

/*
 * General options tidy-up
 */
if ($options['asFile']) {
    $options['printSize'] = $options['size'];
    $options['size'] = 1000;
}
// Minimum sensible size
if ( $options['size'] < 100 ) $options['size'] = 100;

tidyOptions();;
  // Read in the drawing code  ( All formats start out as SVG )


include "svg/draw.inc";

function report_errors_svg($errno, $errstr, $errfile, $errline)
{
    global $messages;
    if ( $messages )
    {
        $dirname = __dir__;
        if ( substr($errfile, 0, strlen($dirname)) == $dirname )
            $errfile = substr($errfile, strlen($dirname));


        switch ($errno)
        {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $err_type = "error";
                break;
            case E_COMPILE_WARNING:
            case E_WARNING:
            case E_USER_WARNING:
                $err_type = "warning";
                break;
            default:
                $err_type = "notice";
                break;
        }

        $messages->addMessage("$err_type", "$errfile:$errline : $errstr\n");
    }
    return false;
}

if (array_key_exists('HTTP_REFERER',$_SERVER) && strpos($_SERVER["HTTP_REFERER"], "demopage.php") !== false )
    set_error_handler("report_errors_svg");

$output = draw();


// Output content header
if ( $options['asFile'] ) {
    $name = $options['filename'];
    if ($name == '') $name = 'shield';
    $pageWidth = $pageHeight = false;
    if ($options['units'] == 'in') {
	$options['printSize'] *= 90;
    } elseif ($options['units'] == 'cm') {
	$options['printSize'] *= 35;
    }
  $proportion = ($options['shape'] == 'flag') ? $options['aspectRatio'] : 1.2;
  switch ($options['saveFormat']) {
    case 'svg':
        if (substr($name,-4) != '.svg') $name .= '.svg';
     header("Content-type: application/force-download");
     header('Content-Disposition: inline; filename="' . $name );
     header("Content-Transfer-Encoding: text");
     header('Content-Disposition: attachment; filename="' . $name);
     header('Content-Type: image/svg+xml');
     echo $output;
     break;
      case 'pdfLtr':
          $pageWidth = 765;
          $pageHeight = 990;
          // flowthrough
    case 'pdfA4':
        if (!$pageWidth) $pageWidth = 744;
        if (!$pageHeight) $pageHeight = 1051;
    $im = new Imagick();
    $im->readimageblob($output);
    $im->setimageformat('pdf');
    $margin = 40; // bit less than 1/2"
    $maxWidth = $pageWidth - $margin - $margin;
    $imageWidth = $options['printSize'];
    if ($imageWidth > $maxWidth) $imageWidth = $maxWidth;
    $imageHeight = $imageWidth * $proportion;
    $im->scaleImage($imageWidth, $imageHeight);
    $fromBottom = $pageHeight - $margin - $margin - $imageHeight;
    $fromSide = $margin + (($pageWidth - $margin - $margin - $imageWidth) / 2);
    $im->setImagePage($pageWidth,$pageHeight,$fromSide * 0.9,$fromBottom * 0.9);
        if (substr($name,-4) != '.pdf') $name .= '.pdf';
    header("Content-type: application/force-download");
    header('Content-Disposition: inline; filename="' . $name);
    header("Content-Transfer-Encoding: 8bit");
    header('Content-Disposition: attachment; filename="' . $name);
    header('Content-Type: application/pdf');
    echo $im->getimageblob();
    break;
    case 'jpg':
      $im = new Imagick();
      $im->readimageblob($output);
      $im->setimageformat('jpeg');
      $im->setimagecompressionquality(90);
    $imageWidth = $options['printSize'];
    $imageHeight = $imageWidth * $proportion;
      $im->scaleimage($imageWidth,$imageHeight);
        if (substr($name,-4) != '.jpg') $name .= '.jpg';
      header("Content-type: application/force-download");
      header('Content-Disposition: inline; filename="' . $name);
      header("Content-Transfer-Encoding: binary");
      header('Content-Disposition: attachment; filename="' . $name);
      header('Content-Type: image/jpg');
      echo $im->getimageblob();
      break;
    case 'png':
    default:
     $im = new Imagick();
     $im->setBackgroundColor(new ImagickPixel('transparent'));
     $im->readimageblob($output);
     $im->setimageformat('png32');
    $imageWidth = $options['printSize'];
    $imageHeight = $imageWidth * $proportion;
      $im->scaleimage($imageWidth,$imageHeight);
      if (substr($name,-4) != '.png') $name .= '.png';
      header("Content-type: application/force-download");
     header('Content-Disposition: inline; filename="' . $name);
     header("Content-Transfer-Encoding: binary");
     header('Content-Disposition: attachment; filename="' . $name);
     header('Content-Type: image/png');
     echo $im->getimageblob();
     break;
   }
} else {
  switch ($options['outputFormat']) {
    case 'jpg':
      $im = new Imagick();
      $im->readimageblob($output);
      $im->setimageformat('jpeg');
      $im->setimagecompressionquality(90);
      // $im->scaleimage(1000,1200);
      header('Content-Type: image/jpg');
      echo $im->getimageblob();
      break;
    case 'json':
        $newDom = new DOMDocument();
        $newDom->loadXML($output);
      $im = new Imagick();
      $im->setBackgroundColor(new ImagickPixel('transparent'));
      $im->readimageblob($output);
      $im->setimageformat('png32');
      $json = [];
      $json['image'] = base64_encode($im->getimageblob());
      $json['options'] = $options;
      $allMessages = $newDom->getElementsByTagNameNS('http://drawshield.net/blazonML','message');
      $messageArray = [];
     foreach($allMessages as $node) {
          $thisMessage = [];
          for ($i = 0; $i < $node->attributes->length; $i++) {
              $thisMessage[$node->attributes->item($i)->nodeName] = $node->attributes->item($i)->nodeValue;
          }
          $thisMessage['content'] = $node->nodeValue;
          $messageArray[] = $thisMessage;
      }
      $json['messages'] = $messageArray;
      $json['tree'] = $dom->saveXML();
      $baggage = $dom->getElementsByTagNameNS('http://drawshield.net/blazonML','input')->item(0);
      $baggage->parentNode->removeChild($baggage);
      $baggage = $dom->getElementsByTagNameNS('http://drawshield.net/blazonML','messages')->item(0);
      $baggage->parentNode->removeChild($baggage);
      $minTree = $dom->saveXML();
      $minTree = preg_replace('/blazonML:/', '', $minTree);
      $minTree = preg_replace('/<\?xml.*\?>\n/','', $minTree);
      $minTree = preg_replace('/<\/?blazon.*>\n/','', $minTree);
      $minTree = preg_replace('/[<>"]/','', $minTree);
      $json['mintree'] = $minTree;
      header('Content-Type: application/json');
      echo json_encode($json);
      break;
    case 'png':
      $im = new Imagick();
      $im->setBackgroundColor(new ImagickPixel('transparent'));
      $im->readimageblob($output);
      $im->setimageformat('png32');
      // $im->scaleimage(1000,1200);
      header('Content-Type: image/png');
      echo $im->getimageblob();
      break;
    default:
    case 'svg':
      header('Content-Type: text/xml; charset=utf-8');
      echo $output;
      break;
  }
}


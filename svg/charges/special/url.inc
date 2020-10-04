<?php

$url = $node->getAttribute(blazonML::A_TOKENS);
// strip off delimiters
$startPos = strpos($url,'<');
$endPos = strpos($url,'>');
$url = substr($url,$startPos,strlen($url) - $endPos);

error.log($url);

$size = getimagesize($url);

$svg = '<?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0" y="0" height="'
 $size[0] . '" width="' . $size[1] . '" ><g id="body"><image href="' . $url .
 '"  height="' . $size[0] . '" width="' . $size[1] . '" /></g></svg>';

$charge['svgCode'] = $svg;



<?php /* Copyright 2010-2021 Karl Wilcox, Mattias Basaglia

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

require_once("chevron-helper.inc");

$num = $node->getAttribute('number');
if ( $num < 1 ) $num = 1;
if ( existModifierWithKeyterm($node, 'braced')) {
  switch ( $num ) {
    case 1:
      $bodySpec = '<path d="M 503.05,210.05 L 822.26,1240.40 C 822.26,1240.40 711.14,1240.40 713.16,1240.40 C 715.18,1240.40 507.09,551.48
      507.09,551.48 L 288.90,1240.40 C 288.90,1240.40 169.70,1240.40 175.76,1240.40 C 181.82,1240.40 505.07,208.03 503.05,210.05 z" />';
      break;
    case 2:
      $bodySpec = '<path d="M 369.71,210.05 L 139.40,1240.40 L 220.21,1240.40 L 371.73,551.48 L 533.36,1240.40 L 606.09,1240.40 L 369.71,210.05 z" />
      <path d="M 547.50,905.03 L 507.09,747.45 L 628.31,210.05 L 858.62,1240.40 L 779.83,1240.40 L 630.33,551.48 L 547.50,905.03 z" />
      <path d="M 454.56,969.68 L 387.89,1240.40 L 464.67,1240.40 L 492.95,1137.37 L 454.56,969.68 z" />';
      break;
    case 3:
      $bodySpec = '<path d="M 290.92,210.05 L 40.40,1260.60 L 125.25,1260.60 L 292.94,551.48 L 454.56,1224.24 L 496.99,1052.51 L 290.92,210.05 z" />
      <path d="M 349.51,840.38 L 244.45,1288.89 L 321.22,1286.87 L 389.91,1008.07 L 349.51,840.38 z" />
      <path d="M 565.68,840.38 L 466.69,1242.42 L 545.48,1242.42 L 602.05,1008.07 L 565.68,840.38 z" />
      <path d="M 442.44,781.79 L 404.06,632.29 L 501.03,210.05 L 753.57,1250.50 L 672.76,1248.48 L 505.07,551.48 L 442.44,781.79 z" />
      <path d="M 650.53,781.79 L 612.15,634.31 L 713.16,210.05 L 963.68,1250.50 L 876.81,1248.48 L 713.16,551.48 L 650.53,781.79 z" />';
      break;
    case 4:
    default:
      $bodySpec = '<path d="M 197.98,214.09 C 187.88,226.21 -46.46,1268.69 -46.46,1268.69 L 32.32,1270.71 L 193.94,553.50 L 349.51,1216.16 L 391.93,1056.55 L 197.98,214.09 z" />
      <path d="M 157.58,1274.75 C 157.58,1274.75 254.55,838.36 252.53,846.44 C 250.51,854.52 292.94,1016.15 292.94,1016.15 L 226.27,1284.85 L 157.58,1274.75 z" />
      <path d="M 341.43,791.89 L 305.06,626.23 L 393.95,216.11 L 589.92,1062.61 L 549.52,1228.28 L 395.97,551.48 L 341.43,791.89 z" />
      <path d="M 452.54,848.46 L 490.93,1020.19 L 440.42,1236.36 L 359.61,1232.32 L 452.54,848.46 z" />
      <path d="M 654.57,852.50 L 692.96,1014.13 L 636.39,1250.50 L 557.60,1248.48 L 654.57,852.50 z" />
      <path d="M 539.42,785.83 L 503.05,620.17 L 598.01,214.09 L 836.40,1248.48 L 759.63,1246.46 L 598.01,555.52 L 539.42,785.83 z" />
      <path d="M 745.49,785.83 L 703.06,624.21 L 798.02,218.13 L 1038.43,1274.75 L 957.62,1274.75 L 800.04,553.50 L 745.49,785.83 z" />';
      break;
  }

    $dy = 500 - $layout->cross_point->y;
    if ( $dy != 0 )
        $bodySpec = "<g transform='translate(0,-$dy)'>$bodySpec</g>";

    $ordinary['body'] = (string)$bodySpec;
} else {

$chevron_width = chevron_width($layout, 1);
$width = 50;
$dy = 300 / M_SQRT2;
$cy = -$chevron_width / M_SQRT2;
$dy0 = $dy / 2;

$builder = new PathBuilder();
if ( $num % 2 )
{
    $dy0 = $dy;
    build_chevron($layout, $width, $cy, $builder, 0.5);
}

 for ( $i = 0; $i < floor($num / 2); $i++ )
{
     build_chevron($layout, $width, $cy + $dy0 + $dy * $i, $builder, 0.5);
     build_chevron($layout, $width, $cy - $dy0 - $dy * $i, $builder, 0.5);
}

    $ordinary['shape_spec'] = (string)$builder;
}

$ordinary['chargesWithin'] = 'x/x/200,200,180,180:500,900,240,240:800,200,180,180/x/x/x';

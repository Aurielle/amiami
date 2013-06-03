<?php

/**
 * AmiAmi grabber (version 1.0 released on 3.6.2013, http://www.konata.cz)
 *
 * Copyright (c) 2013 Václav Vrbka (aurielle@aurielle.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

/**
 * IMPORTANT!
 *
 * Modify this file and save it as config.php. Don't modify other files unless you know what are you doing.
 * You can adjust the look of generated HTML by modifying the template - file template.latte.
 */

// Source file containing URLs to amiami.jp/com, one URL on each line
$source = __DIR__ . '/figurky_cerven.txt';

// Where to put generated HTML
$output = __DIR__ . '/figurky_cerven.html';

// Flip character names (to jp order)
// WARNING! Name order not consistent through AmiAmi, use at your own risk
$jpNameOrder = FALSE;

// Name of directory where images to figures will be put
// Must have writing permissions
$imgDir = __DIR__ . '/images';

// Name of the template file
$template = __DIR__ . '/template.latte';

// Prefix for images, that means string that would be in front of image filename (typically URL to konata.cz)
$imagePrefix = 'http://www.konata.cz/wp-content/uploads/figurky/';
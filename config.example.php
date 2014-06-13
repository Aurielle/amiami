<?php

/**
 * AmiAmi grabber (version 2.0 released on 13.6.2014, http://www.konata.cz)
 *
 * Copyright (c) 2014 Václav Vrbka (aurielle@aurielle.cz)
 */

/**
 * IMPORTANT!
 *
 * Modify this file and save it as config.php. Don't modify other files unless you know what are you doing.
 * You can adjust the look of generated HTML by modifying the template - file template.latte.
 */

// Text file containing URLs to amiami.com, one URL per line
// URLs have to include gcode parameter in the query string
// Note: __DIR__ means current directory
$source = __DIR__ . '/figurky.txt';

// Path where the output HTML will be created
$output = __DIR__ . '/figurky_out.html';

// EXPERIMENTAL feature: Flip character names to japanese order
// WARNING! Name order not consistent through AmiAmi and will produce nonsense
// with names that aren't simply "Firstname Givenname"
$jpNameOrder = FALSE;

// Path to directory where images of figures will be downloaded
// Must have write permissions
$imgDir = __DIR__ . '/images';

// Path to template file - adjust resulting HTML in this file
$template = __DIR__ . '/template.latte';

// Server image URL prefix, that means string that would be before image filename (typically URL to konata.cz)
// Including the trailing slash
$imagePrefix = 'http://www.konata.cz/wp-content/uploads/figurky/';

// Timeout - how long will the script wait to download each page or image, in seconds
$timeout = 300;

// Whether to strip (Preorder) from the figure name
$stripPreorders = TRUE;
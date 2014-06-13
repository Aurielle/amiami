<?php

/**
 * AmiAmi grabber (version 2.0 released on 13.6.2014, http://www.konata.cz)
 *
 * Copyright (c) 2014 Václav Vrbka (aurielle@aurielle.cz)
 */

namespace Aurielle\AmiamiGrabber;
use Nette;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/libs/Figure.php';
Nette\Diagnostics\Debugger::enable(FALSE, __DIR__ . '/log');
Nette\Utils\SafeStream::register();


// Config loading
$config = __DIR__ . '/config.php';
if (!file_exists($config)) {
	echo 'Config file is missing. Example config should be present, so go by instructions inside.' . PHP_EOL;
	exit;
}

require_once $config;

// Image dir check
if (!is_dir($imgDir) || !is_writable($imgDir)) {
	echo 'Specified image dir does not exist or is not writable.' . PHP_EOL;
	exit;
}


// Get the file contents and begin parsing
$c = file_get_contents($source);
$urls = explode("\n", $c);
$info = array();

foreach ($urls as $url) {
	// Parse figure URL
	$query = parse_url(trim($url), PHP_URL_QUERY);
	$parts = array();
	parse_str($query, $parts);

	// Invalid URL, skip
	if (!isset($parts['gcode'])) {
		echo 'Invalid URL found (no gcode parameter present): ' . $url . PHP_EOL;
		continue;
	}

	// Let the magic do the rest
	$fig = new Figure($parts['gcode']);
	$fig->setTimeout($timeout);
	$fig->setImgDir($imgDir);
	$fig->setJpNameOrder($jpNameOrder);
	$fig->setStripPreorders($stripPreorders);

	try {
		$info[] = $fig->getInfo();

	} catch (FigureException $e) {
		Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		echo '!!! ' . $e->getMessage();
		continue;
	}

	// And wait
	sleep(10);
}

if (empty($info)) {
	echo 'Nothing to render.' . PHP_EOL;
}

// template
$template = new Nette\Templating\FileTemplate($template);
$template->registerFilter(new Nette\Latte\Engine());
$template->registerHelperLoader('Nette\Templating\Helpers::loader');

$template->figures = $info;
$template->imagePrefix = $imagePrefix;
$template->save($output);
echo 'Finished~!' . PHP_EOL;
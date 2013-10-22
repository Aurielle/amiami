<?php

/**
 * AmiAmi grabber (version 1.0 released on 3.6.2013, http://www.konata.cz)
 *
 * Copyright (c) 2013 Václav Vrbka (aurielle@aurielle.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

require_once __DIR__ . '/vendor/autoload.php';
Nette\Diagnostics\Debugger::enable(FALSE, __DIR__ . '/log');

if (!file_exists($config = __DIR__ . '/config.php')) {
	echo 'Config file is missing.' . PHP_EOL;
	exit;
}

require_once $config;

if (!is_dir($imgDir) || !is_writable($imgDir)) {
	echo 'Specified image dir does not exist or is not writable.' . PHP_EOL;
	exit;
}

$c = file_get_contents($source);
$urls = explode("\n", $c);
$info = array();

foreach ($urls as $url) {
	$query = parse_url(trim($url), PHP_URL_QUERY);
	$parts = array();
	parse_str($query, $parts);

	// Invalid URL, skip
	if (!isset($parts['gcode'])) {
		continue;
	}

	// Find the ID
	$figure = array();
	$figure['id'] = $figId = $parts['gcode'];

	// Fetch the pages
	$urlcom = "http://www.amiami.com/top/detail/detail?gcode={$figId}&page=top";
	$urljp = "http://www.amiami.jp/top/detail/detail?gcode={$figId}&page=top";
	try {
		$ch = new Kdyby\Curl\Request($urlcom);
		$ch->setTimeout(60);
		$response = $ch->send();
		$dom = \phpQuery::newDocument($response->getResponse());

	} catch (\Exception $e) {
		Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		echo "!!! Downloading details for figure '$figId' failed, skipping." . PHP_EOL;
		continue;
	}

	// Begin parsing
	$figure['image'] = pq('.product_img_area img')->attr('src');
	$figure['image_basename'] = $filename = basename($figure['image']);
	$figure['image_thumb'] = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.' . pathinfo($filename, PATHINFO_EXTENSION);
	try {
		$fh = fopen('safe://' . $imgDir . '/' . $filename, 'wb');
		$ch = new Kdyby\Curl\Request($figure['image']);
		$ch->setTimeout(60);
		$res = $ch->send();
		fwrite($fh, $res->getResponse());
		fclose($fh);

		// thumb
		$img = Nette\Image::fromString($res->getResponse());
		$img->resize(150, 150, Nette\Image::EXACT);
		$img->save($imgDir . '/' . $figure['image_thumb']);

	} catch (\Exception $e) {
		Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		echo '!!! Error while downloading image ' . $filename . ' (' . get_class($e) . ': ' . $e->getMessage() . '), please download manually: ' . $figure['image'] . PHP_EOL . PHP_EOL;
	}

	$figure['name'] = trim(pq('#title h2')->text());
	$figure['character'] = trim(pq('dl.spec_data')->find('dt:contains(Character Name)')->next('dd')->text());
	if ($jpNameOrder) {
		$orig = $figure['character'];
		$figure['character'] = implode(' ', array_reverse(explode(' ', $orig, 2)));
		$figure['name'] = str_replace($orig, $figure['character'], $figure['name']);
	}

	$figure['manufacturer'] = trim(pq('dl.spec_data')->find('dt:contains(Maker)')->next('dd')->text());
	$figure['link'] = $urlcom;

	$releasedate = trim(pq('dl.spec_data')->find('dt:contains(Release Date)')->next('dd')->text());
	$releasedate = substr($releasedate, strpos($releasedate, ' '));
	$figure['releasedate'] = date('Y/m', strtotime($releasedate));

	// Now the prices from jp version
	try {
		$ch = new Kdyby\Curl\Request($urljp);
		$ch->setTimeout(60);
		$response = $ch->send();
		$dom = \phpQuery::newDocument($response->getResponse());

	} catch (\Exception $e) {
		Nette\Diagnostics\Debugger::log($e, Nette\Diagnostics\Debugger::ERROR);
		@unlink($imgDir . '/' . $figure['image_basename']);
		@unlink($imgDir . '/' . $figure['image_thumb']);
		echo "!!! Downloading prices for figure '$figId' failed, skipping and deleting images." . PHP_EOL;
		continue;
	}

	$figure['price'] = (int) str_replace(',', '', trim(pq('ul > li.selling_price')->text()));
	$internetprice = trim(pq('ul > li.price')->html());
	$internetprice = Nette\Utils\Strings::replace($internetprice, '~<font class="off_price">[^>]+<\/font>~');
	$figure['internetprice'] = (int) str_replace(',', '', $internetprice);

	$info[] = (object) $figure;
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
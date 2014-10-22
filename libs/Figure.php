<?php

/**
 * AmiAmi grabber (version 2.0 released on 13.6.2014, http://www.konata.cz)
 *
 * Copyright (c) 2014 Václav Vrbka (aurielle@aurielle.cz)
 */

namespace Aurielle\AmiamiGrabber;

use Nette;
use Kdyby\Curl;


/**
 * Provides details about one figure
 */
final class Figure extends Nette\Object
{
	/** @var string */
	private $code;

	/** @var array */
	private $info = array();

	/** @var string */
	private $page;

	/** @var int */
	private $timeout;

	/** @var bool */
	private $jpNameOrder;

	/** @var bool */
	private $stripPreorders;

	/** @var string */
	private $imgDir;



	public function __construct($code)
	{
		$this->code = $code;
		$this->info['code'] = $code;
	}


	/**
	 * Returns all parsed info
	 * @return \stdClass
	 */
	public function getInfo()
	{
		if (!$this->page) {
			$this->downloadDetails();
		}

		return (object) $this->info;
	}

	/**
	 * @param boolean $jpNameOrder
	 * @return Figure provides a fluent interface
	 */
	public function setJpNameOrder($jpNameOrder)
	{
		$this->jpNameOrder = (bool) $jpNameOrder;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getJpNameOrder()
	{
		return $this->jpNameOrder;
	}

	/**
	 * @param int $timeout
	 * @return Figure provides a fluent interface
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = (int) $timeout;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param boolean $stripPreorders
	 * @return Figure provides a fluent interface
	 */
	public function setStripPreorders($stripPreorders)
	{
		$this->stripPreorders = (bool) $stripPreorders;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getStripPreorders()
	{
		return $this->stripPreorders;
	}

	/**
	 * @param string $imgDir
	 * @return Figure provides a fluent interface
	 */
	public function setImgDir($imgDir)
	{
		$this->imgDir = $imgDir;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getImgDir()
	{
		return $this->imgDir;
	}



	/**
	 * Runs the download and parsing process
	 */
	private function downloadDetails()
	{
		$this->fetchPage();
		$this->fillInfo();
		$this->processImages();
	}


	/**
	 * Downloads and stores a page
	 */
	private function fetchPage()
	{
		try {
			$url = $this->buildUrl($this->code);
			$ch = $this->curlFactory($url);
			$response = $ch->send();

			$this->page = $response->getResponse();

		} catch (Curl\CurlException $e) {
			throw new FigureException("Downloading details for figure {$this->code} failed, skipping.", $e->getCode(), $e);
		}
	}


	/**
	 * Fetches info from downloaded page
	 */
	private function fillInfo()
	{
		// Load to phpQuery
		$dom = \phpQuery::newDocument($this->page);

		// Image path
		$this->info['image'] = \pq('.product_img_area img')->attr('src');
		$this->info['image_basename'] = basename($this->info['image']);
		$this->info['image_thumb'] = pathinfo($this->info['image_basename'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($this->info['image_basename'], PATHINFO_EXTENSION);

		// Figure name and character
		//$this->info['name'] = trim(\pq('#title h2')->text());
		$this->info['name'] = trim(\pq('title')->text());
		$this->info['name'] = str_replace('AmiAmi [Character & Hobby Shop] | ', '', $this->info['name']);
		if ($this->stripPreorders) {
			$this->info['name'] = str_replace('(Preorder)', '', $this->info['name']);
		}

		$this->info['character'] = trim(\pq('dl.spec_data')->find('dt:contains(Character Name)')->next('dd')->text());
		if ($this->jpNameOrder) {
			$orig = $this->info['character'];
			$this->info['character'] = implode(' ', array_reverse(explode(' ', $orig, 2)));
			$this->info['name'] = str_replace($orig, $this->info['character'], $this->info['name']);
		}

		// Manufacturer and link to figure
		$this->info['manufacturer'] = trim(\pq('dl.spec_data')->find('dt:contains(Maker)')->next('dd')->text());
		$this->info['link'] = $this->buildUrl($this->code);

		// Release date
		$releasedate = trim(\pq('dl.spec_data')->find('dt:contains(Release Date)')->next('dd')->text());
		$releasedate = substr($releasedate, strpos($releasedate, ' '));
		$this->info['releasedate'] = strtotime($releasedate);

		// Prices
		$this->info['price'] = (int) str_replace(',', '', trim(\pq('ul > li.selling_price')->text()));
		$internetprice = trim(\pq('ul > li.price')->html());
		$internetprice = Nette\Utils\Strings::replace($internetprice, '~<(font|span) class="off_price">[^>]+<\/(font|span)>~');
		$this->info['internetprice'] = (int) str_replace(',', '', $internetprice);
	}


	/**
	 * Downloads images and makes thumbnails
	 */
	private function processImages()
	{
		try {
			$fh = fopen('safe://' . $this->imgDir . '/' . $this->info['image_basename'], 'wb');
			$ch = $this->curlFactory($this->info['image']);
			$res = $ch->send();
			fwrite($fh, $res->getResponse());
			fclose($fh);

		} catch (Curl\CurlException $e) {
			throw new FigureException("Downloading image for figure $this->code failed.", $e->getCode(), $e);
		}

		try {
			$img = Nette\Image::fromString($res->getResponse());
			$img->resize(150, 150, Nette\Image::EXACT);
			$img->save($this->imgDir . '/' . $this->info['image_thumb']);

		} catch (\Exception $e) {
			throw new FigureException("Unexpected image-related exception for figure $this->code.", $e->getCode(), $e);
		}
	}


	/**
	 * Creates and returns new curl request
	 * @param $url
	 * @return Curl\Request
	 */
	private function curlFactory($url)
	{
		$ch = new Curl\Request($url);
		$ch->setTimeout($this->timeout);

		return $ch;
	}


	/**
	 * @param string $code
	 * @return string
	 */
	private function buildUrl($code)
	{
		return "http://www.amiami.com/top/detail/detail?gcode={$code}&page=top";
	}
}


class FigureException extends \RuntimeException {}
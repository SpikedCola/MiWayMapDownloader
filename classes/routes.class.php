<?php
/**
 * Helper class for downloading map link and parsing associated route info
 * 
 * @author Jordan Skoblenick <parkinglotlust@gmail.com> 2013-06-01
 */
class routes {
	/**
	 * @var string URL of the page containing map links
	 */
	protected $mapsUrl= 'http://www.mississauga.ca/portal/miway/routemaps';
	
	/**
	 * @var bool [True] to skip maps that we've already downloaded
	 */
	public $skipKnownMaps = false;
	
	/**
	 * @var bool [True] to skip "Local" routes
	 */
	public $skipLocalRoutes = false;
	
	/**
	 * @var bool [True] to skip "Express" routes
	 */
	public $skipExpressRoutes = false;
	
	/**
	 * @var bool [True] to skip "School" routes
	 */
	public $skipSchoolRoutes = false;
	
	/**
	 * @var DOMXPath DOM XPath object
	 */
	protected $xpath;
	
	/**
	 * @var DOMNode The table containing the maps
	 */
	protected $mapTable;
	
	public function __construct() {
		if ($this->skipKnownMaps && !defined('OUTPUT_FOLDER')) {
			throw new Exception('You must define OUTPUT_FOLDER to use $skipKnownRoutes');
		}
	}
	
	/**
	 * Gets map links from the site to be downloaded and processed.
	 * 
	 * @return array An associative array of ('route name' => 'map link')
	 */
	public function getMapLinks() {
		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTMLFile($this->mapsUrl);
		$this->xpath = new DOMXPath($dom);
		$mapTable = $this->xpath->query('//div[@id="myPageContentId"]/table//table/tr[3]');
		if (!$mapTable || !$mapTable->length) {
			throw new Exception('Failed to find table of maps');
		}
		$this->mapTable = $mapTable->item(0);
		return $this->getEnabledRoutes();
	}
	
	/**
	 * Calls getRouteBySelector for each type of route ("local", "express"
	 * and "school", if selected) and handles skipping known maps.
	 * 
	 * @return array An array of ('route name' => 'map link')
	 */
	protected function getEnabledRoutes() {
		$routes = [];
		if (!$this->skipLocalRoutes) {
			$routes = array_merge($routes, $this->getRoutesBySelector('td[1]/ul/li/a'));
		}
		if (!$this->skipExpressRoutes) {
			$routes = array_merge($routes, $this->getRoutesBySelector('td[2]/ul/li/a', 'http://www.mississauga.ca'));
		}
		if (!$this->skipSchoolRoutes) {
			$routes = array_merge($routes, $this->getRoutesBySelector('td[3]/ul/li/a', 'http://www.mississauga.ca'));
		}

		if ($this->skipKnownMaps && $files = glob(OUTPUT_FOLDER.'*.png')) {
			foreach ($files as $file) {
				$route = basename($file, '.png');
				if (isset($routes[$route])) {
					unset($routes[$route]);
				}
			}
		}
		return $routes;
	}
	
	/**
	 * Querys the $mapTable for the given XPath $selector (likely
	 * matching a route table/column). 
	 * 
	 * Returned nodes have their value ("route name") and link saved, 
	 * along with an optional $urlPrefix for the link. The "route name" 
	 * also has any new lines ("\r\n") replaced with a space (" ").
	 * 
	 * @param string $selector The XPath selector string to query on
	 * @param string $urlPrefix An optional prefix for the node link's
	 * "href" value
	 * @return array An array of ('route name' => 'map link')
	 */
	protected function getRoutesBySelector($selector, $urlPrefix = null) {
		$routeNodes = $this->xpath->query($selector, $this->mapTable);
		if (!$routeNodes->length) {
			throw new Exception('Failed to find route nodes matching '.$selector);
		}
		$routes = [];
		foreach ($routeNodes as $route) {
			$routes[str_replace("\r\n", ' ', trim($route->nodeValue))] = ($urlPrefix ? $urlPrefix : '').$route->getAttribute('href');
		}
		return $routes;
	}
}
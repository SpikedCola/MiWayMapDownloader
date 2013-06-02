<?php
/**
 * MiWay Map Downloader
 * 
 * The maps offered on the Mississauga Public Transit ("MiWay")
 * website are high-detail PDFs that render beautifully on a PC, 
 * but not so much on a mobile device. They take 30s to 1m or
 * more on my iPhone 4 in Safari.
 * 
 * "MiWay" has an app in the App Store, but it has very poor
 * ratings, and contacting customer service 3x hasn't lead
 * to a single email or phone response.
 * 
 * That's where this tool comes into play.
 * 
 * "MMD" will grab the PDF maps from the "MiWay" site, crop out 
 * /just/ the map inside (minus the border), and save it as a PNG.
 * 
 * The "routes" class has the option to skip routes that are 
 * already saved - useful since ImageMagick is pretty slow. 
 * 
 * That reminds me, this script @requires ImageMagick & 
 * GhostScript to be installed, or bad things will happen.
 * 
 * You also have the ability to skip types of routes, like
 * "school" or "express" routes, if these are of no use to you.
 * 
 * To configure, define TEMP_FOLDER and OUTPUT_FOLDER, and
 * set any options you wish either as defaults in the "routes"
 * class, or by setting properties of the "routes" object.
 * 
 * @author Jordan Skoblenick <parkinglotlust@gmail.com> May 31, 2013 
 */

define('TEMP_FOLDER', __DIR__.'/temp/'); // for storing temp PDFs/non-cropped png's
define('OUTPUT_FOLDER', __DIR__.'/maps/'); // cropped maps go here

require_once(__DIR__.'/classes/routes.class.php');
require_once(__DIR__.'/classes/map.class.php');

echo "\nSearching for map PDF links...";

try {
	$routes = new routes();
	$routes->skipKnownMaps = true;
	$routes->skipKnownRoutes = true;
	$routes->skipSchoolRoutes = true;
	$links = $routes->getMapLinks();
	if (!$count = count($links)) {
		throw new Exception('Failed to find any map links?');
	}
}
catch (Exception $ex) {
	die("\nError while finding route map links:\n".$ex->getMessage()."\n");
}

echo " Found ".$count."\n";

try {
	foreach ($links as $routeName => $link) {
		$map = new map($link);

		echo "\n+ Route '".$routeName."'";

		echo "\n> Downloading map...";
		$map->download();

		echo "\n> Converting '".basename($link)."' to '".$routeName.".png'...";
		$map->convertToPNG();

		echo "\n> Cropping map...";
		$map->crop();

		echo "\n> Saving new image...";
		$map->save($routeName);

		echo "\n> Done!\n";
	}
}
catch (Exception $ex) {
	die("\nError while processing maps:\n".$ex->getMessage()."\n");
}

echo "\nAll finished!\n";
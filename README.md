# MiWay Map Downloader

## Rationale

The maps offered on the [Mississauga Public Transit](http://www.mississauga.ca/portal/miway/routemaps) ( *MiWay* ) website are high-detail PDFs that render beautifully on a PC, but not so much on a mobile device. They take 30s to 1m or more on my iPhone 4 in Safari, and you can forget about panning the map.

*MiWay* has an app in the App Store, but it has very poor ratings, and contacting *Customer Service (miway.customerservice@mississauga.ca)* 3x hasn't generated a single email or phone response.

So, that's where this tool comes into play.

" **MMD** " will grab the PDF maps from the *MiWay* site, crop out *just* the map inside (minus the border), and save it as a PNG. This works much better, and the maps load nearly instantly.

## Features

The `routes` class has the option to skip routes that are already saved - useful since ImageMagick is pretty slow (5-8s for each image). 

You also have the ability to skip types of routes, like *School* or *Express* routes, if you so choose.

## Dependencies

This script requires [ImageMagick](http://www.imagemagick.org/script/download.php) & [GhostScript](http://www.ghostscript.com/download/gsdnld.html) to be installed, or bad things will happen.

## Configuration 

To configure, define `TEMP_FOLDER` and `OUTPUT_FOLDER`, and set any options you wish either as defaults in the `routes` class, or by setting properties of a `routes` object.
<?php
/**
 * Helper class for downloading maps and processing them into PNGs.
 * 
 * @requires ImageMagick & GhostScript to be installed, or bad 
 * things will happen
 * 
 * @author Jordan Skoblenick <parkinglotlust@gmail.com> 2013-06-01
 */
class map {
	/**
	 * @var int The density in DPI to use when converting 
	 * from pdf to png (300 seems to be a good number, lower
	 * will be smaller and lower quality)
	 */
	protected $density = 300;
	
	/**
	 * @var int Image depth in bits. Only 8 appears 
	 * to work properly
	 */
	protected $depth = 8;
	
	/**
	 * @var array The different possible colors for the border
	 * surrounding the map 
	 */
	protected $borderColors = array(
	    0xFF311A, // red
	    0xFF795A, // orange
	    0x11747E  // dark teal (express)
	); 
	
	/**
	 * @var int The thickness of the border surrounding the map.
	 * 11 was decided through trial-and-error and seems to be
	 * a good number 
	 */
	protected $borderThickness = 11;
	
	/**
	 * @var string The URL that links to the map 
	 */
	protected $link;
	
	/**
	 * @var string The path to the temporary PDF file to be downloaded
	 */
	protected $inputFile;
	
	/**
	 * @var string The path to the temporary PNG to be generated
	 */
	protected $tempOutputFile;
	
	/**
	 * @var resource An image resource representing the final map 
	 */
	protected $mapImage;
	
	public function __construct($link) {
		if (!defined('TEMP_FOLDER')) {
			throw new Exception('TEMP_FOLDER must be defined to use the map class');
		}
		if (!defined('OUTPUT_FOLDER')) {
			throw new Exception('OUTPUT_FOLDER must be defined to use the map class');
		}
		$this->link = $link;
		$this->inputFile = TEMP_FOLDER.basename($this->link); // 'NAV_47.pdf';
	}
	
	/**
	 * Downloads the map to $inputFile
	 */
	public function download() {
		file_put_contents($this->inputFile, file_get_contents($this->link));
	}
	
	/**
	 * Convert the downloaded $inputFile into a PNG
	 */
	public function convertToPNG() {
		$this->tempOutputFile = $this->inputFile.'.png'; // 'NAV_47.pdf.png'

		$command = 'convert -density '.$this->density.' -depth '.$this->depth.' '.$this->inputFile.' '.$this->tempOutputFile.' 2>&1';

		$output = null;
		$code = null;
		exec($command, $output, $code);

		if ($code !== 0) {
			throw new Exception("ImageMagick Error!\n".print_r($output, true));
		}
	}
	
	/**
	 * Crop the PNG down to just the map in the center, 
	 * removing the border.
	 */
	public function crop() {
		$image = imagecreatefrompng($this->tempOutputFile);
		if (!$image) {
			throw new Exception('Failed to load image: '.$this->tempOutputFile);
		}
		$width = imagesx($image);
		$height = imagesy($image);

		// at height/2, find left border
		$leftBorder = null;
		for ($i = 0; $i < $width/2; $i++) {
			$color = imagecolorat($image, $i, $height/2);
			if (in_array($color, $this->borderColors)) {
				$leftBorder = $i+1;
				break;
			}
		}

		// at height/2, find right border
		$rightBorder = null;
		for ($i = $width-1; $i > $width/2; $i--) {
			$color = imagecolorat($image, $i, $height/2);
			if (in_array($color, $this->borderColors)) {
				$rightBorder = $i;
				break;
			}
		}

		// at width/2, find top border
		$topBorder = null;
		for ($i = 0; $i < $height/2; $i++) {
			$color = imagecolorat($image, $width/2, $i);
			if (in_array($color, $this->borderColors)) {
				$topBorder = $i+1;
				break;
			}
		}

		// at width/2. find bottom border
		$bottomBorder = null;
		for ($i = $height-1; $i > $height/2; $i--) {
			$color = imagecolorat($image, $width/2, $i);
			if (in_array($color, $this->borderColors)) {
				$bottomBorder = $i;
				break;
			}
		}

		if ($leftBorder && $rightBorder && $topBorder && $bottomBorder) {
			$topBorder += $this->borderThickness;
			$leftBorder += $this->borderThickness;
			$bottomBorder -= $this->borderThickness;
			$rightBorder -= $this->borderThickness;

			$newWidth = $rightBorder-$leftBorder;
			$newHeight = $bottomBorder-$topBorder;
			$this->mapImage = imagecreatetruecolor($newWidth, $newHeight);

			imagecopy($this->mapImage, $image, 0, 0, $leftBorder, $topBorder, $newWidth, $newHeight);
			
		}
		else {
			throw new Exception('Failed to find 1 or more border(s)');
		}
	}
	
	/**
	 * Save the map to the OUTPUT_FOLDER using the specified $routeName, 
	 * and clean up temporary files.
	 * 
	 * @param string $routeName The route name, or name of the file to be saved
	 */
	public function save($routeName) {
		$outputFile = OUTPUT_FOLDER.$routeName.'.png'; // '47 Ridgeway Loop.png'

		imagepng($this->mapImage, $outputFile);

		unlink($this->inputFile);
		unlink($this->tempOutputFile);
	}
}
<?php
/**
 * PhpCaptcha - A visual and audio CAPTCHA generation library
 * 
 * @author Edward Eliot
 * @license BSD License
 * @link http://www.ejeliot.com/pages/2
 */
namespace CakephpCaptcha\Lib;

use Cake\Core\Exception\Exception;

define('CAPTCHA_SESSION_ID', 'php_captcha');
define('CAPTCHA_WIDTH', 300);
define('CAPTCHA_HEIGHT', 70);
define('CAPTCHA_NUM_CHARS', 5);
define('CAPTCHA_NUM_LINES', 70);
define('CAPTCHA_CHAR_SHADOW', true);
define('CAPTCHA_OWNER_TEXT', '');
define('CAPTCHA_CHAR_SET', '');
define('CAPTCHA_CASE_INSENSITIVE', true);
define('CAPTCHA_BACKGROUND_IMAGES', '');
define('CAPTCHA_MIN_FONT_SIZE', 16);
define('CAPTCHA_MAX_FONT_SIZE', 25);
define('CAPTCHA_USE_COLOUR', true);
define('CAPTCHA_FILE_TYPE', 'jpeg');
define('CAPTCHA_FLITE_PATH', '/usr/bin/flite');
define('CAPTCHA_AUDIO_PATH', '/tmp/'); // must be writeable by PHP process


class PhpCaptcha {

	public $oImage;
	public $aFonts;
	public $iWidth;
	public $iHeight;
	public $iNumChars;
	public $iNumLines;
	public $iSpacing;
	public $bCharShadow;
	public $sOwnerText;
	public $aCharSet;
	public $bCaseInsensitive;
	public $vBackgroundImages;
	public $iMinFontSize;
	public $iMaxFontSize;
	public $bUseColour;
	public $sFileType;
	public $sCode = '';
      
	function __construct($settings = []) {
		$imagesPath = dirname(__FILE__) . DS . 'fonts' . DS;
		
		$aFonts = array(
			$imagesPath . 'VeraBd.ttf',
			$imagesPath . 'VeraIt.ttf',
			$imagesPath . 'Vera.ttf',
			$imagesPath . 'VeraBI.ttf',
			$imagesPath . 'VeraMoBd.ttf',
			$imagesPath . 'VeraMoBI.ttf',
			$imagesPath . 'VeraMoIt.ttf',
			$imagesPath . 'VeraMono.ttf',
			$imagesPath . 'VeraSe.ttf',
			$imagesPath . 'VeraSeBd.ttf' 
		);
		
		$iWidth = CAPTCHA_WIDTH;
		$iHeight = CAPTCHA_HEIGHT;

		// get
		                           // parameters
		$this->aFonts = $aFonts;
		$this->SetNumChars(CAPTCHA_NUM_CHARS);
		$this->SetNumLines(CAPTCHA_NUM_LINES);
		$this->DisplayShadow(CAPTCHA_CHAR_SHADOW);
		$this->SetOwnerText(CAPTCHA_OWNER_TEXT);
		$this->SetCharSet(CAPTCHA_CHAR_SET);
		$this->CaseInsensitive(CAPTCHA_CASE_INSENSITIVE);
		$this->SetBackgroundImages(CAPTCHA_BACKGROUND_IMAGES);
		$this->SetMinFontSize(CAPTCHA_MIN_FONT_SIZE);
		$this->SetMaxFontSize(CAPTCHA_MAX_FONT_SIZE);
		$this->UseColour(CAPTCHA_USE_COLOUR);
		$this->SetFileType(CAPTCHA_FILE_TYPE);
		$this->SetWidth($iWidth);
		$this->SetHeight($iHeight);
	}

	function CalculateSpacing() {
		$this->iSpacing = (int) ($this->iWidth / $this->iNumChars);
	}

	function SetWidth($iWidth) {
		$this->iWidth = $iWidth;
		if ($this->iWidth > 500) {
			$this->iWidth = 500;
		}
		
		$this->CalculateSpacing();
	}
      
      function SetHeight($iHeight) {
         $this->iHeight = $iHeight;
         if ($this->iHeight > 200) $this->iHeight = 200; // to prevent performance impact
      }
      
      function SetNumChars($iNumChars) {
         $this->iNumChars = $iNumChars;
         $this->CalculateSpacing();
      }
      
      function SetNumLines($iNumLines) {
         $this->iNumLines = $iNumLines;
      }
      
      function DisplayShadow($bCharShadow) {
         $this->bCharShadow = $bCharShadow;
      }
      
      function SetOwnerText($sOwnerText) {
         $this->sOwnerText = $sOwnerText;
      }
      
      function SetCharSet($vCharSet) {
         // check for input type
         if (is_array($vCharSet)) {
            $this->aCharSet = $vCharSet;
         } else {
            if ($vCharSet != '') {
               // split items on commas
               $aCharSet = explode(',', $vCharSet);
            
               // initialise array
               $this->aCharSet = array();
            
               // loop through items 
               foreach ($aCharSet as $sCurrentItem) {
                  // a range should have 3 characters, otherwise is normal character
                  if (strlen($sCurrentItem) == 3) {
                     // split on range character
                     $aRange = explode('-', $sCurrentItem);
                  
                     // check for valid range
                     if (count($aRange) == 2 && $aRange[0] < $aRange[1]) {
                        // create array of characters from range
                        $aRange = range($aRange[0], $aRange[1]);
                     
                        // add to charset array
                        $this->aCharSet = array_merge($this->aCharSet, $aRange);
                     }
                  } else {
                     $this->aCharSet[] = $sCurrentItem;
                  }
               }
            }
         }
      }
      
      function CaseInsensitive($bCaseInsensitive) {
         $this->bCaseInsensitive = $bCaseInsensitive;
      }
      
      function SetBackgroundImages($vBackgroundImages) {
         $this->vBackgroundImages = $vBackgroundImages;
      }
      
      function SetMinFontSize($iMinFontSize) {
         $this->iMinFontSize = $iMinFontSize;
      }
      
      function SetMaxFontSize($iMaxFontSize) {
         $this->iMaxFontSize = $iMaxFontSize;
      }
      
      function UseColour($bUseColour) {
         $this->bUseColour = $bUseColour;
      }
      
      function SetFileType($sFileType) {
      	
         // check for valid file type
         if (in_array($sFileType, array('gif', 'png', 'jpeg'))) {
            $this->sFileType = $sFileType;
         } else {
            $this->sFileType = 'jpeg';
         }
      }
      
      function DrawLines() {
         for ($i = 0; $i < $this->iNumLines; $i++) {
            // allocate colour
            if ($this->bUseColour) {
               $iLineColour = imagecolorallocate($this->oImage, rand(100, 250), rand(100, 250), rand(100, 250));
            } else {
               $iRandColour = rand(100, 250);
               $iLineColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
            }
            
            // draw line
            imageline($this->oImage, rand(0, $this->iWidth), rand(0, $this->iHeight), rand(0, $this->iWidth), rand(0, $this->iHeight), $iLineColour);
         }
      }
      
      function DrawOwnerText() {
         // allocate owner text colour
         $iBlack = imagecolorallocate($this->oImage, 0, 0, 0);
         // get height of selected font
         $iOwnerTextHeight = imagefontheight(2);
         // calculate overall height
         $iLineHeight = $this->iHeight - $iOwnerTextHeight - 4;
         
         // draw line above text to separate from CAPTCHA
         imageline($this->oImage, 0, $iLineHeight, $this->iWidth, $iLineHeight, $iBlack);
         
         // write owner text
         imagestring($this->oImage, 2, 3, $this->iHeight - $iOwnerTextHeight - 3, $this->sOwnerText, $iBlack);
         
         // reduce available height for drawing CAPTCHA
         $this->iHeight = $this->iHeight - $iOwnerTextHeight - 5;
      }
      
      function GenerateCode() {
         // reset code
         $this->sCode = '';
         
         // loop through and generate the code letter by letter
         for ($i = 0; $i < $this->iNumChars; $i++) {
            if (count($this->aCharSet) > 0) {
               // select random character and add to code string
               $this->sCode .= $this->aCharSet[array_rand($this->aCharSet)];
            } else {
               // select random character and add to code string
               $this->sCode .= chr(rand(65, 90));
            }
         }
         
         // save code in session variable
         if ($this->bCaseInsensitive) {
            $_SESSION[CAPTCHA_SESSION_ID] = strtoupper($this->sCode);
         } else {
            $_SESSION[CAPTCHA_SESSION_ID] = $this->sCode;
         }
      }
      
      function DrawCharacters() {
         // loop through and write out selected number of characters
         for ($i = 0; $i < strlen($this->sCode); $i++) {
            // select random font
            $sCurrentFont = $this->aFonts[array_rand($this->aFonts)];
            
            // select random colour
            if ($this->bUseColour) {
               $iTextColour = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));
            
               if ($this->bCharShadow) {
                  // shadow colour
                  $iShadowColour = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));
               }
            } else {
               $iRandColour = rand(0, 100);
               $iTextColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
            
               if ($this->bCharShadow) {
                  // shadow colour
                  $iRandColour = rand(0, 100);
                  $iShadowColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
               }
            }
            
            // select random font size
            $iFontSize = rand($this->iMinFontSize, $this->iMaxFontSize);
            
            // select random angle
            $iAngle = rand(-33, 33);
            
            // get dimensions of character in selected font and text size
            $aCharDetails = imageftbbox($iFontSize, $iAngle, $sCurrentFont, $this->sCode[$i], array());
            
            // calculate character starting coordinates
            $iX = $this->iSpacing / 4 + $i * $this->iSpacing;
            $iCharHeight = $aCharDetails[2] - $aCharDetails[5];
            $iY = $this->iHeight / 2 + $iCharHeight / 4; 
            
            // write text to image
            imagefttext($this->oImage, $iFontSize, $iAngle, $iX, $iY, $iTextColour, $sCurrentFont, $this->sCode[$i], array());
            
            if ($this->bCharShadow) {
               $iOffsetAngle = rand(-30, 30);
               
               $iRandOffsetX = rand(-5, 5);
               $iRandOffsetY = rand(-5, 5);
               
               imagefttext($this->oImage, $iFontSize, $iOffsetAngle, $iX + $iRandOffsetX, $iY + $iRandOffsetY, $iShadowColour, $sCurrentFont, $this->sCode[$i], array());
            }
         }
      }
      
      function WriteFile($sFilename) {
         if ($sFilename == '') {
            // tell browser that data is jpeg
            header("Content-type: image/$this->sFileType");
         }
         
         switch ($this->sFileType) {
            case 'gif':
               $sFilename != '' ? imagegif($this->oImage, $sFilename) : imagegif($this->oImage);
               break;
            case 'png':
               $sFilename != '' ? imagepng($this->oImage, $sFilename) : imagepng($this->oImage);
               break;
            default:
               $sFilename != '' ? imagejpeg($this->oImage, $sFilename) : imagejpeg($this->oImage);
         }
      }
      
      function Create($sFilename = '') {
      	
         // check for required gd functions
         if (!function_exists('\imagecreate') || !function_exists("\image$this->sFileType") || ($this->vBackgroundImages != '' && !function_exists('\imagecreatetruecolor'))) {
            throw new Exception('GD Library not found');
         }
         
         // get background image if specified and copy to CAPTCHA
         if (is_array($this->vBackgroundImages) || $this->vBackgroundImages != '') {
            // create new image
            $this->oImage = imagecreatetruecolor($this->iWidth, $this->iHeight);
            
            // create background image
            if (is_array($this->vBackgroundImages)) {
               $iRandImage = array_rand($this->vBackgroundImages);
               $oBackgroundImage = imagecreatefromjpeg($this->vBackgroundImages[$iRandImage]);
            } else {
               $oBackgroundImage = imagecreatefromjpeg($this->vBackgroundImages);
            }
            
            // copy background image
            imagecopy($this->oImage, $oBackgroundImage, 0, 0, 0, 0, $this->iWidth, $this->iHeight);
            
            // free memory used to create background image
            imagedestroy($oBackgroundImage);
         } else {
            // create new image
            $this->oImage = imagecreate($this->iWidth, $this->iHeight);
         }
         
         // allocate white background colour
         imagecolorallocate($this->oImage, 255, 255, 255);
         
         // check for owner text
         if ($this->sOwnerText != '') {
            $this->DrawOwnerText();
         }
         
         // check for background image before drawing lines
         if (!is_array($this->vBackgroundImages) && $this->vBackgroundImages == '') {
            $this->DrawLines();
         }
         
         $this->GenerateCode();
         $this->DrawCharacters();
         
         // write out image to file or browser
         $this->WriteFile($sFilename);
         
         // free memory used in creating image
         imagedestroy($this->oImage);
         
         return true;
      }
      
      // call this method statically
      function Validate($sUserCode, $bCaseInsensitive = true) {
         if ($bCaseInsensitive) {
            $sUserCode = strtoupper($sUserCode);
         }
         
         if (!empty($_SESSION[CAPTCHA_SESSION_ID]) && $sUserCode == $_SESSION[CAPTCHA_SESSION_ID]) {
            // clear to prevent re-use
            unset($_SESSION[CAPTCHA_SESSION_ID]);
            
            return true;
         }
         
         return false;
      }
   }
   
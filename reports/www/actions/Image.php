<?
	require_once("WebPage.php");
	require_once("mime.php");
	require_once("Entity.php");
	require_once("Date.php");
	require_once("img.php");

	$gImageContentType = "image/png";

	function Image_outputHandler($img) {
		global $gImageContentType;
		header('Content-type: '.$gImageContentType);
		header('Content-Length: ' . strlen($img));
		return $img;
	}

	class Image extends WebPage {

		public function doGet() {
			$id = $_GET['id'];
			$entity = $_GET['entity'];
			$width = $_GET['width'];
			$height = $_GET['height'];
			$dimension = $_GET['dim'];
			$minDimension = $_GET['size'];
			$cropFirst = $_GET['cropFirst'];
			$cropWidth = $_GET['cropWidth'];
			$cropHeight = $_GET['cropHeight'];
			$cropX = $_GET['cropX'];
			$cropY = $_GET['cropY'];
			$forceJPGQuality = $_GET['jpg'];

			//sleep(2);

			//die($cropX."x".$cropY."-".$cropWidth."x".$cropHeight);

			//da($_GET);
			//die();

			//if ( $entity == "ItemImageList" || $entity == "ItemImageSearch" ) sleep(3);	// sleep a few seconds

			// include required class:
			$entity = preg_replace("/^http\:\/\//i", "", $entity);
			require_once($entity.".php");

			// fetch item from DB:
			$image = DB::fetchById($entity, $id);
			if ( !$image ) E5::error404();

			$cacheKey = md5($_SERVER['REQUEST_URI']."/".$image->updatedOn);

			// important! this has to be in quotes:
			$etag = "\"".$cacheKey."\"";

			$headers = getAllHeaders();
			
			// check non-changed request:
			if ( $etag == $headers["If-None-Match"] ) {
				// return non-changed!
				header("HTTP/1.1 304 Not Modified");
				header("ETag: ".$etag);
				exit();
			}

			// check cache:
			$cachedFile = E5::PATH_CACHE.$cacheKey.".".$image->ext;
			if ( !$_GET['noCache'] && is_file($cachedFile) ) {
				// output cached image:
				ob_start("Image_outputHandler");
				header("Last-Modified: ".gmdate("D, j M Y G:i:s T", Date::mysql2timestamp($image->updatedOn)) );
				header("ETag: ".$etag);
				header("Accept-Ranges: bytes");
				readfile($cachedFile);
				ob_end_flush();
				return;
			}

			// set cropping for avatars:
			/*if ( $entity == "CustomerImage" || $entity == "FirmImage" ) {
				if ( ! (isset($cropFirst) && isset($cropX) && isset($cropY) && isset($cropWidth) && isset($cropHeight)) ) {
					$cropFirst = true;
					$cropX = $image->cropX;
					$cropY = $image->cropY;
					$cropWidth = $image->cropWidth;
					$cropHeight = $image->cropHeight;
				}
			}*/

			//sleep(2);
			$im = NULL;

			if ( isset($cropFirst) ) {
				if ( isset($cropX) && isset($cropY) && isset($cropWidth) && isset($cropHeight) ) {
					if ( !$thumb ) $im = $this->createImageFor($image);
					else $im = $thumb;
					$srcW = imagesx($im);
					$srcH = imagesy($im);
					$thumb = imagecreatetruecolor($cropWidth, $cropHeight);
					$white = imagecolorallocate($thumb, 255, 255, 255);
					imagefilledrectangle($thumb, 0, 0, $cropWidth, $cropHeight, $white);
					$startX=0;
					$startY=0;

					if($cropY<0){
						$startY=-$cropY;
						$cropHeight+=$cropY;
						$cropY=0;
					}
					if($cropX<0){
						$startX=-$cropX;
						$cropWidth+=$cropX;
						$cropX=0;
					}					
					if($cropY+$cropHeight>$srcH){
						$cropHeight = $srcH-$cropY;
					}
					if($cropX+$cropWidth>$srcW){
						$cropWidth = $srcW-$cropX;
					}
					imagecopyresampled($thumb, $im, $startX, $startY, $cropX, $cropY, $cropWidth, $cropHeight, $cropWidth, $cropHeight);
				}
				else if ( $cropWidth ) {
					if ( !$thumb ) $im = $this->createImageFor($image);
					else $im = $thumb;
					$w = imagesx($im);
					$h = imagesy($im);
					$thumb = imagecreatetruecolor($cropWidth, $h);
					$white = imagecolorallocate($thumb, 255, 255, 255);
					imagefilledrectangle($thumb, 0, 0, $cropWidth, $cropHeight, $white);
					imagecopyresampled($thumb, $im, 0, 0, $w/2-$cropWidth/2, 0, $cropWidth, $h, $cropWidth, $h);
				}
			}

			$w = 0;
			if ( $dimension || $minDimension ) {
				if ( $thumb ) $im = $thumb;
				else if ( !$im ) $im = $this->createImageFor($image);

				$w = imagesx($im);
				$h = imagesy($im);
				if ( $dimension ) {
					// detect what side is larger:
					if ( $w < $h ) $height = $dimension;
					else $width = $dimension;
				}
				else {
					// detect what side is smaller:
					if ( $w < $h ) $width = $minDimension;
					else $height = $minDimension;
				}
			}
			if ( $width || $height ) {
				if ( $thumb ) $im = $thumb;
				else if ( !$im ) $im = $this->createImageFor($image);

				//if ( !$im ) $im = $this->createImageFor($image);
				if ( !$w ) $w = imagesx($im);
				if ( !$h ) $h = imagesy($im);

				if ( $width && $w != $width ) {
					// resize:
					$height = ( $width / $w ) * $h;
					$thumb = imagecreatetruecolor($width, $height);
					imageantialias($thumb, true);
					imageantialias($im, true);
					imagealphablending($thumb, false);
					imagesavealpha($thumb, true);
					$background = imagecolorallocate($thumb, 255, 255, 255);
					imageColorTransparent($thumb, $background);
					imagecopyresampled($thumb, $im, 0, 0, 0, 0, $width, $height, $w, $h);
				}
				else if ( $height && $h != $height ) {
					// resize:
					$width = ( $height / $h ) * $w;
					$thumb = imagecreatetruecolor($width, $height);
					imageantialias($thumb, true);
					imageantialias($im, true);
					imagealphablending($thumb, false);
					imagesavealpha($thumb, true);
					$background = imagecolorallocate($thumb, 255, 255, 255);
					imageColorTransparent($thumb, $background);
					imagecopyresampled($thumb, $im, 0, 0, 0, 0, $width, $height, $w, $h);
				}
				//else $thumb = $im;
			}

			if ( !isset($cropFirst) ) {
				if ( isset($cropX) && isset($cropY) && isset($cropWidth) && isset($cropHeight) ) {
					if ( !$thumb ) $im = $this->createImageFor($image);
					else $im = $thumb;
					//$w = imagesx($im);
					//$h = imagesy($im);
					$thumb = imagecreatetruecolor($cropWidth, $cropHeight);
					imagecopyresampled($thumb, $im, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight, $cropWidth, $cropHeight);
				}
				else if ( $cropWidth ) {
					if ( !$thumb ) $im = $this->createImageFor($image);
					else $im = $thumb;
					$w = imagesx($im);
					$h = imagesy($im);
					$thumb = imagecreatetruecolor($cropWidth, $h);
					imagecopyresampled($thumb, $im, 0, 0, $w/2-$cropWidth/2, 0, $cropWidth, $h, $cropWidth, $h);
				}
			}

			if ( $thumb ) {
				// save to cache:
				if ( $image->ext == "gif" ) {
					$gImageContentType = "image/gif";
					imagegif($thumb, $cachedFile);
				}
				else if ( $image->ext == "jpg" || $forceJPGQuality ) {
					$gImageContentType = "image/jpeg";
					imagejpeg($thumb, $cachedFile, $forceJPGQuality?$forceJPGQuality:90);
				}
				else imagepng($thumb, $cachedFile);
				//$this->logger->w("drawn!");

				// output image:
				ob_start("Image_outputHandler");
				header("Last-Modified: ".gmdate("D, j M Y G:i:s T", Date::mysql2timestamp($image->updatedOn)) );
				header("ETag: ".$etag);
				header("Accept-Ranges: bytes");
				readfile($cachedFile);
				ob_end_flush();
				//$this->logger->w("drawn!");
				return;
			}

			// send content:
			header("Content-Type: ".mimeByExt($image->ext));
			header("Content-Length: ".$image->length);
			header("Last-Modified: ".gmdate("D, j M Y G:i:s T", Date::mysql2timestamp($image->updatedOn)) );
			header("ETag: ".$etag);
			header("Accept-Ranges: bytes");
			//header('Content-Disposition: attachment; filename="'.$file->name.'"');
			print $image->content;
			//$this->logger->w("posted!");
			exit;
		}

		private function createImageFor($image) {
			if ( $image->ext == "bmp" || substr($image->content, 0, 2) == "BM" ) {
				// bmp is not supported natively:
				$tmpFile = tempnam("", "").".bmp";
				$f2 = fopen($tmpFile, "wb");
				fwrite($f2, $image->content);
				fclose($f2);

				$tmpOut = $tmpFile.".png";
				imgConvert($tmpFile, $tmpOut, "", false);
				$im = imagecreatefrompng($tmpOut);
			}
			else $im = imagecreatefromstring($image->content);
			return $im;
		}
	}
?>

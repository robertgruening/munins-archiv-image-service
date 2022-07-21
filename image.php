<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once(__DIR__."/logger.php");

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    Post();
}
else {
	global $logger;
	$logger->error("Unsupported HTTP method!");
}

function Post()
{
	global $logger;
	$logger->info("Post()");

	$isToResize = false;
	$newWidth = 0;
	$kontext = null;

	if (isset($_GET["newWidth"])) {
		$isToResize = true;
		$newWidth = $_GET["newWidth"];
		$logger->debug("option: newWidth=".$newWidth);
	}

	if (isset($_GET["kontext"])) {
		$kontext = $_GET["kontext"];
		
		if (strlen($kontext) == 0) {
			$kontext = "/";
		}
		else {
			if ($kontext[0] == "/") {
				$kontext = substr($kontext, 1);
			}
		
			if ($kontext[strlen($kontext) - 1] != "/") {
				$kontext .= "/";
			}
		}
		
		$logger->debug("option: kontext=".$kontext);
	}

	$file = $_FILES["file"];
	$logger->debug("name=".$file["name"]);
	$logger->debug("tmp_name=".$file["tmp_name"]);
	$logger->debug("size=".$file["size"]);
	$logger->debug("error=".$file["error"]);
	
	$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
	$logger->debug("extension=".$extension);
	
	$isImage = (
		$extension == "jpg" ||
		$extension == "jpeg" ||
		$extension == "gif" ||
		$extension == "png"
	);
	
	if (!$isImage) {
		$logger->error($file["name"]." is not an image!");
	}
	
	$absoluteTempDirectory = __DIR__."/tmp";
	$config = array();
	$config["webdavUsername"] = "aaf";
	$config["webdavPassword"] = "gwf";
	$config["webdavServerUrl"] = "https://127.0.0.1/webdav/";
	$config["webdavFundstellenSubpath"] = "Fundstellen/Akten/";
	
	$logger->debug(move_uploaded_file($file["tmp_name"], $absoluteTempDirectory."/".$file["name"]));
	
	createSubfolders($config["webdavServerUrl"].$config["webdavFundstellenSubpath"].$kontext, $config);
	changeImageWidth($absoluteTempDirectory."/".$file["name"], $newWidth);
	uploadImageFile($absoluteTempDirectory."/".$file["name"], $config["webdavServerUrl"].$config["webdavFundstellenSubpath"].$kontext.$file["name"], $config);
	removeImageFile($absoluteTempDirectory."/".$file["name"]);
}

function createSubfolders($webdavFolderUrl, $config) {
	global $logger;
	$logger->info("creating subfolders");
	
	$segments = explode('/', $webdavFolderUrl);
	$webdavSubfolderUrl = "";
	
	for($i = 0; $i < sizeof($segments); $i++) {
	
		$webdavSubfolderUrl.=$segments[$i]."/";
		
		if (stripos($webdavSubfolderUrl, $config["webdavServerUrl"]."/".$config["webdavFundstellenSubpath"]) === false) {
			continue;
		}

		if (doesSubfolderExist($webdavSubfolderUrl, $config)) {
			continue;
		}
		
		createSubfolder($webdavSubfolderUrl, $config);
	}
}

function doesSubfolderExist($webdavFolderUrl, $config) {
	global $logger;
	$logger->info("checking subfolder");
	
	// HEAD method
	// output to nowhere
	// silent
	// print http_code
	// insecure
	// authentication
	$command = "curl ".
		"-I ".
		"-o /dev/null ".
		"-s ".
		"-w \"%{http_code}\\n\" ".
		"-k ".
		"-u \"".$config["webdavUsername"].":".$config["webdavPassword"]."\" ".
		"\"".$webdavFolderUrl."\";";

	exec($command, $output, $result);

	$logger->debug("Response code for checking \"".$webdavFolderUrl."\" is ".$output[0].".");

	return ($output[0] == "200");
}

function createSubfolder($webdavFolderUrl, $config) {
	global $logger;
	$logger->info("creating subfolder");
	
	// output to nowhere
	// silent
	// print http_code
	// insecure
	// authentication
	// CREATE NEW FOLDER method
	$command = "curl ".
		"-o /dev/null ".
		"-s ".
		"-w \"%{http_code}\\n\" ".
		"-k ".
		"-u \"".$config["webdavUsername"].":".$config["webdavPassword"]."\" ".
		"-X MKCOL \"".$webdavFolderUrl."\";";

	exec($command, $output, $result);

	$logger->debug("Response code for creating \"".$webdavFolderUrl."\" is ".$output[0].".");
}

function changeImageWidth($absoluteFilePath, $newWidth) {
	global $logger;
	$logger->info("changing image size");
	
	$command = "mogrify ".
		"-resize ".$newWidth."x ".
		"'".$absoluteFilePath."';";
	
	exec($command, $output, $result);
	
	$logger->debug("output: ".print_r($output));
	$logger->debug("result: ".$result);
}

function uploadImageFile($absoluteFilePath, $webdavFileUrl, $config) {
	global $logger;
	$logger->info("uploading image file");
	
	$command = "curl ".
		"-u '".$config["webdavUsername"].":".$config["webdavPassword"]."' ".
		"-k ".
		"-T '".$absoluteFilePath."' ".
		"'".$webdavFileUrl."';";
	exec($command, $output, $result);
	
	$logger->debug("output: ".print_r($output));
	$logger->debug("result: ".$result);
}

function removeImageFile($absoluteFilePath) {
	global $logger;
	$logger->info("deleting image file");
	
	if (!unlink($absoluteFilePath)) {
		$logger->error($absoluteFilePath." could not be deleted!");
	}
}
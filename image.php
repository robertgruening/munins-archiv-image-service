<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    Post();
}

function Post()
{
	$isToResize = false;
	$newWidth = 0;
	$kontext = null;

	if (isset($_GET["newWidth"])) {
		$isToResize = true;
		$newWidth = $_GET["newWidth"];
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
	}

	$file = $_FILES["file"];
	$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
	$isImage = (
		$extension == "jpg" ||
		$extension == "jpeg" ||
		$extension == "gif" ||
		$extension == "png"
	);
	
	$absoluteTempDirectory = __DIR__."/tmp";
	$config = array();
	$config["webdavUsername"] = "aaf";
	$config["webdavPassword"] = "gwf";
	$config["webdavServerUrl"] = "https://127.0.0.1/webdav/";
	$config["webdavFundstellenSubpath"] = "Fundstellen/Akten/";
	
	move_uploaded_file($file["tmp_name"], $absoluteTempDirectory."/".$file["name"]);
	createSubfolders($config["webdavServerUrl"].$config["webdavFundstellenSubpath"].$kontext, $config);
	changeImageWidth($absoluteTempDirectory."/".$file["name"], $newWidth);
	uploadImageFile($absoluteTempDirectory."/".$file["name"], $config["webdavServerUrl"].$config["webdavFundstellenSubpath"].$kontext.$file["name"], $config);
	removeImageFile($absoluteTempDirectory."/".$file["name"]);
}

function createSubfolders($webdavFolderUrl, $config) {
	if ($webdavFolderUrl[strlen($webdavFolderUrl) - 1] == "/") {
		$webdavFolderUrl = substr($webdavFolderUrl, 0, strlen($webdavFolderUrl) - 1);
	}
	
	$segments = explode('/', $webdavFolderUrl);
	$webdavSubfolderUrl = "";
	
	for($i = 0; $i < sizeof($segments); $i++) {

		$webdavSubfolderUrl.=$segments[$i]."/";
		
		if (stripos($webdavSubfolderUrl, $config["webdavServerUrl"].$config["webdavFundstellenSubpath"]) === false) {
			continue;
		}

		if (doesSubfolderExist($webdavSubfolderUrl, $config)) {
			continue;
		}
		
		createSubfolder($webdavSubfolderUrl, $config);
	}
}

function doesSubfolderExist($webdavFolderUrl, $config) {
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

	return ($output[0] == "200");
}

function createSubfolder($webdavFolderUrl, $config) {
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
}

function changeImageWidth($absoluteFilePath, $newWidth) {
	$command = "mogrify ".
		"-resize ".$newWidth."x ".
		"'".$absoluteFilePath."';";
	
	exec($command, $output, $result);
}

function uploadImageFile($absoluteFilePath, $webdavFileUrl, $config) {
	$command = "curl ".
		"-u '".$config["webdavUsername"].":".$config["webdavPassword"]."' ".
		"-k ".
		"-T '".$absoluteFilePath."' ".
		"'".$webdavFileUrl."';";
	exec($command, $output, $result);
}

function removeImageFile($absoluteFilePath) {
	unlink($absoluteFilePath);
}
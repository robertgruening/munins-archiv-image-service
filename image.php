<?php

echo "init\n";

$isToResize = false;
$resizeWidth = 0;
$orientation = "landscape"; // landscape | orientation

if (isset($_POST["resizeWidth"])) {
    $isToResize = true;
    $resizeWidth = $_POST["resizeWidth"];
    echo "option: resizeWidth\n";
}

if (isset($_POST["orientation"])) {
    $orientation = $_POST["orientation"];
    echo "option: orientation\n";
}

echo "tmp_name: ".$_FILES["file"]["tmp_name"]."\n";
echo "name: ".$_FILES["file"]["name"]."\n";
echo "size: ".$_FILES["file"]["size"]."\n";


$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["file"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$check = getimagesize($_FILES["file"]["tmp_name"]);

echo "mime: ".$check["mime"]."\n";

echo "done";

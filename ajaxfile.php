<?php 
$image = $_POST['image'];
$location = "./images/";
$image_parts = explode(";base64,", $image);
$image_base64 = base64_decode($image_parts[1]);
$filename = $_POST['name'].".png";
$file = $location . $filename;
file_put_contents($file, $image_base64);
?>
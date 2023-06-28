<html>
<head>
    <title>VR Map</title>
</head>
<body>
<?php
    $url = strval(file_get_contents("https://www.hdgames.net/guild.php"));
    $url = preg_replace('/[ ]{2,}|[\t]/', '', trim($url));
    $url = preg_replace('#\s+#',' ',trim($url));
    $url = str_replace("> <","><",$url);
    $url = str_replace('"../rom/','"https://www.hdgames.net/rom/',$url);
    $loc1 = stripos($url,'<img src="');
    $loc2 = stripos($url,'" width="');
    $loc3 = strlen($url)-(strlen($url)-$loc2)-$loc1;
    $final=str_replace("<img ",'<img id=table ',substr($url,$loc1,$loc3).'"/>');
    print $final;
?>
</body>
</html>
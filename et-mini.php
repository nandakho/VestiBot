<html>
<head>
    <title>Endless Tower - Mini</title>
</head>
<body>
<?php
    $url = strval(file_get_contents("https://www.hdgames.net/mini.php"));
    $url = preg_replace('/[ ]{2,}|[\t]/', '', trim($url));
    $url = preg_replace('#\s+#',' ',trim($url));
    $url = str_replace("> <","><",$url);
    $dat1 = stripos($url,'<span class="cooking">');
    $dat2 = stripos($url,"</span></strong>");
    $dat3 = strlen($url)-(strlen($url)-$dat2)-$dat1;
    $date = substr(substr($url,$dat1,$dat3),-8);
    $loc1 = stripos($url,'<table width="100%" border="1">');
    $loc2 = strrpos($url,'<strong>96F</strong></td></tr></table><p>&nbsp;</p>');
    $loc3 = strlen($url)-(strlen($url)-$loc2)-$loc1;
    $final=str_replace('<table width="100%" border="1">','<table id="table" width="100%" border="1"><tr><td align="center" valign="bottom" colspan="21"><h1><b>Endless Tower - '.$date.'</b></h1></td></tr>',substr($url,$loc1,$loc3)."<strong>96F</strong></td></tr></table><p>&nbsp;</p>");
    print $final;
?>
</body>
</html>
<html>
    <header>
        <title>Vestinel Panel</title>
        <link rel="stylesheet" href="assets/css/members.css" />
    </header>
    <body>
    <?php
	require __DIR__ . '/VestiBot/vendor/autoload.php';
	$dotenv = new Dotenv\Dotenv(__DIR__.'/VestiBot');
	$dotenv->load();
	$servername = $_ENV['DB_SRV'];
	$username = $_ENV['DB_USR'];
	$password = $_ENV['DB_PW'];
	$dbname = $_ENV['DB_NAME'];
    $conn = mysqli_connect($servername,$username,$password,$dbname);
    $que1=mysqli_query($conn,"select active,count(*) from anggota group by active");
    $row1=mysqli_fetch_all($que1);       
    $que2=mysqli_query($conn,"select count(distinct(active)) from anggota");
    $row2=mysqli_fetch_all($que2);
    $total=intval($row2[0][0]);
    $stylen=1;
    $ntab=ceil($total/3);
    $ntabl=0;
    for($aw=0;$aw<$total;$aw++){
        $que3=mysqli_query($conn,"select ign,job,line,ptlead from anggota where active='".$row1[$aw][0]."' order by ptlead desc,ign");
        $row3=mysqli_fetch_all($que3);
        switch($stylen){
            case 1:
                $style=1;
                $ntabl++;
                echo('<table width=115%><tr><td>');
            break;
            case 2:
                $style=2;
            break;
            case 3:
                $style=3;
            break;
        }
        switch($style){
            case 1:
                echo('<table border=1 class=left>');
                $stylen=2;
            break;
            case 2:
                echo('<table border=1 class=mid>');
                $stylen=3;
            break;
            case 3:
                echo('<table border=1 class=right>');
                $stylen=1;
            break;
        }
        echo('<tr><td align="center" colspan="3"><b>Team '.$row1[$aw][0].'</b></td></tr>');
        for($ew=0;$ew<$row1[$aw][1];$ew++){
            echo('<tr');
            if($row3[$ew][3]=="Yes"){
                echo(' bgcolor="#03fcfc"');
            }
            if($row3[$ew][3]=="Vice"){
                echo(' bgcolor="#ffe587"');
            }
            echo('><td align="center">'.$row3[$ew][0].'</td>'.'<td align="center">'.$row3[$ew][1].'</td>'.'<td align="center">'.$row3[$ew][2].'</td></tr>');
        }
        if($stylen==1){
            echo('</td></tr></table>');
        } else {
            if($ntabl==$ntab && $aw==$total){
                echo('</td></tr></table>');
            }
        }
        echo('</table>');
    }
    ?>
    </body>
</html>

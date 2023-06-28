<html>
    <header>
        <title>Vestinel Panel</title>
        <link rel="stylesheet" href="assets/css/members.css" />
    </header>
    <body>
<?php
session_start();
if ( isset( $_SESSION['user_id'] ) ) {
	require __DIR__ . '/VestiBot/vendor/autoload.php';
	$dotenv = new Dotenv\Dotenv(__DIR__.'/VestiBot');
	$dotenv->load();
	$servername = $_ENV['DB_SRV'];
	$username = $_ENV['DB_USR'];
	$password = $_ENV['DB_PW'];
	$dbname = $_ENV['DB_NAME'];
    $conn = mysqli_connect($servername,$username,$password,$dbname);
} else {
    // Redirect them to the login page
    header("Location: https://vestinel.fun/login.php");
}
?>
<ul>
<li><a href="members.php?action=add">Add Member</a></li>
<li><a href="members.php?action=edit&sort=ign">View and Edit Members</a></li>
<li><a href="members.php?action=team">View Team</a></li>
<li class="right"><a class="logout" href="login.php?action=logout">Logout</a></li>
<li class="right"><a href="members.php?action=cpw">Change Password</a></li>
<li class="right"><a><b>User: <?php echo($_SESSION['user_id'])?></b></a></li>
</ul>
<div class="content">
<?php
if(isset($_GET['action'])){
    if($_GET['action']=="add"){
        if(isset($_POST['anick'])){
			$anick=$_POST['anick'];
			$aline=$_POST['aline'];
			$ajob=$_POST['ajob'];
			mysqli_query($conn,"insert into anggota values('".$anick."','".$aline."','".ucwords($ajob)."',0,'N')");
			echo('New member has been registered:<br><table border="1"><tr><td>Nickname:</td><td>'.$anick.'</td></tr><tr><td>Job:</td><td>'.$ajob.'</td></tr><tr><td>LINE:</td><td>'.$aline.'</td></tr></table><br>');
		} else {
        }
        echo('<form method="post" action="members.php?action=add"><div><div><input type="text" name="anick" required="required" placeholder="Nickname" /></div><div><select name="ajob" required="required" id="job"><option value="">Job</option>');
        $query=mysqli_query($conn,"select job from job order by job");
        $row=mysqli_fetch_all($query);       
        $querys=mysqli_query($conn,"select count(*) from job");
        $tot=mysqli_fetch_all($querys);
        $rown=intval($tot[0][0]);
        for($a=0;$a<$rown;$a++){
            echo('<option value="'.$row[$a][0].'">'.$row[$a][0].'</option>');
        }
        echo('</select></div><div><input type="text" name="aline" required="required" placeholder="LINE" /></div><div><input type="submit" value="Add Member" /></div></div></form>');
    }

    if($_GET['action']=="edit"){
        if(isset($_GET['nick'])){
            if(isset($_POST['nicku'])){
                mysqli_query($conn,'update anggota set ign="'.$_POST['nicku'].'" where ign="'.$_GET['nick'].'"');
                mysqli_query($conn,'update anggota set line="'.$_POST['lineu'].'" where ign="'.$_GET['nick'].'"');
                mysqli_query($conn,'update anggota set job="'.$_POST['jobu'].'" where ign="'.$_GET['nick'].'"');
                mysqli_query($conn,'update anggota set active="'.$_POST['actu'].'" where ign="'.$_GET['nick'].'"');
                mysqli_query($conn,'update anggota set ptlead="'.$_POST['leadu'].'" where ign="'.$_GET['nick'].'"');
                echo("Data has been updated!");
            } else {
                $query=mysqli_query($conn,'select ign,line,job,active,ptlead from anggota where ign="'.$_GET['nick'].'"');
                $row=mysqli_fetch_all($query);       
                echo('<form method="post" action="members.php?action=edit&nick='.$_GET['nick'].'"><table border="1"><tr><td align="center"><b>Nickname</b></td><td align="center"><b>Job</b></td><td align="center"><b>Line</b></td><td align="center"><b>Team</b></td><td align="center"><b>Party Lead</b></td><tr>');
                echo('<tr><td><input type="text" name="nicku" required="required" value="'.$row[0][0].'"/></td><td><select name="jobu" required="required" id="job">');
                $querys=mysqli_query($conn,'select job from job where job!="'.$row[0][2].'" order by job');
                $rows=mysqli_fetch_all($querys);       
                $queryss=mysqli_query($conn,"select count(*) from job");
                $tots=mysqli_fetch_all($queryss);
                $rown=intval($tots[0][0])-1;
                for($a=0;$a<$rown;$a++){
                    echo('<option value="'.$rows[$a][0].'">'.$rows[$a][0].'</option>');
                }
                echo('<option selected="selected">'.$row[0][2].'</option></select></td><td><input type="text" name="lineu" required="required" value="'.$row[0][1].'"/></td><td><input type="text" name="actu" required="required" value="'.$row[0][3].'"/></td><td align=center><select name="leadu" required="required" id="lead"><option selected="selected">'.$row[0][4].'</option><option value=');
                if($row[0][4]=='Yes'){
                    echo('"No">No</option><option value="Vice">Vice</option>');
                } 
                if($row[0][4]=='No'){
                    echo('"Vice">Vice</option><option value="Yes">Yes</option>');
                }
                if($row[0][4]=='Vice'){
                    echo('"No">No</option><option value="Yes">Yes</option>');
                }
                echo('</select></td></tr><tr><td colspan="5" align="center"><input type="submit" value="Save Changes" /></td></tr></table></form>');
            }
        } else {
            echo('<table border="1"><tr><td align="center"><b>No</b></td><td align="center"><b><a href="members.php?action=edit&sort=ign">Nickname</a></b></td><td align="center"><b><a href="members.php?action=edit&sort=job">Job</a></b></td><td align="center"><b><a href="members.php?action=edit&sort=line">Line</a></b></td><td align="center"><b><a href="members.php?action=edit&sort=active">Team</a></b></td><td align="center"><b><a href="members.php?action=edit&sort=ptlead">Party Lead</a></b></td><td><b>Remove</b></td><tr>');
            $query=mysqli_query($conn,"select ign,line,job,active,ptlead from anggota order by ".$_GET['sort']);
            $row=mysqli_fetch_all($query);       
            $querys=mysqli_query($conn,"select count(*) from anggota");
            $tot=mysqli_fetch_all($querys);
            $rown=intval($tot[0][0]);
            for($a=0;$a<$rown;$a++){
                $no=$a+1;
                echo('<tr><td align="center">'.$no.'</td><td><a href="members.php?action=edit&nick='.$row[$a][0].'">'.$row[$a][0].'</a></td><td>'.$row[$a][2].'</td><td>'.$row[$a][1].'</td><td align="center">'.$row[$a][3].'</td><td align="center">'.$row[$a][4].'</td><td align="center"><a href="members.php?action=del&nick='.$row[$a][0].'&line='.$row[$a][1].'&job='.$row[$a][2].'&act='.$row[$a][3].'&ptlead='.$row[$a][4].'&yes=0">X</a></tr>');
            }
            echo('</table>');
        }
    }

    if($_GET['action']=="cpw"){
        if(isset($_POST['pass'])){
            $pass=md5($_POST['pass']);
            $npass1=md5($_POST['npass1']);
            $npass2=md5($_POST['npass2']);
            $que=mysqli_query($conn,'select count(*) from webid where user="'.$_SESSION['user_id'].'" and pass="'.$pass.'"');
            $fetch=mysqli_fetch_all($que);
            if($fetch[0][0]==1){
                if($npass1==$npass2){
                    mysqli_query($conn,'update webid set pass="'.$npass1.'" where user="'.$_SESSION['user_id'].'"');
                    echo('Password has been updated!<br>');
                } else {
                    echo("Password doens't match!<br>");
                }
            } else {
                echo('Wrong Password!<br>');
            }
        }
        echo('<form method="post" action="members.php?action=cpw"><div><div><input type="password" name="pass" required="required" placeholder="Old Password" /></div><div><input type="password" name="npass1" required="required" placeholder="New Password" /></div><div><input type="password" name="npass2" required="required" placeholder="Confirm New Password" /></div><div><input type="submit" value="Save Changes" /></div></div></form>');
    }

    if($_GET['action']=="del"){
        if($_GET['yes']=="1"){
            mysqli_query($conn,'delete from anggota where ign="'.$_GET['nick'].'" and line="'.$_GET['line'].'" and job="'.$_GET['job'].'" and active="'.$_GET['act'].'"');
            echo('Member deleted!');
        } else {
            echo('Are you sure you want to remove this member?<br><table border="1"><tr><td align="center"><b>Nick</b></td><td align="center"><b>Job</b></td><td align="center"><b>Line</b></td><td align="center"><b>Team</b></td></tr><tr><td>'.$_GET['nick'].'</td><td>'.$_GET['job'].'</td><td>'.$_GET['line'].'</td><td>'.$_GET['act'].'</td></tr><tr><td colspan="2" align="center"><b><a href="members.php?action=del&nick='.$_GET['nick'].'&line='.$_GET['line'].'&job='.$_GET['job'].'&act='.$_GET['act'].'&yes=1">Yes</a></b></td><td colspan="2" align="center"><b><a href=members.php?action=edit&sort=ign>No</a></b></td></tr>');
        }
    }

    if($_GET['action']=="team"){
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
                    echo('<table width=111%><tr><td>');
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
    }
}
?>
</div>
</body>
</html>
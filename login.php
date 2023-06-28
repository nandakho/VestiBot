<?php
	require __DIR__ . '/VestiBot/vendor/autoload.php';
	$dotenv = new Dotenv\Dotenv(__DIR__.'/VestiBot');
	$dotenv->load();
	$servername = $_ENV['DB_SRV'];
	$username = $_ENV['DB_USR'];
	$password = $_ENV['DB_PW'];
	$dbname = $_ENV['DB_NAME'];
	$conn = mysqli_connect($servername,$username,$password,$dbname);
	session_start();
	if(isset($_GET['action'])){
		if($_GET['action']=="logout"){
			session_destroy();
			print('Logged out! Click <a href="./login.php">here</a> if you are not redirected');
			header("refresh:3;url=login.php");
		}
	} else {
		if(isset($_POST['user'])){
			$user=$_POST['user'];
			$pass=md5($_POST['pass']);
			$que=mysqli_query($conn,'select count(*) from webid where user="'.$user.'" and pass="'.$pass.'"');
			$fetch=mysqli_fetch_all($que);
			if($fetch[0][0]==1){
				$_SESSION['user_id']=$user;
				print('Login success! Click <a href="./members.php">here</a> if you are not redirected');
				header( "refresh:3;url=members.php" );
			} else {
				echo('Username or Password is not valid! Click <a href="./login.php">here</a> if you are not redirected');
				header( "refresh:3;url=login.php" );
			}
		} else {
			echo('<html><head><title>Vestinel Panel</title></head><body><form method="post" action="login.php"><div><div><input type="text" name="user" required="required" placeholder="Username" /></div><div><input type="password" name="pass" required="required" placeholder="Password" /></div><div><input type="submit" value="Login" /></div></div></form></body></html>');
		}
	}
?>
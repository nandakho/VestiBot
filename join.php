<html>
	<head>
		<title>Vestinel Guild</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
	</head>
	<body class="is-preload">
        <?php
		require __DIR__ . '/VestiBot/vendor/autoload.php';
		$dotenv = new Dotenv\Dotenv(__DIR__.'/VestiBot');
		$dotenv->load();
		$servername = $_ENV['DB_SRV'];
		$username = $_ENV['DB_USR'];
		$password = $_ENV['DB_PW'];
		$dbname = $_ENV['DB_NAME'];
        	$conn = mysqli_connect($servername,$username,$password,$dbname);
		if($_POST['name']){
			$nama=$_POST['name'];
		} else {
			$nama="-";
		}
		if($_POST['nick']){
			$nick=$_POST['nick'];
		} else {
			$nick="-";
		}
		if($_POST['gender']){
			$gender=$_POST['gender'];
		} else {
			$gender="-";
		}
		if($_POST['line']){
			$line=$_POST['line'];
		} else {
			$line="-";
		}
		if($_POST['disc']){
			$disc=$_POST['disc'];
		} else {
			$disc="-";
		}
		if($_POST['job']){
			$job=$_POST['job'];
		} else {
			$job="-";
		}
		if($_POST['contri']){
			$contri=$_POST['contri'];
		} else {
			$contri="0";
		}
		if($_POST['gm']){
			$gm=$_POST['gm'];
		} else {
			$gm="0";
		}
		if($_POST['message']){
			$text=str_replace('"',"'",strval($_POST["message"]));
		} else {
			$text="-";
		}
		mysqli_query($conn,'insert into recruitment values("'.$nama.'","'.$nick.'","'.$gender.'","'.$line.'","'.$disc.'","'.$job.'","'.$contri.'","'.$gm.'","'.$text.'")');
        ?>
		<!-- Header -->
			<section id="header">
				<header>
					<a href=".."><img src="images/Vestinel.png" width="320" height="199" alt="Logo"/></a>
					<p>Record has been submited!<br />We will contact you soon <?php echo $_POST["name"];?></p>
				</header>
            </section>
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/jquery.scrolly.min.js"></script>
			<script src="assets/js/jquery.poptrox.min.js"></script>
			<script src="assets/js/browser.min.js"></script>
			<script src="assets/js/breakpoints.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>

	</body>
</html>

<?php

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;
use \LINE\LINEBot\MessageBuilder\RawMessageBuilder;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response){
	$servername = $_ENV['DB_SRV'];
	$username = $_ENV['DB_USR'];
	$password = $_ENV['DB_PW'];
	$dbname = $_ENV['DB_NAME'];
	$conn = mysqli_connect($servername,$username,$password,$dbname);   
	$querys=mysqli_query($conn,"select count(*) from anggota");
	$tot=mysqli_fetch_all($querys);
	$rown=intval($tot[0][0]);
	return "Vestinel Bot is running!\nCurrent active members: ".$rown;
});

$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];
	
	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}

	// is this request comes from LINE?
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	$ch = curl_init();
	$apiexch = "https://api.poporing.life/";
	$apihist = "https://www.romexchange.com/api?item=";
	$apisrch = "https://www.romexchange.com/api?exact=false&slim=true&item=";
	$servername = $_ENV['DB_SRV'];
	$username = $_ENV['DB_USR'];
	$password = $_ENV['DB_PW'];
	$dbname = $_ENV['DB_NAME'];
	$conn = mysqli_connect($servername,$username,$password,$dbname); 
	$conns = mysqli_connect($servername,$username,$password,"test");  
	$data = json_decode($body, true);
	foreach ($data['events'] as $event){
		$userMessage = $event['message']['text'];
		$userID = $event['source']['userId'];
		$ttype = $event['source']['type'];
		$groupID = $event['source']['groupId'];
		$saus=$bot->getProfile($userID);
		$bquery=mysqli_query($conn,"select count(*) from userid where uid='".$userID."'");
		$btot=mysqli_fetch_all($bquery);
		$uidexist=intval($btot[0][0]);
		if($saus->isSucceeded()){
			$uprof=$saus->getJSONDecodedBody();
			$dpname=$uprof['displayName'];
			if($uidexist==0){
				mysqli_query($conn,"insert into userid values('".$userID."','".$dpname."')");
			}
		} else {
			if($uidexist==0){
				mysqli_query($conn,"insert into userid values('".$userID."','".$userID."')");
			} else {
				$dptemp1=mysqli_query($conn,"select line from userid where uid='".$userID."'");
				$dptemp2=mysqli_fetch_all($dptemp1);
				$dpname=$dptemp2[0][0];
			}
		}
		$text = strval($userMessage);
		$text_arr = str_getcsv($text," ",'"');
		$aquery=mysqli_query($conn,"select count(*) from admin where uid='".$userID."'");
		$atot=mysqli_fetch_all($aquery);
		$adm=intval($atot[0][0]);
		
		//Ping
		if($text_arr[0]=='!hi'){
			$message = "Halo ".$dpname."!";
		}

		//Poporing bot exchange
		if($text_arr[0]=='!price'){
			if($text_arr[1]){
				$b=count($text_arr);
				if($b>2){
					for($a=2;$a<$b;$a++){
						$text_arr[1].="_".$text_arr[$a];
					}
				}
				$srep=["'","_"," ","."];
				$target1=str_replace("*","_star",$text_arr[1]);
				$target2=str_replace($srep,"_",$target1);
				$target3=str_replace("+","r",$target2);
				$targetp=$apiexch."get_latest_price/".$target3."?mini=0";
				curl_setopt($ch,CURLOPT_URL,$targetp);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				$apiout=curl_exec($ch);
				curl_close($ch);
				$pprout=json_decode($apiout,TRUE);
				$pprtime=$pprout['data']['data']['timestamp'];
				if($pprtime){
					$pprnam1=ucwords(str_replace("_"," ",strval($pprout['data']['item_name'])));
					$pprnama=str_replace(" Star Card","★ Card",$pprnam1);
					$pprharga1=number_format(intval($pprout['data']['data']['last_known_price']))." zeny";
					$pprharga2=number_format(intval($pprout['data']['data']['price']))." zeny";
					$pprjumlah=number_format(intval($pprout['data']['data']['volume']));
					if(intval($pprout['data']['data']['snapping'])==-1){
						$pprsnap="No";
					} else {
						$pprsnap=intval($pprout['data']['data']['snapping']);
					}
					file_put_contents('./price/price.json',file_get_contents('./price/price1.json',true).$pprnama);
					if($pprout['data']['data']['volume']==0){
						file_put_contents('./price/price.json',file_get_contents('./price/price2.json',true).$pprharga1,FILE_APPEND);
					} else {
						file_put_contents('./price/price.json',file_get_contents('./price/price2.json',true).$pprharga2,FILE_APPEND);
					}
					file_put_contents('./price/price.json',file_get_contents('./price/price3.json',true).$pprjumlah,FILE_APPEND);
					file_put_contents('./price/price.json',file_get_contents('./price/price4.json',true).$pprsnap,FILE_APPEND);
					file_put_contents('./price/price.json',file_get_contents('./price/price5.json',true),FILE_APPEND);
					$message=json_decode(file_get_contents('./price/price.json',true),true);
					$templ="flex";
				} else {
					$text_ara=str_replace("_"," ",$text_arr[1]);
					$message = "Not found!\nLooks like ".$text_ara." doesn't exist!";
				}
			} else {
				$message = "Function:\nShow current exchange price of an item.\n\nUsage:\n'!price <ItemName>'";
			}
		}

		//Romexchange history
		if($text_arr[0]=='!history'){
			if($text_arr[1]){
				$b=count($text_arr);
				if($b>2){
					for($a=2;$a<$b;$a++){
						$text_arr[1].="%20".$text_arr[$a];
					}
				}
				$sinp=str_replace(" ","%20",$text_arr[1]);
				$targetp=$apihist.$sinp.'&exact=false&slim=false&sort_server=sea';
				curl_setopt($ch,CURLOPT_URL,$targetp);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$apiout=curl_exec($ch);
				curl_close($ch);
				$romeout=json_decode($apiout,TRUE);
				$romename = "Name: ".strval($romeout[0]['name']);
				if($romename){
					$message = $romename;
					$b=1;
					for($a=0;$a<=6;$a++){
						$message .= "\n".$b." - ".substr(strval($romeout[0]['sea']['week']['data'][$a]['time']),0,10)." - ".number_format(intval($romeout[0]['sea']['week']['data'][$a]['price']))." zeny";
						++$b;
					}
					$message .= "\nPowered by romexchange.com";
				} else {
					$message = "Not found!\nPowered by romexchange.com";
				}
			} else {
				$message = "Function:\nShow exchange price history of an item, up to 7 days\n\nUsage:\n'!history <ItemName>'";
			}
		}

		//Item Search
		if($text_arr[0]=='!search'){
			if($text_arr[1]){
				$b=count($text_arr);
				if($b>2){
					for($a=2;$a<$b;$a++){
						$text_arr[1].=" ".$text_arr[$a];
					}
				}
				$sinp=str_replace(" ","%20",$text_arr[1]);
				$targetp=$apisrch.$sinp;
				curl_setopt($ch,CURLOPT_URL,$targetp);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$apiout=curl_exec($ch);
				curl_close($ch);
				$romeout=json_decode($apiout,TRUE);
				$romename = strval($romeout[0]['name']);
				file_put_contents('./search/search.json',file_get_contents('./search/search1.json',true).$text_arr[1]);
				if($romename){
					file_put_contents('./search/search.json',file_get_contents('./search/search2.json',true).$romename,FILE_APPEND);
					file_put_contents('./search/search.json',file_get_contents('./search/search3a.json',true).strtolower($romename),FILE_APPEND);
					file_put_contents('./search/search.json',file_get_contents('./search/search4.json',true).strtolower($romename),FILE_APPEND);
					file_put_contents('./search/search.json',file_get_contents('./search/search5.json',true),FILE_APPEND);
				} else {
					file_put_contents('./search/search.json',file_get_contents('./search/search2.json',true)."Not Found!",FILE_APPEND);
					file_put_contents('./search/search.json',file_get_contents('./search/search3b.json',true),FILE_APPEND);
				}
				$message=json_decode(file_get_contents('./search/search.json',true),true);
				$templ="flex";
			} else {
				$message = "Function:\nShow full name of the item searched\n\nUsage:\n'!search <ItemName>'";
			}
		}

		//List VR
		if($text_arr[0]=='!vr'){
			$url = strval(file_get_contents("https://www.hdgames.net/guild.php"));
    		$url = preg_replace('/[ ]{2,}|[\t]/', '', trim($url));
    		$url = preg_replace('#\s+#',' ',trim($url));
    		$url = str_replace("> <","><",$url);
    		$url = str_replace('"../rom/','"https://www.hdgames.net/rom/',$url);
    		$loc1 = stripos($url,'img src="https:');
    		$loc2 = stripos($url,'" width="');
    		$loc3 = strlen($url)-(strlen($url)-$loc2)-$loc1;
			$final=strval(substr($url,$loc1+9,$loc3-9));
			$message=$final;
			$thumb="https://vestinel.fun/images/vr-thumb.png";
			$templ="img";
		}

		//List ET
		if($text_arr[0]=='!et'){
			if($text_arr[1]=="mini"){
				$message="https://vestinel.fun/images/etmini.png";
				$thumb="https://vestinel.fun/images/etmini-thumb.png";
			} else {
				$message="https://vestinel.fun/images/etmvp.png";
				$thumb="https://vestinel.fun/images/etmvp-thumb.png";
			}
			$templ="img";
		}

		//Add database anggota #AdminNeeded
		if($adm==1){
			if($text_arr[0]=='!add'){
				if($text_arr[1]){
					if($text_arr[2]){
						if($text_arr[3]){		
							$querys=mysqli_query($conn,"select count(*) from job where job='".$text_arr[2]."'");
							$tot=mysqli_fetch_all($querys);
							$rown=intval($tot[0][0]);	
							if($rown!=0){
								if(mysqli_query($conn,"insert into anggota values('".$text_arr[1]."','".$text_arr[3]."','".ucwords($text_arr[2])."',0,'N')")){
									$message = strval("Nickname: '".$text_arr[1]."', Job: '".ucwords($text_arr[2])."', Line: '".$text_arr[3]."' telah ditambahkan ke database");
								} else {
									$message = "Error: ".mysqli_error($conn);
								}
							} else {
								$message = "Job '".$text_arr[2]."' is not valid!\Use '!list job' to show the list of all Job available.";
							}
						} else {
							$message = "Function:\nAdd new member to the database\n\nUsage:\n'!add <Nickname> <Job> <LINE>'";
						}
					} else {
						$message = "Function:\nAdd new member to the database\n\nUsage:\n'!add <Nickname> <Job> <LINE>'";
					}
				} else {
					$message = "Function:\nAdd new member to the database\n\nUsage:\n'!add <Nickname> <Job> <LINE>'";
				}
			}

			//Select from database anggota
			if($text_arr[0]=='!list'){
				if($text_arr[1]){
					$querys=mysqli_query($conn,"select count(*) from job where job='".$text_arr[1]."'");
					$tot=mysqli_fetch_all($querys);
					$rown=intval($tot[0][0]);
					if($rown!=0){
						$querys=mysqli_query($conn,"select count(*) from anggota where job='".$text_arr[1]."'");
						$tot=mysqli_fetch_all($querys);
						$rown=intval($tot[0][0]);				
						$query=mysqli_query($conn,"select ign,line from anggota where job='".$text_arr[1]."' order by ign");
						$row=mysqli_fetch_all($query);
						file_put_contents('./job/job.json',file_get_contents('./job/job1.json',true));
						if($text_arr[2]){
							$temprown = intval($rown-(($text_arr[2]-1)*10));
							if($temprown>10){
								file_put_contents('./job/job.json',ucwords($text_arr[1])." ".intval(($text_arr[2]*10)-9).'-'.intval($text_arr[2]*10).' dari '.$rown,FILE_APPEND);
								$trown=intval($text_arr[2]*10);
							} else {
								if($rown==0){
									file_put_contents('./job/job.json',ucwords($text_arr[1])." 0 dari 0",FILE_APPEND);
								} else {
									file_put_contents('./job/job.json',ucwords($text_arr[1])." ".intval(($text_arr[2]*10)-9).'-'.$rown.' dari '.$rown,FILE_APPEND);
								}
								$trown=$rown;
							}	
							file_put_contents('./job/job.json',file_get_contents('./job/job2.json',true),FILE_APPEND);
							for($a=intval(($text_arr[2]-1)*10);$a<$trown;$a++){
								file_put_contents('./job/job.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
							}
							if($temprown>10){
								if($text_arr[2]<2){
									file_put_contents('./job/job.json',file_get_contents('./job/job3a.json',true).'\"'.$text_arr[1].'\" 2',FILE_APPEND);
									file_put_contents('./job/job.json',file_get_contents('./job/job4a.json',true),FILE_APPEND);
								} else {
									file_put_contents('./job/job.json',file_get_contents('./job/job3b.json',true).'\"'.$text_arr[1].'\" '.intval($text_arr[2]-1),FILE_APPEND);
									file_put_contents('./job/job.json',file_get_contents('./job/job4b.json',true).'\"'.$text_arr[1].'\" '.intval($text_arr[2]+1),FILE_APPEND);
									file_put_contents('./job/job.json',file_get_contents('./job/job5b.json',true),FILE_APPEND);
								}
							} else {
								file_put_contents('./job/job.json',file_get_contents('./job/job3b.json',true).'\"'.$text_arr[1].'\" '.intval($text_arr[2]-1),FILE_APPEND);
								file_put_contents('./job/job.json',file_get_contents('./job/job4c.json',true),FILE_APPEND);
							}
							$message=json_decode(file_get_contents('./job/job.json',true),true);
							$templ="flex";
						} else {
							if($rown>10){
								file_put_contents('./job/job.json',ucwords($text_arr[1]).' 1-10 from '.$rown,FILE_APPEND);
								$trown=10;
							} else {
								if($rown==0){
									file_put_contents('./job/job.json',ucwords($text_arr[1])." 0 from 0",FILE_APPEND);
								} else {
									file_put_contents('./job/job.json',ucwords($text_arr[1]).' 1-'.$rown.' from '.$rown,FILE_APPEND);
								}
								$trown=$rown;
							}
							file_put_contents('./job/job.json',file_get_contents('./job/job2.json',true),FILE_APPEND);
							for($a=0;$a<$trown;$a++){
								file_put_contents('./job/job.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
							}
							if($rown>10){
								file_put_contents('./job/job.json',file_get_contents('./job/job3a.json',true).'\"'.$text_arr[1].'\" 2',FILE_APPEND);
								file_put_contents('./job/job.json',file_get_contents('./job/job4a.json',true),FILE_APPEND);
							} else {
								file_put_contents('./job/job.json',file_get_contents('./job/job3.json',true),FILE_APPEND);
							}
							$message=json_decode(file_get_contents('./job/job.json',true),true);
							$templ="flex";
						}
					} else {
						switch($text_arr[1]){
							case "all":
								$querys=mysqli_query($conn,"select count(*) from anggota");
								$tot=mysqli_fetch_all($querys);
								$rown=intval($tot[0][0]);
								$query=mysqli_query($conn,"select ign,job,line from anggota order by ign,job");
								$row=mysqli_fetch_all($query);
								file_put_contents('./all/all.json',file_get_contents('./all/all1.json',true));
								if($text_arr[2]){
									$temprown = intval($rown-(($text_arr[2]-1)*10));
									if($temprown>10){
										file_put_contents('./all/all.json',intval(($text_arr[2]*10)-9).'-'.intval($text_arr[2]*10).' from '.$rown,FILE_APPEND);
										$trown=intval($text_arr[2]*10);
									} else {
										file_put_contents('./all/all.json',intval(($text_arr[2]*10)-9).'-'.$rown.' from '.$rown,FILE_APPEND);
										$trown=$rown;
									}
									file_put_contents('./all/all.json',file_get_contents('./all/all2.json',true),FILE_APPEND);
									for($a=intval(($text_arr[2]-1)*10);$a<$trown;$a++){
										file_put_contents('./all/all.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][2].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
									}
									if($temprown>10){
										if($text_arr[2]<2){
											file_put_contents('./all/all.json',file_get_contents('./all/all3a.json',true),FILE_APPEND);
										} else {
											file_put_contents('./all/all.json',file_get_contents('./all/all3b.json',true).intval($text_arr[2]-1),FILE_APPEND);
											file_put_contents('./all/all.json',file_get_contents('./all/all4b.json',true).intval($text_arr[2]+1),FILE_APPEND);
											file_put_contents('./all/all.json',file_get_contents('./all/all5b.json',true),FILE_APPEND);
										}
									} else {
										file_put_contents('./all/all.json',file_get_contents('./all/all3b.json',true).intval($text_arr[2]-1),FILE_APPEND);
										file_put_contents('./all/all.json',file_get_contents('./all/all4c.json',true),FILE_APPEND);
									}
									$message=json_decode(file_get_contents('./all/all.json',true),true);
									$templ="flex";
								} else {
									if($rown>10){
										file_put_contents('./all/all.json','1-10 from '.$rown,FILE_APPEND);
										$trown=10;
									} else {
										file_put_contents('./all/all.json','1-'.$rown.' from '.$rown,FILE_APPEND);
										$trown=$rown;
									}
									file_put_contents('./all/all.json',file_get_contents('./all/all2.json',true),FILE_APPEND);
									for($a=0;$a<$trown;$a++){
										file_put_contents('./all/all.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][2].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
									}
									if($rown>10){
										file_put_contents('./all/all.json',file_get_contents('./all/all3a.json',true),FILE_APPEND);
									} else {
										file_put_contents('./all/all.json',file_get_contents('./all/all3.json',true),FILE_APPEND);
									}
									$message=json_decode(file_get_contents('./all/all.json',true),true);
									$templ="flex";
								}
								break;
							case "adm":
								$querys=mysqli_query($conn,"select count(*) from admin");
								$tot=mysqli_fetch_all($querys);
								$rown=intval($tot[0][0]);
								$query=mysqli_query($conn,"select line from admin");
								$row=mysqli_fetch_all($query);
								$message = strval("Number of VestiBot's Admin: ".$rown);
								for($a=0;$a<$rown;$a++){
									$message .= strval("\n".$row[$a][0]);
								}							
								break;
							case "job":
								$querys=mysqli_query($conn,"select count(*) from job");
								$tot=mysqli_fetch_all($querys);
								$rown=intval($tot[0][0]);
								$query=mysqli_query($conn,"select job from job");
								$row=mysqli_fetch_all($query);
								$message = strval('Number of Job: '.$rown);
								for($a=0;$a<$rown;$a++){
									$message .= strval("\n".$row[$a][0]);
								}							
								break;		
							default:
								$message = "Job '".$text_arr[1]."' is not valid!\Use '!list job' to show the list of all Job available.";
								break;
						}
					}
				} else {
					$message = "Function:\nShow the list of members' Nickname and LINE of a certain Job\n\nUsage:\n'!list <Job>'";
				}
			}

			//Register website
			if($ttype=="user"){
				if($text_arr[0] == '!web'){
					if($text_arr[1]=="reg"){
						$querys=mysqli_query($conn,"select count(*),user from webid where uid='".$userID."'");
						$tot=mysqli_fetch_all($querys);
						$registered=intval($tot[0][0]);
						$used=$tot[0][1];
						if($registered==1){
							$message="Your line account is already registered as:\n".$used;
						} else {
							if($text_arr[2]){
								if($text_arr[3]){
									$quer=mysqli_query($conn,"select count(*) from webid where user='".$text_arr[2]."'");
									$totz=mysqli_fetch_all($quer);
									$uexist=intval($totz[0][0]);
									if($uexist==1){
										$message="This username is already taken!";
									} else {
										mysqli_query($conn,'insert into webid values("'.$text_arr[2].'","'.md5($text_arr[3]).'","'.$userID.'")');
										$message=$text_arr[2]." has been registered!";
									}
								} else {
									$message="Please provide password!";
								}
							} else {
								$message="Your line account isn't registered yet!\n\nPlease register with:\n'!web reg <user> <pass>'";
							}
						}
					} else {
						$message="Function:\nVestinel's website user panel\nhttps://vestinel.fun/members.php\n\nUsage:\n'!web reg'\nTo show registered status\n\n'!web reg <user> <password>'\nTo register new user";
					}
				}
			}

			//Recruitment Menu
			if($text_arr[0]=='!rec'){
				$querys=mysqli_query($conn,"select count(*) from recruitment");
				$tot=mysqli_fetch_all($querys);
				$que=intval($tot[0][0]);
				if($que!=0){
					if($text_arr[1]){
						if($text_arr[1]<$que){
							$lim=$text_arr[1];
						} else {
							$lim=$que-1;
						}
					} else {
						$lim=0;
					}
					$qact=$lim+1;
					$query=mysqli_query($conn,"select * from recruitment limit ".$lim.",1");
					$row=mysqli_fetch_all($query);
					if(empty($row[0][8])){
						$msg="-";
					} else {
						$msg=preg_replace( "/\r|\n/", " ", strval($row[0][8]));
					}
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec1.json',true).$qact." of ".$que);
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec2.json',true).$row[0][0],FILE_APPEND); //nama
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec3.json',true).$row[0][2],FILE_APPEND); //gender
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec4.json',true).$row[0][3],FILE_APPEND); //line
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec5.json',true).$row[0][4],FILE_APPEND); //discord
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec6.json',true).$row[0][1],FILE_APPEND); //nick
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec7.json',true).$row[0][5],FILE_APPEND); //job
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec8.json',true).$row[0][6],FILE_APPEND); //contri
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec9.json',true).$row[0][7],FILE_APPEND); //gm
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec10.json',true).$msg,FILE_APPEND); //msg
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec11.json',true).$row[0][1],FILE_APPEND);
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec12.json',true).$row[0][1],FILE_APPEND);
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec13.json',true).$qact,FILE_APPEND);
					file_put_contents('./rec/rec.json',file_get_contents('./rec/rec14.json',true),FILE_APPEND);
					$message=json_decode(file_get_contents('./rec/rec.json',true),true);
					$templ="flex";
				} else {
					$message = "No one in queue!";
				}
			}

			//To be used with recruitment menu
			if($text_arr[0]=="!acc"){
				if($text_arr[1]){
					$b=count($text_arr);
					$anick=$text_arr[1];
					if($b>2){
						for($a=2;$a<$b;$a++){
							$anick.=" ".$text_arr[$a];
						}
					}
					$querys=mysqli_query($conn,"select count(*) from recruitment where nick='".$anick."'");
					$tot=mysqli_fetch_all($querys);
					$que=intval($tot[0][0]);
					if($que!=0){
						$query=mysqli_query($conn,"select * from recruitment where nick='".$anick."'");
						$row=mysqli_fetch_all($query);
						mysqli_query($conn,"insert into anggota values('".$row[0][0]."','".$row[0][2]."','".$row[0][5]."',1)");
						mysqli_query($conn,"delete from recruitment where nick='".$anick."'");
						$message=$anick." has been accepted!";
					} else {
						$message=$anick." not found!";
					}
				}
			}

			if($text_arr[0]=="!rej"){
				if($text_arr[1]){
					$b=count($text_arr);
					$anick=$text_arr[1];
					if($b>2){
						for($a=2;$a<$b;$a++){
							$anick.=" ".$text_arr[$a];
						}
					}
					$querys=mysqli_query($conn,"select count(*) from recruitment where nick='".$anick."'");
					$tot=mysqli_fetch_all($querys);
					$que=intval($tot[0][0]);
					if($que!=0){
						mysqli_query($conn,"delete from recruitment where nick='".$anick."'");
						$message=$anick." has been rejected!";
					} else {
						$message=$anick." not found!";
					}
				}
			}

			//Delete Member #AdminNeeded
			if($text_arr[0]=='!del'){
				if($text_arr[1]){
					$querys=mysqli_query($conn,"select count(*) from anggota where ign='".$text_arr[1]."'");
					$tot=mysqli_fetch_all($querys);
					$rown=intval($tot[0][0]);
					if($rown!=0){
						if(mysqli_query($conn,"delete from anggota where ign='".$text_arr[1]."'")){
							$message = strval("Nickname: ".$text_arr[1]." has been disposed!");
						} else {
							$message = "Error: ".mysqli_error($conn);
						}	
					} else {
						$message = "There's no one with Nickname: ".$text_arr[1];
					}
				} else {
					$message = "Function:\nDelete a member from database!\n\nUsage:\n'!del <Nickname>'";
				}
			}
			
			//Update line #AdminNeeded
			if($text_arr[0]=='!cline'){
				if($text_arr[1]){
					if($text_arr[2]){
						$querys=mysqli_query($conn,"select count(*) from anggota where line='".$text_arr[1]."'");
						$tot=mysqli_fetch_all($querys);
						$rown=intval($tot[0][0]);
						if($rown!=0){
							if(mysqli_query($conn,"update anggota set line='".$text_arr[2]."' where line='".$text_arr[1]."'")){
								$message = strval("Anda telah memperbahuri Line: '".$text_arr[1]."' menjadi '".$text_arr[2]."'");
							} else {
								$message = "Error: ".mysqli_error($conn);
							}
						} else {
							$message = "There's no one with LINE: ".$text_arr[1];
						}
					} else {
						$message = "Function:\nUpdate LINE of a member\n\nUsage:\n'!cline <OldLINE> <NewLINE>'";
					}
				} else {
					$message = "Function:\nUpdate LINE of a member\n\nUsage:\n'!cline <OldLINE> <NewLINE>'";
				}	
			}
	
			//Ganti nickname #AdminNeeded
			if($text_arr[0]=='!cnick'){
				if($text_arr[1]){
					if($text_arr[2]){
						$querys=mysqli_query($conn,"select count(*) from anggota where ign='".$text_arr[1]."'");
						$tot=mysqli_fetch_all($querys);
						$rown=intval($tot[0][0]);
						if($rown!=0){
							if(mysqli_query($conn,"update anggota set ign='".$text_arr[2]."' where ign='".$text_arr[1]."'")){
								$message = strval("Nickname: '".$text_arr[1]."' has been changed to: '".$text_arr[2]."'");
							} else {
								$message = "Error: ".mysqli_error($conn);
							}	
						} else {
							$message = "There's no one with Nickname: ".$text_arr[1];
						}
					} else {
						$message = "Function:\nUpdate member's Nickname\n\nUsage:\n'!cnick <OldNickname> <NewNickname>'";
					}
				} else {
					$message = "Function:\nUpdate member's Nickname\n\nUsage:\n'!cnick <OldNickname> <NewNickname>'";
				}
			}		
	
			//Ganti job #AdminNeeded
			if($text_arr[0]=='!cjob'){
				if($text_arr[1]){
					if($text_arr[2]){
						if($text_arr[3]){
							$querys=mysqli_query($conn,"select count(*) from anggota where ign='".$text_arr[1]."' and job='".$text_arr[2]."'");
							$tot=mysqli_fetch_all($querys);
							$rown=intval($tot[0][0]);
							if($rown!=0){
								if(mysqli_query($conn,"update anggota set job='".$text_arr[3]."' where ign='".$text_arr[1]."' and job='".$text_arr[2]."'")){
									$message = strval("Nickname: '".$text_arr[1]."' \nTheir Job has been changed to: '".$text_arr[3]."'");
								} else {
									$message = "Error: ".mysqli_error($conn);
								}	
							}
						} else {
							$message = "Function:\nChange Job of a member\n\nUsage:\n'!cjob <Nickname> <OldJob> <NewJob>'";
						}
					} else {
						$message = "Function:\nChange Job of a member\n\nUsage:\n'!cjob <Nickname> <OldJob> <NewJob>'";
					}
				} else {
					$message = "Function:\nChange Job of a member\n\nUsage:\n'!cjob <Nickname> <OldJob> <NewJob>'";
				}
			}
	
			//Reset Database #AdminNeeded
			if($text_arr[0]=='!dbreset'){
				if($text_arr[1]){
					switch($text_arr[1]){
						case "job":
							$message = "Can't reset this database!";
							break;
						case "log":
							$message = "Can't reset this database!";
							break;
						case "help":
							$message = "Can't reset this database!";
							break;
						case "admin":
							$message = "Can't reset this database!";
							break;
						case "userid":
							$message = "Can't reset this database!";
							break;
						default:
							if(mysqli_query($conn,"delete from ".$text_arr[1])){
								$message = strval("Database ".$text_arr[1]." has been reset!");
							} else {
								$message = "Error: ".mysqli_error($conn);
							}
							break;
					}
				} else {
					$message = "Function:\nReset a database\n\nUsage:\n'!dbreset <dbname>'";
				}
			}
	
			//Menambahkan Admin #AdminNeeded
			if($text_arr[0]=='!adm'){
				if($text_arr[1]){
					if($text_arr[2]){
						if(strlen($text_arr[1])==33){
							$query=mysqli_query($conn,"insert into admin values('".$text_arr[1]."','".$text_arr[2]."')");
							$message = "UserID: ".$text_arr[1]."\nLINE: ".$text_arr[2]."\nhas been added as VestiBot Admin!";
						} else {
							$message = "UserID is not valid!";
						}
					} else {
						$message = "Function:\nAdd administrator authority to LINE user with a certain UserID\n\nUsage:\n'!adm <userID> <LINE>'";
					}
				} else {
					$message = "Function:\nAdd administrator authority to LINE user with a certain UserID\n\nUsage:\n'!adm <userID> <LINE>'";
				}
			}
		}
		
		//Help
		if($text_arr[0]=='!help'){
			if($text_arr[1]){
				if($ttype=="user"){
					$query=mysqli_query($conn,"select description from help where command='".$text_arr[1]."'");
					$row=mysqli_fetch_all($query);
					$message = strval($row[0][0]);
				} else {
					$message = "For more information about this, please PM me!";
				}
			} else {
				if($adm==1){
					$querys=mysqli_query($conn,"select count(*) from help");
					$tot=mysqli_fetch_all($querys);
					$rown=intval($tot[0][0]);
					$query=mysqli_query($conn,"select command,private from help order by command");
					$row=mysqli_fetch_all($query);
					$message = "Here's the list of usable command:";
					for($a=0;$a<$rown;$a++){
						$message .= strval("\n - ".$row[$a][0].$row[$a][1]);
					}
					$message .= "\n*These command can only be used via 'Private Message'.\n**These command can only be used by Admin.\n\nPlease PM me with:\n'!help <Command>'\nFor more information about that command!";
				} else {
					$querys=mysqli_query($conn,"select count(*) from help where private!='**'");
					$tot=mysqli_fetch_all($querys);
					$rown=intval($tot[0][0]);
					$query=mysqli_query($conn,"select command,private from help where private!='**' order by command");
					$row=mysqli_fetch_all($query);
					$message = "Here's the list of usable command:";
					for($a=0;$a<$rown;$a++){
						$message .= strval("\n - ".$row[$a][0].$row[$a][1]);
					}
					$message .= "\n*These command can only be used via 'Private Message'.\n**These command can only be used by Admin.\n\nPlease PM me with:\n'!help <Command>'\nFor more information about that command!";
				}
			}
		}
		
		//Only usable in private chat
		if($ttype=="user"){
			//Intip UserID
			if($text == '!id'){
				if($userID){
					$message = "LINE Name:\n".$dpname."\nUserID:\n".$userID;
					if($adm==1){
						$message .= "\nYou are an Admin!";
					}
				} else {
					$message = "Your UserID can not be read, please use official LINE Client.\nLINE Lite is not supported!";
				}
			}

			//Select from database anggota
			if($text_arr[0]=='!list'){
				if($text_arr[1]){
					$querys=mysqli_query($conn,"select count(*) from job where job='".$text_arr[1]."'");
					$tot=mysqli_fetch_all($querys);
					$rown=intval($tot[0][0]);
					if($rown!=0){
						$querys=mysqli_query($conn,"select count(*) from anggota where job='".$text_arr[1]."'");
						$tot=mysqli_fetch_all($querys);
						$rown=intval($tot[0][0]);				
						$query=mysqli_query($conn,"select ign,line from anggota where job='".$text_arr[1]."' order by ign");
						$row=mysqli_fetch_all($query);
						file_put_contents('./job/job.json',file_get_contents('./job/job1.json',true));
						if($text_arr[2]){
							$temprown = intval($rown-(($text_arr[2]-1)*10));
							if($temprown>10){
								file_put_contents('./job/job.json',ucwords($text_arr[1])." ".intval(($text_arr[2]*10)-9).'-'.intval($text_arr[2]*10).' dari '.$rown,FILE_APPEND);
								$trown=intval($text_arr[2]*10);
							} else {
								if($rown==0){
									file_put_contents('./job/job.json',ucwords($text_arr[1])." 0 of 0",FILE_APPEND);
								} else {
									file_put_contents('./job/job.json',ucwords($text_arr[1])." ".intval(($text_arr[2]*10)-9).'-'.$rown.' of '.$rown,FILE_APPEND);
								}
								$trown=$rown;
							}	
							file_put_contents('./job/job.json',file_get_contents('./job/job2.json',true),FILE_APPEND);
							for($a=intval(($text_arr[2]-1)*10);$a<$trown;$a++){
								file_put_contents('./job/job.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
							}
							if($temprown>10){
								if($text_arr[2]<2){
									file_put_contents('./job/job.json',file_get_contents('./job/job3a.json',true).'\"'.$text_arr[1].'\" 2',FILE_APPEND);
									file_put_contents('./job/job.json',file_get_contents('./job/job4a.json',true),FILE_APPEND);
								} else {
									file_put_contents('./job/job.json',file_get_contents('./job/job3b.json',true).'\"'.$text_arr[1].'\" '.intval($text_arr[2]-1),FILE_APPEND);
									file_put_contents('./job/job.json',file_get_contents('./job/job4b.json',true).'\"'.$text_arr[1].'\" '.intval($text_arr[2]+1),FILE_APPEND);
									file_put_contents('./job/job.json',file_get_contents('./job/job5b.json',true),FILE_APPEND);
								}
							} else {
								file_put_contents('./job/job.json',file_get_contents('./job/job3b.json',true).'\"'.$text_arr[1].'\" '.intval($text_arr[2]-1),FILE_APPEND);
								file_put_contents('./job/job.json',file_get_contents('./job/job4c.json',true),FILE_APPEND);
							}
							$message=json_decode(file_get_contents('./job/job.json',true),true);
							$templ="flex";
						} else {
							if($rown>10){
								file_put_contents('./job/job.json',ucwords($text_arr[1]).' 1-10 of '.$rown,FILE_APPEND);
								$trown=10;
							} else {
								if($rown==0){
									file_put_contents('./job/job.json',ucwords($text_arr[1])." 0 of 0",FILE_APPEND);
								} else {
									file_put_contents('./job/job.json',ucwords($text_arr[1]).' 1-'.$rown.' of '.$rown,FILE_APPEND);
								}
								$trown=$rown;
							}
							file_put_contents('./job/job.json',file_get_contents('./job/job2.json',true),FILE_APPEND);
							for($a=0;$a<$trown;$a++){
								file_put_contents('./job/job.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
							}
							if($rown>10){
								file_put_contents('./job/job.json',file_get_contents('./job/job3a.json',true).'\"'.$text_arr[1].'\" 2',FILE_APPEND);
								file_put_contents('./job/job.json',file_get_contents('./job/job4a.json',true),FILE_APPEND);
							} else {
								file_put_contents('./job/job.json',file_get_contents('./job/job3.json',true),FILE_APPEND);
							}
							$message=json_decode(file_get_contents('./job/job.json',true),true);
							$templ="flex";
						}
					} else {
						switch($text_arr[1]){
							case "all":
								$querys=mysqli_query($conn,"select count(*) from anggota");
								$tot=mysqli_fetch_all($querys);
								$rown=intval($tot[0][0]);
								$query=mysqli_query($conn,"select ign,job,line from anggota order by ign,job");
								$row=mysqli_fetch_all($query);
								file_put_contents('./all/all.json',file_get_contents('./all/all1.json',true));
								if($text_arr[2]){
									$temprown = intval($rown-(($text_arr[2]-1)*10));
									if($temprown>10){
										file_put_contents('./all/all.json',intval(($text_arr[2]*10)-9).'-'.intval($text_arr[2]*10).' of '.$rown,FILE_APPEND);
										$trown=intval($text_arr[2]*10);
									} else {
										file_put_contents('./all/all.json',intval(($text_arr[2]*10)-9).'-'.$rown.' of '.$rown,FILE_APPEND);
										$trown=$rown;
									}
									file_put_contents('./all/all.json',file_get_contents('./all/all2.json',true),FILE_APPEND);
									for($a=intval(($text_arr[2]-1)*10);$a<$trown;$a++){
										file_put_contents('./all/all.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][2].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
									}
									if($temprown>10){
										if($text_arr[2]<2){
											file_put_contents('./all/all.json',file_get_contents('./all/all3a.json',true),FILE_APPEND);
										} else {
											file_put_contents('./all/all.json',file_get_contents('./all/all3b.json',true).intval($text_arr[2]-1),FILE_APPEND);
											file_put_contents('./all/all.json',file_get_contents('./all/all4b.json',true).intval($text_arr[2]+1),FILE_APPEND);
											file_put_contents('./all/all.json',file_get_contents('./all/all5b.json',true),FILE_APPEND);
										}
									} else {
										file_put_contents('./all/all.json',file_get_contents('./all/all3b.json',true).intval($text_arr[2]-1),FILE_APPEND);
										file_put_contents('./all/all.json',file_get_contents('./all/all4c.json',true),FILE_APPEND);
									}
									$message=json_decode(file_get_contents('./all/all.json',true),true);
									$templ="flex";
								} else {
									if($rown>10){
										file_put_contents('./all/all.json','1-10 of '.$rown,FILE_APPEND);
										$trown=10;
									} else {
										file_put_contents('./all/all.json','1-'.$rown.' of '.$rown,FILE_APPEND);
										$trown=$rown;
									}
									file_put_contents('./all/all.json',file_get_contents('./all/all2.json',true),FILE_APPEND);
									for($a=0;$a<$trown;$a++){
										file_put_contents('./all/all.json',',{"type":"box","layout":"horizontal","spacing":"sm","contents":[{"type":"text","text":"'.$row[$a][0].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][1].'","size":"xxs","flex":1,"align":"center"},{"type":"text","text":"'.$row[$a][2].'","size":"xxs","flex":1,"align":"center"}]}',FILE_APPEND);
									}
									if($rown>10){
										file_put_contents('./all/all.json',file_get_contents('./all/all3a.json',true),FILE_APPEND);
									} else {
										file_put_contents('./all/all.json',file_get_contents('./all/all3.json',true),FILE_APPEND);
									}
									$message=json_decode(file_get_contents('./all/all.json',true),true);
									$templ="flex";
								}
								break;
							case "adm":
								$querys=mysqli_query($conn,"select count(*) from admin");
								$tot=mysqli_fetch_all($querys);
								$rown=intval($tot[0][0]);
								$query=mysqli_query($conn,"select line from admin");
								$row=mysqli_fetch_all($query);
								$message = strval('Number of VestiBot Admin: '.$rown);
								for($a=0;$a<$rown;$a++){
									$message .= strval("\n".$row[$a][0]);
								}							
								break;
							case "job":
								$querys=mysqli_query($conn,"select count(*) from job");
								$tot=mysqli_fetch_all($querys);
								$rown=intval($tot[0][0]);
								$query=mysqli_query($conn,"select job from job");
								$row=mysqli_fetch_all($query);
								$message = strval('Number of available Job: '.$rown);
								for($a=0;$a<$rown;$a++){
									$message .= strval("\n".$row[$a][0]);
								}							
								break;		
							default:
								$message = "Job '".$text_arr[1]."' is not valid!\nUse '!list job' to see the list of available Job.";
								break;
						}
					}
				} else {
					$message = "Function:\nShow the list of members with a certain Job\n\nUsage:\n'!list <Job>'";
				}
			}
		}

	//Defining Template
	switch($templ){
		case "img":
			$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($message,$thumb);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			break;
		case "flex":
			$result = $bot->replyMessage($event['replyToken'],new RawMessageBuilder($message));
			break;
		default:
			$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			break;
	}

	//logging if there's user interaction
	if($message){
		mysqli_query($conn,"insert into log values('".date("Y-m-d H:i:s",strtotime('-2 hours'))."','".$dpname."','".$userMessage."','".$ttype."')");
	}
	
	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
	}
});
$app->run();

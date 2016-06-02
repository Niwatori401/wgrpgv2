<?php 

include_once "Database.php";
include_once 'constants.php';
include_once "common.php";
include_once "users.php";
include_once "RPGUser.php";
include_once "RPGCharacter.php";

@session_start();

?>

<!DOCTYPE html>
<html>
	<head>
		<title>WGRPG</title>
		<link rel="stylesheet" type="text/css" href="main.css" media="screen" />
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="JS/jquery.numeric.js" type="text/javascript"></script>
		<?php header('Content-Type: text/html; charset=ISO-8859-15');?>
	</head>
	<body>
		<div class='topWindow'>
			<div class='navigation'>
				<a href='index.php'><u>Home</u></a>
				<?php if(!isset($_SESSION['objUser'])){?>| <a href='register.php'><u>Register</u></a><?php } ?>
				<?php if(isset($_SESSION['objUser'])){?>| <a href='DisplayCharacterSelection.php'><u>Characters</u></a>
				<?php } ?>
			</div>
			<div class='logo'>
				<img src='logo.png'/>
			</div>
			<div class='login'>
				<?php if(!isset($_SESSION['objUser'])){ ?>
				<form action='login.php' method='post'>
					<ul>
						<li class='noBullet'>Username: &nbsp;&nbsp;&nbsp;<input class='useridInput' type='text' name='strUserID' size='45'/></li>
						<li class='noBullet'>Password:&nbsp;&nbsp;&nbsp;&nbsp; <input type='password' class='passwordInput' name='strPassword' size='45'/></li>
						<li class='fR noBullet'><input class='blogbutton' type='submit' value='Login'/></li>
					</ul>
				</form>
				<?php }else{ ?>
					Welcome, <?=$_SESSION['objUser']->getStringUserID()?>.
					<br/><br/><a href='settings.php'><u>Settings</u></a>
					<br/><a href='logout.php'><u>Logout</u></a>
				<?php } ?>
			</div>
		</div>
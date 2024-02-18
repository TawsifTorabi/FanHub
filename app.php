<?php
    //Check if app requested for webpage view
    $web = isset($_GET['view']) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <script src='js/aurna-lightbox_1.0.4.js' type='text/javascript'></script>
	<link href="css/aurna-lightbox_1.0.4.css" rel="stylesheet"/>
    <script src="js/main.js"></script>
    <div class="topnavOnlyfans">
        <div class="Logo">TheOnlyfans</div>

        <?php
            //Start of "Launch In Browser" button condition
            //Show The "Launch In Browser" Button only in app
            if(!$web){
        ?>
            <div onclick="launchExternal('web')" class="webbtn">Launch In Browser</div>
        <?php } 
            //Ends "Launch In Browser" Button
        ?>
    </div>


    <div class="topnav" id="myTopnav">
        <a href="#home" class="active"><i class="fa-solid fa-gauge-high"></i> Control Panel</a>
        <a href="javascript:aurnaIframe('scan_device.php')"><i class="fa-solid fa-square-plus"></i> Manage Device</a>
        <a href="javascript:aurnaIframe('websocket.php')"><i class="fa-solid fa-square-plus"></i> Web Socket</a>
        <a href="javascript:void(0);" class="icon" onclick="resNav()">
            <i class="fa fa-bars"></i>
        </a>
    </div>

    <div class="centerbox">
        <div id="TableContainer">
            <h2>Welcome</h2>
            </br>
            <div id="deviceTableContainer"><i class="fa-solid fa-spinner fa-spin"></i> Scanning For Supported Devices</div>
        </div>
    </div>
    <?php
        if(isset($_GET['view'])){
            if($_GET['view'] == 'web'){
                include_once 'webview.php';
            }
        }
    ?>
</body>
</html>
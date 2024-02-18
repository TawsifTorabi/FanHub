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
    <script>
        document.onkeydown=function(e){
                    if(e.which == 27) {
                        esc();
                        return false;
                    }
                }
                
        function esc(){
            //hideIframe();
            parent.hideIframe();
        }
    </script>
    <script src="js/main.js"></script>
    <script src="js/scan-device.js"></script>
    <div id="id01" class="modal">
        <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">×</span>
        <div class="container">
        <h1>Reset Device?</h1>
        <p>Are you sure you want to Reset the Device?</p>
        
        <div class="clearfix">
            <input type="text" id="resetPass" placeholder="Enter Device Password..."/><br>
            <br>
            <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancel</button>
            <button type="button" onclick="confirmResetDevice()" class="deletebtn">Reset</button>
        </div>
        </div>
    </div>

<script>
// Get the modal
var modal = document.getElementById('id01');

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>
    <div class="centerbox">
        
        <div id="SetupBox" style="display: none;">
            <div class="setupContainer">
                <h2>Setup - FanHub <span id="setupDeviceId"></span></h2>
                <span class="smallTextCont">Connected at <small id="setupDeviceCOM"></small></span>
                <br><br>
                <div id="devicePassword" style="display: inherit;">
                    Device Password: <br>
                    <input type="text" id="passwordInput" placeholfer="Type in Device Password..."/>
                    <button onclick="confirmDevicePassword()">Continue Setup</button>
                    <button onclick="cancelSetup()">Cancel</button>
                    <span id="notificaitonText" style="display: inline; color: red;"></span>
                </div>
                <div id="deviceWifi" style="display: none;">
                    <button onclick="cancelSetup()">Cancel</button>
                    <button onclick="rescanWifi()" id="rescanWifiBtn">Rescan Wifi</button>
                    <h5 id="scanWifiNotification"><i class="fa-solid fa-spinner fa-spin"></i> Scanning for Available Wifi Networks.</h5>
                    <br>
                    <div id="wifiList"></div> 
                </div>
            </div>
            <br>
            <br>
            <br>
            <br>
        </div>
        <div id="SuccessBox" style="display: none;">
            <div class="successContainer">
                <span style="font-size: 30px;"><i style="color: green;" class="fa-solid fa-circle-check"></i> Device Setup Successful!</span>
                <br>
                <br>
                <span id="noticationDevice"></span>
                <br>
                <br>
                You will be able to control this FanHub device on the Same WiFi or LAN. <br>
                This Device is paired as default FanHub Device.
            </div>
        </div>

        <div id="TableContainer">       
            <h2 class="h2_spec_1">Available Devices <i style="display: none;" id="loadingDevices" class="fa-solid fa-spinner fa-spin"></i></h2>
            <button onclick="reScan()" class="btn btn_spec_1" id="rescan_btn" disabled><i class="fa-solid fa-refresh"></i> Rescan</button>
            </br>
            <div id="deviceTableContainer">
                    <h5><i class="fa-solid fa-spinner fa-spin"></i> Scanning For Supported Devices</h5>
                    <br>
                    <small style="color: #d9d9d9;">Connect Your FanHub Flashed ESP32 Device into the USB</small><br>
            </div>
        </div>
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
        <br> 
    
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
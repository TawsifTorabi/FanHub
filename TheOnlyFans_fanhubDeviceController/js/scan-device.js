var lastResponse;
let enableRescanBtn = true;
let setupIsRunning = false;
let setupCOM = '';
let setupDeviceID = 0;
let setupDevicePassword = "";

//Function to Get Available Ports List from Powershell
function fetchPorts() {
    makeAjaxRequest("../ajax.php?data=getPorts", function (responseText) {
      var jsonResponse = JSON.parse(responseText);
      if (JSON.stringify(jsonResponse) !== JSON.stringify(lastResponse)) {
        document.getElementById("loadingDevices").style.display = "inherit";
        responseToChangedPorts(jsonResponse);
        lastResponse = jsonResponse;

        if(enableRescanBtn == true){
            enableRescanBtn = false;
            document.getElementById('rescan_btn').removeAttribute('disabled');
        }

      }
    });
}

function Timer(fn, t) {
    var timerObj = setInterval(fn, t);

    this.stop = function() {
        if (timerObj) {
            clearInterval(timerObj);
            timerObj = null;
        }
        return this;
    }

    // start timer using current settings (if it's not already running)
    this.start = function() {
        if (!timerObj) {
            this.stop();
            timerObj = setInterval(fn, t);
        }
        return this;
    }

    // start with new or original interval, stop current interval
    this.reset = function(newT = t) {
        t = newT;
        return this.stop().start();
    }
}

//Request for Available Ports List Every 3 seconds

var portScanTimer = new Timer(function() {
                        fetchPorts();
                    }, 2000);

//If Ports Lists gets updated 
function responseToChangedPorts(beforeResponse) {
    portScanTimer.stop();
    console.log("Response changed:", beforeResponse);
    console.log('Updating Device Table...');
    setTimeout(() => {
        makeAjaxRequest("../ajax.php?data=findDevice", function(response){
            console.log(response);
            displayDeviceTable(response);
            console.log("Updated Table Successfully");
            portScanTimer.reset(3000);
            portScanTimer.start();
            document.getElementById("loadingDevices").style.display = "none";
            
            var jsonResponse = JSON.parse(response);
            if (jsonResponse.error === 'nodevice') {
                makeAjaxRequest("../ajax.php?data=findDevice", function(response){
                    console.log(response);
                    displayDeviceTable(response);
                });
            }
        });
    }, 5000);
    if(setupIsRunning){
        setup_DisconnectedErrorHandler();
    }
}

function reScan(){
    console.log("Rescan Called.");
    document.getElementById('rescan_btn').setAttribute('disabled', true);
    document.getElementById('rescan_btn').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scanning...';

    makeAjaxRequest("../ajax.php?data=findDevice", function(response){
        document.getElementById('rescan_btn').innerHTML = '<i class="fa-solid fa-refresh"></i> Rescan';
        document.getElementById('rescan_btn').removeAttribute('disabled');
        displayDeviceTable(response);
    });
    
    if(setupIsRunning){
        setup_DisconnectedErrorHandler();
    }
}


function setup_DisconnectedErrorHandler() {
    makeAjaxRequest('ajax.php?data=findDevice', function(response) {
        var parsedResponse = JSON.parse(response);

        if (parsedResponse.error === 'none') {
            var devices = parsedResponse.devices;

            if (devices.length === 0) {
                console.log('setupErrorHandler -  found No devices.');
                cancelSetup();
            } else {
                var matchingDevice = devices.find(function(device) {
                    return device.COMPort === setupCOM && device.DeviceID === setupDeviceID;
                });

                if (!matchingDevice) {
                    console.log('setupErrorHandler - Device ID and COM Port do not match the retrieved devices.');
                    cancelSetup();
                }
            }
        } else if (parsedResponse.error === 'nodevice') {
            console.log('setupErrorHandler - No device found.');
            cancelSetup();
        } else {
            console.log('setupErrorHandler - Unexpected error: ' + parsedResponse.error);
            cancelSetup();
        }
    });
}


function confirmDevicePassword(){
    if(setupCOM == ""){return;}

    let password = document.getElementById("passwordInput").value;
    console.log(password);
    
    makeAjaxRequest("../ajax.php?data=sendPassword&com="+setupCOM+"&password="+password, function (responseText) {
        console.log(responseText);
        var jsonResponse = JSON.parse(responseText);
        if(jsonResponse.status == "correctPassword"){
            document.getElementById('notificaitonText').innerHTML = "";
            document.getElementById('devicePassword').style.display = "none";
            document.getElementById('deviceWifi').style.display = "inline";
            setupDevicePassword = password;
            getScannedWifi();

        }else if(jsonResponse.status == "wrongPassword"){
            document.getElementById('notificaitonText').innerHTML = "Wrong Password, Try Again.";
            document.getElementById('devicePassword').style.display = "inherit";
        }
    });
}

function rescanWifi(){
    document.getElementById('scanWifiNotification').style.display = "inherit";
    getScannedWifi();
}



function hideButton(element) {
    var button = element.querySelector('wifiCred')[0];
    button.style.display = 'none';
}


var setupSSID = "";
var setupElementID = "";

function initConnect(ssid, elementID){
    console.log("initConnect Called");
    console.log("ssid- "+ssid);
    console.log("elementID- "+elementID);

    console.log("Previous SSID- "+setupSSID);
    console.log("Previous elementID- "+setupElementID);

    if(setupElementID != "" && setupSSID != ""){
        console.log("Not First Click.");
        document.getElementById(setupElementID).style.display = 'none';
    }

    //After Hiding Previous Div, now update variables.
    setupSSID = ssid;
    setupElementID = elementID;

    setupElement = document.getElementById(setupElementID);
    setupElement.style.display = 'inline';
}

function connectDeviceToWifi(btnId, i){
    var wifipassword = document.getElementById(i+"PassInput").value;
    if(wifipassword == ""){
        alert("Wifi Password Can't be Empty!");
        return;
    }
    console.log("Establishing Wifi Connection.");
    document.getElementById(btnId).setAttribute('disabled', true);
    document.getElementById(btnId).innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connecting...';

    makeAjaxRequest("../ajax.php?data=connectToWifi&com="+setupCOM+"&ssid="+setupSSID+"&password="+wifipassword, function (response) {
        var Status = JSON.parse(response);
        console.log(Status.status);

        if(Status.status == "awatingConnection"){
            isConnected(btnId, i);
        }else{
            alert("Device Communication Error");
            document.getElementById(btnId).removeAttribute('disabled');
            document.getElementById(btnId).innerHTML = 'Connect';
        }
    });
}


function doneSetup(ipAddress, deviceID, devicePassword){
    console.log(ipAddress);
    console.log(deviceID);
    console.log(devicePassword);

    makeAjaxRequest("../ajax.php?data=saveDevice&ip="+ipAddress+"&deviceID="+deviceID+"&devicePassword="+devicePassword, function (response) {
        console.log(response);
        var jsonResponse = JSON.parse(response);
        if(jsonResponse.status == "deviceInserted"){
            document.getElementById('noticationDevice').innerHTML = 'Device is Set';
        }else if(jsonResponse.status == "deviceInsertedDefault"){
            document.getElementById('noticationDevice').innerHTML = 'Device is Set as Default';
        }else if(jsonResponse.status == "ipAddressUpdated"){
            document.getElementById('noticationDevice').innerHTML = 'Device was connected before, updated IP Address.';
        }else{
            alert('Device is Set, but an error occured configuring with the app.');
        }

        document.getElementById('SetupBox').style.display = 'none';
        document.getElementById('devicePassword').style.display = 'inherit';
        document.getElementById('deviceWifi').style.display = 'none';
        document.getElementById('wifiList').innerHTML = '';
        document.getElementById('setupDeviceId').innerHTML = "";
        document.getElementById('setupDeviceCOM').innerHTML = "";
        
        
        document.getElementById('SuccessBox').style.display = 'inline';
        
        setupCOM = "";
        setupDeviceID = 0;
        setupIsRunning = false;
        setupDevicePassword = "";

        reScan();
    });



}

function isConnected(btnId, i){
    makeAjaxRequest("../ajax.php?data=isConnected&com="+setupCOM, function (response) {
        var Status = JSON.parse(response);
        var ipAddress;
        if(Status.status == "connectionSuccess"){
            console.log('Connected to Wifi!');
            ipAddress = Status.ipAddress;
            document.getElementById(btnId).innerHTML = '<i class="fa-solid fa-check"></i> Connected';
            document.getElementById(i+"PassInput").style.display = "none";
            setTimeout(1000, function(){
                setupElement = document.getElementById(setupElementID);
                setupElement.style.display = 'none';
            });
            setTimeout(3000, doneSetup(ipAddress, setupDeviceID, setupDevicePassword));
        }
        if(Status.status == "wrongCredential"){
            console.log('Credential Error');
            alert('Wifi Credential Error!');
            document.getElementById(btnId).removeAttribute('disabled');
            document.getElementById(btnId).innerHTML = 'Connect';    
        }
    });
}

function getScannedWifi(){
    document.getElementById('rescanWifiBtn').style.display = 'none';
    makeAjaxRequest("../ajax.php?data=scanWifi&com="+setupCOM, function (response) {
        console.log(response);
        document.getElementById('scanWifiNotification').style.display = "none";
        var wifiList = JSON.parse(response);

        if (wifiList.status && wifiList.status === 'no_networks') {
            document.getElementById('wifiList').innerHTML = '<p>No Wi-Fi networks found.</p>';
        } else if (wifiList.wifiScanResults) {
            var wifiHtml = '';

            var i = 1;
            wifiList.wifiScanResults.forEach(function (network) {
                var signalStrengthClass = getSignalStrengthClass(network.RSSI);
                wifiHtml += '<div id="wifissidContainer'+i+'" class="wifissidContainer">'+
                            '<span>' + network.SSID +  '</span>'+
                            '<span onclick="initConnect(\''+network.SSID+'\',\'ssid'+i+'\')">&nbsp;&nbsp;&nbsp;&nbsp;<i class="pointer fa-solid fa-circle-arrow-right"></i></span>'+
                            '<span class="floatRight">'+
                            '<span class="wifiCred" id="ssid'+i+'" style="display: none;">'+
                            '<input placeholder="Wifi Password..." id="'+i+'PassInput"/>'+
                            '<button id="connectBtn'+i+'" onclick="connectDeviceToWifi(\'connectBtn'+i+'\','+i+');">Connect</button>'+
                            '</span>'+
                            generateSignalStrengthHTML(signalStrengthClass) + 
                            '</span>'+
                            '</div>';
                i++;
            });

            document.getElementById('wifiList').innerHTML = wifiHtml;
        }
        document.getElementById('rescanWifiBtn').style.display = 'inherit';
    });  
}

function generateSignalStrengthHTML(signalStrengthClass) {
    
    var html = '<div class="wave-cont ' + signalStrengthClass + '">' +
        '<div class="wv4 wave" style="">'+
        '<div class="wv3 wave" style="">'+
            '<div class="wv2 wave" style="">'+
            '<div class="wv1 wave">'+
            '</div></div></div></div></div>';

    return html;
}

function getSignalStrengthClass(rssi) {
    if (rssi >= -50) {
        return 'waveStrength-4';
    } else if (rssi >= -60) {
        return 'waveStrength-3';
    } else if (rssi >= -70) {
        return 'waveStrength-2';
    } else {
        return 'waveStrength-1';
    }
}




function setupDevice(COM, DeviceID){
    setupIsRunning = true;
    document.getElementById('SetupBox').style.display = "inherit";
    document.getElementById('setupDeviceId').innerHTML = DeviceID;
    document.getElementById('setupDeviceCOM').innerHTML = COM;
    setupCOM = COM;
    setupDeviceID = DeviceID;
}

function cancelSetup(){
    document.getElementById('SetupBox').style.display = "none";
    document.getElementById('setupDeviceId').innerHTML = "";
    document.getElementById('setupDeviceCOM').innerHTML = "";
    setupCOM = "";
    setupDeviceID = 0;
    setupIsRunning = false;
}


var resetCOM;
var resetDeviceID;
var resetPassword;

function resetDevice(com, deviceID){
    document.getElementById('id01').style.display = 'block';
    resetCOM = com;
    resetDeviceID = deviceID;
}

function confirmResetDevice(){
    resetPassword = document.getElementById('resetPass').value;
    if(resetPassword.length <= 0){
        alert("Password can't be empty.");
        return;
    }

    makeAjaxRequest("../ajax.php?data=resetDevice&com="+resetCOM+"&pass="+resetPassword,function(response){
        var jsonResponse = JSON.parse(response);
        if(jsonResponse.status == "reset"){
            alert('Reset Success!');
            document.getElementById('id01').style.display = 'none';
        }else if(jsonResponse.status == 'passwordError'){
            alert("Wrong Password");
        }else{
            alert("Error Resetting Device.");
        }
        reScan();
    });
}

function generateDeviceDivs(devices) {
    // Create a container div
    var containerDiv = document.createElement('div');
    containerDiv.classList.add('device-container'); // You can add a class for styling if needed

    // Iterate through devices and create divs
    devices.forEach(function (device) {
        var deviceDiv = document.createElement('div');
        deviceDiv.classList.add('device');
        deviceDiv.setAttribute('id',device.DeviceID);

        var buttonHtml;
        if(device.Configured == false){
            buttonHtml = '<button onclick="setupDevice(\''+ device.COMPort +'\', \''+ device.DeviceID +'\')" class="btn41-43 btn-43">Setup Device</a>';
        }else{
            buttonHtml = '<button onclick="resetDevice(\''+ device.COMPort +'\', \''+ device.DeviceID +'\')" class="btn41-43 btn-43">Reset Device</a>';
        }

        // Create and append elements for COMPort and DeviceID
        var deviceMainElement = document.createElement('div');
        deviceMainElement.classList.add('deviceMain');
        deviceMainElement.innerHTML = '<span class="fanHubLogo"><span class="logopart_fan">Fan</span><span class="logopart_hub">Hub</span></span>'+
        '<span class="fanhub_info"> <b>COM Port:</b> ' + device.COMPort + " | " + '<b>DeviceID:</b> ' + device.DeviceID + "</span>"+
        '<span class="floatRight">'+buttonHtml+'</span>';

        deviceDiv.appendChild(deviceMainElement);

        // Append the device div to the container div
        containerDiv.appendChild(deviceDiv);
    });

    return containerDiv;
}

function displayDeviceTable(responseText) {
    var jsonResponse = JSON.parse(responseText);
    if (jsonResponse.error === 'nodevice') {
        var noDeviceMessage = document.createElement('div');
        noDeviceMessage.classList.add('nodeviceMessage');
        noDeviceMessage.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i>&nbsp;&nbsp; No devices found.<br><small>Make sure the Serial Port is not busy. Try turning off the Arduino IDE.</small>';
        // Append the element to the container element
        document.getElementById('deviceTableContainer').innerHTML = "";
        document.getElementById('deviceTableContainer').appendChild(noDeviceMessage);
        
    }else if (jsonResponse.error === 'none') {
        // Get the devices array from the response and generate the table
        var devices = jsonResponse.devices;
        var table = generateDeviceDivs(devices);

        // Append the table to the document body or any other container element
        document.getElementById('deviceTableContainer').innerHTML = "";
        document.getElementById('deviceTableContainer').appendChild(table);
    } else {
        console.error('Error in JSON response:', jsonResponse.error);
    }
    document.getElementById('rescan_btn').removeAttribute('disabled');
}





//Core Functions
//Make Ajax Request Function
function makeAjaxRequest(url, callback) {
    var xhr = new XMLHttpRequest();
  
    // Configure it: GET-request for the given URL, asynchronously
    xhr.open('GET', url, true);
  
    // Setup a callback function to handle the response
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        // If the request is successful (status code 200), call the callback function
        callback(xhr.responseText);
      }
    };
  
    // Send the request
    xhr.send();
  }
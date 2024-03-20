<?php
require("connect_db.php"); 

if (isset($_GET['data'])) {

    if ($_GET['data'] == 'getPorts') {
        // Gets List of COM Ports
        // PowerShell command to get the list of serial ports
        $command = "[System.IO.Ports.SerialPort]::getportnames()";

        // Execute PowerShell command and capture the output
        $output = shell_exec('powershell.exe -command ' . escapeshellarg($command) . ' 2>&1');

        // Trim and split the output into an array
        $ports = explode("\n", trim($output));

        // Create an associative array to store the serial ports
        $serialPorts = array("serialPorts" => $ports);

        // Convert the array to JSON
        $jsonOutput = json_encode($serialPorts, JSON_PRETTY_PRINT);

        header('Content-Type: application/json; charset=utf-8');

        // Display the JSON results
        echo $jsonOutput;
    }


	
    if ($_GET['data'] == 'findDevice') {
        // PowerShell command to get the list of serial ports
        $commandGetPorts = "[System.IO.Ports.SerialPort]::getportnames()";
    
        // Execute PowerShell command and capture the output
        $outputGetPorts = shell_exec('powershell.exe -command ' . escapeshellarg($commandGetPorts) . ' 2>&1');
    
        // Trim and split the output into an array
        $ports = explode("\n", trim($outputGetPorts));
    
        $deviceCount = 0;
        $foundDevices = [];
    
        // Iterate through each serial port
        foreach ($ports as $port) {
            // Skip empty port names
            if (empty($port)) {
                continue;
            }
    
            // PowerShell commands to send and read a message
            $commandSendAndRead = "\$port= new-Object System.IO.Ports.SerialPort " . $port . ",115200,None,8,one; \$port.Open(); \$port.WriteLine('TheOnlyfansDeviceID'); \$port.ReadLine(); \$port.Close();";
    
            // Execute the PowerShell command
            $outputSendAndRead = shell_exec('powershell.exe -command ' . escapeshellarg($commandSendAndRead) . ' 2>&1');
    
            // Check if the response matches the expected pattern
            if (preg_match('/TheOnlyfansID-(\d+)-(set|notset)/', $outputSendAndRead, $matches)) {
                $deviceId = $matches[1];
                $isConfigured = ($matches[2] === 'set');
                $foundDevices[] = ['COMPort' => $port, 'DeviceID' => $deviceId, 'Configured' => $isConfigured];
                $deviceCount++;
            }
        }
    
        // Check if any devices were found
        if ($deviceCount > 0) {
            $result = ['error' => 'none', 'devices' => $foundDevices];
        } else {
            $result = ['error' => 'nodevice'];
        }
    
        header('Content-Type: application/json; charset=utf-8');
    
        // Print the found devices in JSON format
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
    

    if ($_GET['data'] == 'resetDevice') {
        // Check if 'com' and 'password' parameters are set
        if (isset($_GET['com']) && isset($_GET['pass'])) {
            $comPort = $_GET['com'];
            $password = $_GET['pass'];
    
            // PowerShell commands to send and read a password
            $commandSendAndRead = "\$port= new-Object System.IO.Ports.SerialPort ".$comPort.",115200,None,8,one; \$port.Open(); \$port.WriteLine('reset-".$password."'); \$port.ReadLine(); \$port.Close();";
    
            // Execute the PowerShell command
            $outputSendAndRead = shell_exec('powershell.exe -command ' . escapeshellarg($commandSendAndRead) . ' 2>&1');
    
            // Check if the response is empty
            if (trim($outputSendAndRead) === 'Wrongpassword') {
                $result = ['status' => 'passwordError'];
            } elseif (trim($outputSendAndRead) === 'TheOnlyFans_resetting') {
                $result = ['status' => 'reset'];
            } else {
                $result = ['status' => 'error', 'msg' => $outputSendAndRead];
            }
    
            header('Content-Type: application/json; charset=utf-8');
    
            // Print the result in JSON format
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            $result = ['error' => 'missingParameters'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }
    


    if ($_GET['data'] == 'sendPassword') {
        // Check if 'com' and 'password' parameters are set
        if (isset($_GET['com']) && isset($_GET['password'])) {
            $comPort = $_GET['com'];
            $password = $_GET['password'];

            // PowerShell commands to send and read a password
            $commandSendAndRead = "\$port= new-Object System.IO.Ports.SerialPort ".$comPort.",115200,None,8,one; \$port.Open(); \$port.WriteLine('TheOnlyfansPassword-".$password."'); \$port.ReadLine(); \$port.Close();";

            // Execute the PowerShell command
            $outputSendAndRead = shell_exec('powershell.exe -command ' . escapeshellarg($commandSendAndRead) . ' 2>&1');

            // Check if the response matches the expected pattern
            if (trim($outputSendAndRead) === 'WifiConf') {
                $result = ['status' => 'correctPassword'];
            } else {
                $result = ['status' => 'wrongPassword'];
            }

            //header('Content-Type: application/json; charset=utf-8');

            // Print the result in JSON format
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            $result = ['error' => 'missingParameters'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }

    if ($_GET['data'] == 'scanWifi') {
        // Check if 'com' parameter is set
        if (isset($_GET['com'])) {
            $comPort = $_GET['com'];
    
            // PowerShell commands to send and read a scanWifi message
            $commandScanWifi = "\$port= new-Object System.IO.Ports.SerialPort ".$comPort.",115200,None,8,one; \$port.Open(); \$port.WriteLine('scanWifi'); Start-Sleep -Seconds 8; \$port.ReadLine(); \$port.Close();";
    
            // Execute the PowerShell command
            $outputScanWifi = shell_exec('powershell.exe -command ' . escapeshellarg($commandScanWifi) . ' 2>&1');
    
            // Extract and parse SSID and RSSI information
            $ssidAndRssi = explode('","', trim($outputScanWifi, '"'));
    
            // Create an associative array to store the wifi scan results
            $wifiScanResults = [];
    
            // Parse SSID and RSSI information
            foreach ($ssidAndRssi as $item) {
                list($ssid, $rssi) = explode('(', $item);
                $rssi = rtrim($rssi, ')');
                
                // Filter out Wi-Fi signals with RSSI 0
                if ((int)$rssi !== 0) {
                    $wifiScanResults[] = ['SSID' => $ssid, 'RSSI' => (int)$rssi];
                }
            }
    
            header('Content-Type: application/json; charset=utf-8');
    
            // Print the filtered wifi scan results in JSON format
            echo json_encode(['wifiScanResults' => $wifiScanResults], JSON_PRETTY_PRINT);
        } else {
            $result = ['error' => 'missingParameters'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }
    

    
    if ($_GET['data'] == 'connectToWifi') {
        // Check if 'com' and 'password' parameters are set
        if (isset($_GET['com']) && isset($_GET['ssid']) && isset($_GET['password'])) {
            $comPort = $_GET['com'];
            $ssid = $_GET['ssid'];
            $password = $_GET['password'];

            // PowerShell commands to send and read a password
            $commandSendAndRead = "\$port= new-Object System.IO.Ports.SerialPort ".$comPort.",115200,None,8,one; \$port.Open(); \$port.WriteLine('ssid-".$ssid.":password-".$password."'); Start-Sleep -Seconds 1; \$port.ReadLine(); \$port.Close();";

            // Execute the PowerShell command
            $outputSendAndRead = shell_exec('powershell.exe -command ' . escapeshellarg($commandSendAndRead) . ' 2>&1');

            // Check if the response matches the expected pattern
            if (trim($outputSendAndRead) === 'SSID-Received') {
                $result = ['status' => 'awatingConnection'];
            } else {
                $result = ['status' => 'serialError'];
            }

            header('Content-Type: application/json; charset=utf-8');

            // Print the result in JSON format
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            $result = ['status' => 'missingParameters'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }

    if ($_GET['data'] == 'isConnected') {
        // Check if 'com' parameter is set
        if (isset($_GET['com'])) {
            $comPort = $_GET['com'];
    
            // PowerShell commands to send and read a checkConnection message
            $commandCheckConnection = "\$port= new-Object System.IO.Ports.SerialPort ".$comPort.",115200,None,8,one; \$port.Open(); \$port.WriteLine('isConnected'); Start-Sleep -Seconds 7; \$port.ReadLine(); \$port.Close();";
    
            // Execute the PowerShell command
            $outputCheckConnection = shell_exec('powershell.exe -command ' . escapeshellarg($commandCheckConnection) . ' 2>&1');
    
            // Check the response for different scenarios
            if (trim($outputCheckConnection) === 'wifiCredError') {
                $result = ['status' => 'wrongCredential'];
            } elseif (preg_match('/ip-(\d+\.\d+\.\d+\.\d+)/', $outputCheckConnection, $matches)) {
                $ipAddress = $matches[1];
                $result = ['status' => 'connectionSuccess', 'ipAddress' => $ipAddress];
            } else {
                $result = ['status' => 'unknownError'];
            }
    
            header('Content-Type: application/json; charset=utf-8');
    
            // Print the result in JSON format
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            $result = ['error' => 'missingParameters'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }




    if ($_GET['data'] == 'saveDevice') {
        // Check if 'com' parameter is set
        if (isset($_GET['ip']) && isset($_GET['deviceID']) && isset($_GET['devicePassword'])) {
            $ipAddress = $_GET['ip'];
            $deviceID = $_GET['deviceID'];
            $devicePassword = $_GET['devicePassword'];
    
            // Check if deviceID already exists
            $checkQuery = $db->query("SELECT * FROM device WHERE deviceID = '$deviceID'");
            $existingDevice = $checkQuery->fetchArray(SQLITE3_ASSOC);
    
            if ($existingDevice) {
                // Device with the same deviceID exists
                // Check if the provided password matches the existing password
                if ($existingDevice['devicePassword'] === $devicePassword) {
                    // Passwords match, update the IP address
                    $updateQuery = $db->prepare("UPDATE device SET ipAddress = :ipAddress WHERE deviceID = :deviceID");
                    $updateQuery->bindValue(':ipAddress', $ipAddress);
                    $updateQuery->bindValue(':deviceID', $deviceID);
    
                    $updateResult = $updateQuery->execute();
    
                    if ($updateResult) {
                        // IP address successfully updated
                        $result = ['status' => 'ipAddressUpdated'];
                    } else {
                        // Error updating IP address
                        $result = ['status' => 'ipAddressUpdateError'];
                    }
                } else {
                    // Passwords do not match
                    $result = ['status' => 'incorrectPassword'];
                }
            } else {
                // Device with the provided deviceID does not exist
                // Insert a new device with status "default" if no default device is found
                $defaultQuery = $db->query("SELECT COUNT(*) FROM device WHERE status = 'default'");
                $defaultCount = $defaultQuery->fetchArray(SQLITE3_NUM)[0];
                $default = false;
                if ($defaultCount == 0) {
                    // No device with status "default" found, set status to "default"
                    $insertQuery = $db->prepare("INSERT INTO device (name, deviceID, devicePassword, ipAddress, status) VALUES (:name, :deviceID, :devicePassword, :ipAddress, 'default')");
                    $default = true;
                } else {
                    // Devices with status "default" exist, insert with default values
                    $insertQuery = $db->prepare("INSERT INTO device (name, deviceID, devicePassword, ipAddress, status) VALUES (:name, :deviceID, :devicePassword, :ipAddress, :status)");
                }
    
                // You may need to set appropriate values for 'name' field
                $insertQuery->bindValue(':name', 'NoName');
                $insertQuery->bindValue(':deviceID', $deviceID);
                $insertQuery->bindValue(':devicePassword', $devicePassword);
                $insertQuery->bindValue(':ipAddress', $ipAddress);
                $insertQuery->bindValue(':status', 'none');
    
                $insertResult = $insertQuery->execute();
    
                if ($insertResult) {
                    // Device successfully inserted
                    if($default){
                        $result = ['status' => 'deviceInsertedDefault'];
                    }else{
                        $result = ['status' => 'deviceInserted'];
                    }
                } else {
                    // Error inserting device
                    $result = ['status' => 'deviceInsertError'];
                }
            }
    
            // Close database connection
            $db->close();
    
            header('Content-Type: application/json; charset=utf-8');
    
            // Print the result in JSON format
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            $result = ['status' => 'missingParameters'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }

}
?>

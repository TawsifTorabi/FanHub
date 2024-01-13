<?php
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
      if (preg_match('/TheOnlyfansID-(\d+)/', $outputSendAndRead, $matches)) {
          $deviceId = $matches[1];
          $foundDevices[] = ['COMPort' => $port, 'DeviceID' => $deviceId];
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
?>

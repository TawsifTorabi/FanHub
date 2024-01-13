<?php
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
?>

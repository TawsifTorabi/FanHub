//Using LEDC
#include <EEPROM.h>
#include <WiFi.h>
#include <AsyncTCP.h>
#include <ESPAsyncWebServer.h>

AsyncWebServer server(80);
AsyncWebSocket ws("/remote");

const int ledPin = 2; // Define the LED pin

long deviceID = 124752940;  //Device 9 Digit Unique Identifier
String devicePassword = "dunki1432";  //Device Unique Password

bool isDeviceSet = false; //Device Setup Boolean
int isDeviceSetMem = 97;  //EEPROM Address to Store Setup Bool
int currentModeMem = 98;  //EEPROM Address to Store Setup Bool

int fanPWM = 12;
int potPin = 34;

//Input of FAN is 14 volts.

int fanChannel = 0;
int freq = 8000;
int resolution = 8;

int currentMode = 0;

//For Manual Mode
int potValue;
int mappedValue;

//Reset Device with EEPROM Clear
void Reset(){
  Serial.println("TheOnlyFans_resetting");

  // Clear Both Saved Wifi SSID & Password
  for (int i = 0; i < 102; ++i) {
    EEPROM.write(i, 0);
  }

  // Set Setup Boolean to Zero
  EEPROM.write(isDeviceSetMem, 0);
  EEPROM.commit();
  delay(1000);
  ESP.restart();
}

int statusLEDred = 14;
int statusLEDgreen = 26;
int statusLEDblue = 27;
//RGB
void setColor(int redValue, int greenValue, int blueValue) {
  analogWrite(statusLEDred, 255 - redValue);
  analogWrite(statusLEDgreen, 255 - greenValue);
  analogWrite(statusLEDblue, 255 - blueValue);
}

void FanSpeed(int speed){
  //analogWrite(fanPWM, speed);
  ledcWrite(fanChannel, speed);
}

// Set and Save Current Fan Mode
void FanMode(int mode, bool set){
  currentMode = mode;

  if(mode == 1){
    //quite
    FanSpeed(4);
    //Color White
    setColor(0,0,255);
  }
  if(mode == 2){
    //normal
    FanSpeed(10);
    //Color Green
    setColor(0, 255, 0);
  }
  if(mode == 3){
    //turbo
    FanSpeed(255);
    //Color Red
    setColor(255, 0, 0);
  }
  if(mode == 4){
    //manual
    //Do Nothing, loop() will do the rest.
    //Purple
    setColor(230, 0, 230);
  }
  if(mode == 5){
    //Custom
    FanSpeed(200);
    //Rose
    setColor(255, 25, 140);
  }
  if(mode == 6){
    //auto
    //Rose
    FanSpeed(200);
    setColor(255, 255, 0);
  }
  if(mode == 7){
    //off
    FanSpeed(0);
    //red
    setColor(40, 40, 40);
  }

  if(set){
    EEPROM.write(currentModeMem, currentMode);
    EEPROM.commit();
  }
}

String qsid = "";
String qpass = "";

// Wifi Functions
// Function to handle SSID and password and Connect to WiFi
void handleSSIDAndPassword(const String& ssid, const String& password) {

  // Start Wifi Connection
  WiFi.begin(ssid.c_str(), password.c_str());

  // Test Wifi
  if (testWifi()) {
    // Serial.println("Successfully Connected!");

    qsid = ssid;
    qpass = password;

    initWebSocket();
    createWebServer();
    server.begin();
    return;
  } 
}

// Confirm Connection Request
void confirmConnection(){
  // Test Wifi
  if (testWifi()) {
    // Serial.println("Successfully Connected!");

    // Clear EEPROM for WiFi SSID and PASSWORD
    for (int i = 0; i < 96; ++i) {
      EEPROM.write(i, 0);
    }

    // Write SSID and Password
    for (int i = 0; i < qsid.length(); ++i){
      EEPROM.write(i, qsid[i]);
    }
    for (int i = 0; i < qpass.length(); ++i){
      EEPROM.write(32 + i, qpass[i]);
    }

    Serial.print("ip-");
    Serial.print(WiFi.localIP());
    Serial.println();
    EEPROM.write(isDeviceSetMem, 1);
    isDeviceSet = true;
    EEPROM.commit();
    return;
  } else {
    Serial.println("wifiCredError");
  }
}

// Test the Wifi Connection
bool testWifi(void){
  int c = 0;
  // Serial.println("Waiting for Wifi to connect");
  // Try to Connect to the wifi for 10 seconds.
  while ( c < 20 ) {
    if (WiFi.status() == WL_CONNECTED){
      return true;
    }
    delay(500);
    // Serial.print("*");
    c++;
  }
  // Serial.println("");
  // Serial.println("Connect timed out, opening AP");
  return false;
}

String collectedSSIDs = "";
// Scan For Available Wifi Networks
void listAvailableSSIDs() {
  // Serial.println("scanning");
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  delay(100);

  int n = WiFi.scanNetworks();

  if (n == 0) {
    Serial.println("no_networks");
  } else {
    collectedSSIDs = "";  // Clear the variable before collecting SSIDs

    for (int i = 0; i < n; ++i) {
      collectedSSIDs += "\"" + WiFi.SSID(i) + "(" + WiFi.RSSI(i) + ")" + "\"";

      if (i < n - 1) {
        collectedSSIDs += ",";
      }
    }

    Serial.println(collectedSSIDs);
  }
}

// Web Socket Functions
void onEvent(AsyncWebSocket *server, AsyncWebSocketClient *client, AwsEventType type,
             void *arg, uint8_t *data, size_t len) {
  switch (type) {
    case WS_EVT_CONNECT:
      Serial.printf("WebSocket client #%u connected from %s\n", client->id(), client->remoteIP().toString().c_str());
      break;
    case WS_EVT_DISCONNECT:
      Serial.printf("WebSocket client #%u disconnected\n", client->id());
      break;
    case WS_EVT_DATA:
      handleWebSocketMessage(arg, data, len);
      break;
    case WS_EVT_PONG:
    case WS_EVT_ERROR:
      break;
  }
}

void initWebSocket() {
  ws.onEvent(onEvent);
  server.addHandler(&ws);
}

void handleWebSocketMessage(void *arg, uint8_t *data, size_t len) {
  AwsFrameInfo *info = (AwsFrameInfo *)arg;
  if (info->final && info->index == 0 && info->len == len && info->opcode == WS_TEXT) {
    data[len] = 0;
    const char *message = (char *)data;
    Serial.println(message);

    // Extract password and mode from the incoming message
    char mode[10];      // Assuming mode can be up to 10 characters
    char pwm[4];      // Assuming mode can be up to 4 characters
    char password[50];  // Assuming a reasonable maximum password length
    if (sscanf(message, "p:%49[^,], MODE:%9s", password, mode) == 2) {

      // Check if the password matches the stored devicePassword
      if (strcmp(password, devicePassword.c_str()) == 0) {
        // Password is correct, now handle different modes
        if (strcmp(mode, "quiet") == 0) {
          // Handle quiet mode
          FanMode(1, true);
        } else if (strcmp(mode, "normal") == 0) {
          // Handle normal mode
          FanMode(2, true);
        } else if (strcmp(mode, "turbo") == 0) {
          // Handle turbo mode
          FanMode(3, true);
        }else if (strcmp(mode, "manual") == 0) {
          // Handle manual mode
          FanMode(4, true);
        } else if (strcmp(mode, "custom") == 0) {
          // Handle custom mode
          FanMode(5, true);
        } else if (strcmp(mode, "auto") == 0) {
          // Handle custom mode
          FanMode(6, true);
        } else if (strcmp(mode, "off") == 0) {
          // Handle custom mode
          FanMode(7, true);
        }
      } else {
        // Incorrect password, handle accordingly
        Serial.println("wrongPassword");
      }
    }

    if (sscanf(message, "p:%49[^,], PWM:%3s", password, pwm) == 2) {
      if(currentMode == 5 || currentMode == 6){

        // Check if the password matches the stored devicePassword
        if (strcmp(password, devicePassword.c_str()) == 0) {
          // Password is correct, now handle
            FanSpeed(atoi(pwm));
        } else {
          // Incorrect password, handle accordingly
          Serial.println("wrongPassword");
        }
      }
    }

  }
}

String content;
void createWebServer(){
    server.on("/TheOnlyfans", [](AsyncWebServerRequest *request) {
      content = "TheOnlyfansID-";
      content += deviceID;
      content += "-";
      if(isDeviceSet == false){
        content +="notset";
      }else{
        content +="set";
      }

      request->send(200, "text/plain", content);
    });

  } 


// ESP Main Functions
void setup() {
  Serial.begin(115200); // Initialize serial communication
  Serial.println("TheOnlyFans_init");
  WiFi.setHostname("Fanhub-" + deviceID);

  pinMode(ledPin, OUTPUT); // Set the LED pin as output
  pinMode(potPin, INPUT);

  pinMode(statusLEDred, OUTPUT);
  pinMode(statusLEDgreen, OUTPUT);
  pinMode(statusLEDblue, OUTPUT);

  //pinMode(fanPWM, OUTPUT);
  ledcSetup(fanChannel, freq, resolution);
  ledcAttachPin(fanPWM, fanChannel);

  EEPROM.begin(512); // Initializing EEPROM
  delay(1000);

  // Check if Device is Set by User
  if (EEPROM.read(isDeviceSetMem) == 1) {
    isDeviceSet = true;

    currentMode = EEPROM.read(currentModeMem);
    Serial.print("Reloaded Mode: ");
    Serial.print(currentMode);
    Serial.println();

    FanMode(currentMode, false);

    String esid = "";
    for (int i = 0; i < 32; ++i) {
      char ch = EEPROM.read(i);
      if (ch == 0) {
        break; // Stop if null terminator is encountered
      }
      esid += ch;
    }

    String epass = "";
    for (int i = 32; i < 96; ++i) {
      epass += char(EEPROM.read(i));
    }

    Serial.println(epass.c_str());
    Serial.println(esid.c_str());

    // Continuous retry until network is found
    while (true) {
      // Scan for available networks
      int numberOfNetworks = WiFi.scanNetworks();

      // Check if the specified SSID is found
      bool foundSSID = false;
      for (int i = 0; i < numberOfNetworks; i++) {
        if (strcmp(WiFi.SSID(i).c_str(), esid.c_str()) == 0) {
          foundSSID = true;
          break;
        }
      }

      // Print result
      if (!foundSSID) {
        Serial.println("WiFi_notfound");
        // Retry connecting to WiFi
        //Serial.println("Retrying WiFi connection...");
        delay(2000); // Wait for 2 seconds before retrying
      } else {
        Serial.println("WiFi connected!");
        WiFi.begin(esid.c_str(), epass.c_str());
        if (testWifi()) {
          initWebSocket();
          createWebServer();
          server.begin();
          Serial.println(WiFi.localIP());
          break; // Break out of the retry loop if WiFi is successfully connected
        }
      }
    }
  } else {
    isDeviceSet = false;
  }
}


void loop() {
  // Handle WiFi reconnection if disconnected
  if(isDeviceSet){
    if (WiFi.status() != WL_CONNECTED) {
      //Serial.println("WiFi disconnected. Reconnecting...");

      // Retry connecting to WiFi
      WiFi.begin(qsid.c_str(), qpass.c_str());

      // Wait for WiFi to reconnect
      int retryCount = 0;
      while (WiFi.status() != WL_CONNECTED && retryCount < 5) {
        delay(500); // Wait for 0.5 seconds before checking again
        //Serial.print(".");
        retryCount++;
      }

      if (WiFi.status() == WL_CONNECTED) {
        //Serial.println("\nWiFi reconnected!");
        initWebSocket();
        createWebServer();
        server.begin();
        Serial.println(WiFi.localIP());
      } else {
        //Serial.println("\nWiFi reconnect failed. Retrying...");
        return; // Skip the rest of the loop and retry in the next iteration
      }
    }
  }

  if (Serial.available() > 0) {
    // Read the incoming data
    String incomingMessage = Serial.readStringUntil('\n');
    
    // If message is "scanWifi"
    if(incomingMessage == "scanWifi") {
      // Print Scanned Wifi SSiDs
      listAvailableSSIDs();
    }

    // If message is "isConnected"
    if(incomingMessage == "isConnected") {
      // Print Scanned Wifi SSiDs
      confirmConnection();
    }

    // If message is "restart"
    if(incomingMessage == "restart") {
      // Restart ESP
      ESP.restart();
    }

    // If message is "reset"
    if (incomingMessage.startsWith("reset-")) {
      // Extract the provided password from the incoming message
      String providedPassword = incomingMessage.substring(6);

      // Check if the provided password matches the correct password
      if (providedPassword == devicePassword) {
        // Password is correct, restart ESP
        Reset();
      } else {
        // Wrong password, handle the error (e.g., send an error message)
        Serial.println("Wrongpassword");
      }
    }

    // Example Message - "TheOnlyfansDeviceID"
    if (incomingMessage == "TheOnlyfansDeviceID") {
      // Flash the LED
      Serial.print("TheOnlyfansID-");
      Serial.print(deviceID);
      Serial.print("-");
      if(isDeviceSet == false){
        Serial.print("notset");
      }else{
        Serial.print("set");
      }
      Serial.println();
    }

    // Example Message - "TheOnlyfansPassword-dunki1432"
    // Check if the incoming message is a password message
    if (incomingMessage.startsWith("TheOnlyfansPassword-")) {
      // Extract the password from the message
      String receivedPassword = incomingMessage.substring(20);
      
      // Compare the received password with the stored password
      if (receivedPassword == devicePassword && isDeviceSet == false) {
        // Password matches, call your function
        Serial.println("WifiConf");
      } else {
        // Password does not match, handle accordingly
        Serial.println("wrongPassword");
      }
    }
    
    // Example Message = "ssid-your_wifi_name:password-your_wifi_password"
    // Check if the incoming message is in the new format
    if (incomingMessage.startsWith("ssid-")) {
      Serial.println("SSID-Received");
      // Extract SSID and password from the message
      int ssidIndex = incomingMessage.indexOf("ssid-") + 5;
      int colonIndex = incomingMessage.indexOf(':');
      
      if (colonIndex != -1) {
        // Extract SSID and password
        String ssid = incomingMessage.substring(ssidIndex, colonIndex);
        String password = incomingMessage.substring(colonIndex + 1).substring(9);

        // Handle SSID and password
        handleSSIDAndPassword(ssid, password);
      }
    }
  }

  // For Manual Mode
  if(currentMode == 4){
    potValue = analogRead(potPin);
    potValue = map(potValue, 0, 4096, 0, 60);
    FanSpeed(potValue);
    Serial.println(potValue);
  }
}

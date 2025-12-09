#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <HX711.h>
#include <Ultrasonic.h>
#include <ArduinoJson.h>

// -------- WiFi Settings --------
const char* ssid = "why pie";
const char* password = "ratrat110813";

// -------- Supabase Settings --------
const char* supabaseUrlRead = "https://drogypndtmqhpohoedzl.supabase.co/rest/v1/sensors?id=eq.a535ec49-4d69-453b-9cc8-77751f559020"; 
const char* supabaseUrlWrite = "https://drogypndtmqhpohoedzl.supabase.co/rest/v1/sensor_readings";
const char* supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRyb2d5cG5kdG1xaHBvaG9lZHpsIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQyMDk3ODYsImV4cCI6MjA3OTc4NTc4Nn0.sfOSgeJIpdXQ9DM1NpvWGcd9p923ydfeE4B0BS9IRdQ";

// -------- Ultrasonic Sensor (HC-SR04) --------
#define TRIG_PIN 12
#define ECHO_PIN 14
Ultrasonic ultrasonic(TRIG_PIN, ECHO_PIN);

// -------- Load Cell (HX711) --------
#define LOADCELL_DOUT  27
#define LOADCELL_SCK  26
HX711 scale;

// -------- Timing --------
unsigned long previousMillis = 0;
const long interval = 30000; // 30 seconds

// -------- Sensor Flags --------
bool ultrasonicEnabled = true;
bool loadCellEnabled = true;

void setup() {
  Serial.begin(115200);

  // WiFi connection
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("Connected!");

  // Load Cell setup
  scale.begin(LOADCELL_DOUT, LOADCELL_SCK);
  scale.set_scale(-83727); // <-- Corrected scale factor
  scale.tare();

  Serial.println("HX711 and Ultrasonic initialized");
}

void loop() {
  unsigned long currentMillis = millis();
  if(currentMillis - previousMillis >= interval){
    previousMillis = currentMillis;

    // -------- Get sensor configuration --------
    getSensorSettings();

    // -------- Read sensor data --------
    float distance = 0;
    float weight = 0;

    if(ultrasonicEnabled){
      distance = ultrasonic.read();
    }
    if(loadCellEnabled){
      weight = scale.get_units(5); // average 5 readings
    }

    int rssi = WiFi.RSSI();

    // -------- Send data to Supabase --------
    if(WiFi.status() == WL_CONNECTED){
      WiFiClientSecure client;
      client.setInsecure();
      HTTPClient http;
      http.begin(client, supabaseUrlWrite);
      http.addHeader("Content-Type", "application/json");
      http.addHeader("apikey", supabaseKey);
      http.addHeader("Authorization", String("Bearer ") + supabaseKey);

      String payload = "{";
      if(ultrasonicEnabled) payload += "\"ultrasonic_distance_cm\":" + String(distance, 2) + ",";
      if(loadCellEnabled) payload += "\"load_cell_weight_kg\":" + String(weight, 2) + ",";
      payload += "\"signal_strength\":" + String(rssi) + ",";
      payload += "\"bin_id\":\"bc805e27-3449-4633-b438-0f1062092095\"";
      payload += "}";

      int httpResponseCode = http.POST(payload);
      if(httpResponseCode > 0){
        Serial.println("Data sent successfully!");
      } else {
        Serial.println("Error sending data: " + String(httpResponseCode));
      }

      http.end();
    }
  }
}

// -------- Function to get sensor config from Supabase --------
void getSensorSettings() {
  if(WiFi.status() == WL_CONNECTED){
    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient http;
    http.begin(client, supabaseUrlRead);
    http.addHeader("apikey", supabaseKey);
    http.addHeader("Authorization", String("Bearer ") + supabaseKey);

    int httpResponseCode = http.GET();
    if(httpResponseCode > 0){
      String payload = http.getString();
      // Parse JSON
      DynamicJsonDocument doc(512);
      deserializeJson(doc, payload);

      if(doc.size() > 0){
        ultrasonicEnabled = doc[0]["ultrasonic_enabled"];
        loadCellEnabled = doc[0]["load_cell_enabled"];
      }
      Serial.print("Ultrasonic Enabled: "); Serial.println(ultrasonicEnabled);
      Serial.print("Load Cell Enabled: "); Serial.println(loadCellEnabled);
    } else {
      Serial.println("Failed to get sensor config");
    }

    http.end();
  }
}

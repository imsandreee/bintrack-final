#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <HX711.h>
#include <Ultrasonic.h>
#include <ArduinoJson.h> // Ensure you have this library installed

// -------------------------------------------------------------------
// 1. Configuration Constants
// -------------------------------------------------------------------

// -------- WiFi Settings --------
const char* ssid = "why pie";
const char* password = "ratrat110813";

// -------- Supabase Settings --------
const char* supabaseUrlRead = "https://drogypndtmqhpohoedzl.supabase.co/rest/v1/sensors?id=eq.a535ec49-4d69-453b-9cc8-77751f559020"; 
const char* supabaseUrlWrite = "https://drogypndtmqhpohoedzl.supabase.co/rest/v1/sensor_readings";
const char* supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRyb2d5cG5kdG1xaHBvaG9lZHpsIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQyMDk3ODYsImV4cCI6MjA3OTc4NTc4Nn0.sfOSgeJIpdXQ9DM1NpvWGcd9p923ydfeE4B0BS9IRdQ";
const char* BIN_ID = "bc805e27-3449-4633-b438-0f1062092095"; // Added for clarity

// -------- Ultrasonic Sensor (HC-SR04) --------
#define TRIG_PIN 12
#define ECHO_PIN 14
Ultrasonic ultrasonic(TRIG_PIN, ECHO_PIN);                          

// -------- Load Cell (HX711) --------
#define LOADCELL_DOUT 26
#define LOADCELL_SCK 27
HX711 scale;

// Calibration Constant (Used for initial setup)
const long LOAD_CELL_SCALE_FACTOR = -83727; 
// Error value to display if HX711 fails/is unplugged
const float WEIGHT_ERROR_VALUE = -1.0; 
// Timeout for HX711 ready check (milliseconds)
const long HX711_READY_TIMEOUT = 200; 

// -------- Timing --------
unsigned long previousSupabaseMillis = 0;
const long SUPABASE_INTERVAL = 30000; // 30 seconds

unsigned long previousSerialMillis = 0;
const long SERIAL_MONITOR_INTERVAL = 500; // 0.5 seconds

// -------- Sensor Flags --------
bool ultrasonicEnabled = true;
bool loadCellEnabled = true;

// -------- Sensor Variables (Global) --------
float currentDistance = 0;
float currentWeight = 0; // Will be -1.0 on error

// Function Prototypes
void readAndPrintSensors();
void sendDataAndGetConfig();
float readLoadCell(int times); // New helper function
void getSensorSettings();

void setup() {
    Serial.begin(115200);

    // WiFi connection
    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nConnected! IP address: " + WiFi.localIP().toString());

    // Load Cell setup
    scale.begin(LOADCELL_DOUT, LOADCELL_SCK);
    scale.set_scale(LOAD_CELL_SCALE_FACTOR); 
    scale.tare();

    Serial.println("HX711 and Ultrasonic initialized");
    Serial.println("--- Starting Real-time Sensor Monitoring ---");
    Serial.println("Reading and printing data every 500ms. Sending to Supabase every 30s.");
}

void loop() {
    unsigned long currentMillis = millis();

    // --- 1. Real-time Serial Monitor Task (Fast Loop) ---
    if (currentMillis - previousSerialMillis >= SERIAL_MONITOR_INTERVAL) {
        previousSerialMillis = currentMillis;
        readAndPrintSensors();
    }

    // --- 2. Supabase Communication Task (Slow Loop) ---
    if (currentMillis - previousSupabaseMillis >= SUPABASE_INTERVAL) {
        previousSupabaseMillis = currentMillis;
        sendDataAndGetConfig();
    }
}

// -------------------------------------------------------------------
// Helper Functions
// -------------------------------------------------------------------

/**
 * @brief Reads the load cell, checks if it's ready, and returns weight or an error.
 * @param times The number of readings to average.
 * @return float The weight in kg, or WEIGHT_ERROR_VALUE (-1.0) if reading failed.
 */
float readLoadCell(int times) {
    if (scale.wait_ready_timeout(HX711_READY_TIMEOUT)) {
        return scale.get_units(times);
    } else {
        return WEIGHT_ERROR_VALUE;
    }
}


/**
 * @brief Reads sensor data and prints it to the Serial Monitor.
 * This runs fast to provide real-time feedback.
 */
void readAndPrintSensors() {
    
    if (ultrasonicEnabled) {
        currentDistance = ultrasonic.read();
    } else {
        currentDistance = 0.0;
    }
    
    if (loadCellEnabled) {
        // Use the new helper function for safety, averaging just once for speed
        currentWeight = readLoadCell(1); 
    } else {
        currentWeight = 0.0;
    }

    Serial.print("Time: "); Serial.print(millis() / 1000); Serial.print("s | ");
    Serial.print("Dist: "); Serial.print(currentDistance, 2); Serial.print(" cm | ");
    
    // Print Weight or ERROR
    Serial.print("Weight: ");
    if (currentWeight == WEIGHT_ERROR_VALUE) {
        Serial.print("ERROR");
    } else {
        Serial.print(currentWeight, 2);
    }
    Serial.print(" kg | ");
    
    Serial.print("Enabled: U="); Serial.print(ultrasonicEnabled); Serial.print(", L="); Serial.println(loadCellEnabled);
    if (currentWeight == WEIGHT_ERROR_VALUE) {
        Serial.println(">>> HX711 FAILED TO READ: Check wiring (VCC/GND/SCK/DOUT) <<<");
    }
}

/**
 * @brief Handles the slower tasks: getting config and sending data to Supabase.
 */
void sendDataAndGetConfig() {
    
    // -------- Get sensor configuration --------
    getSensorSettings();

    // Re-read sensors just before posting
    if (ultrasonicEnabled) {
        currentDistance = ultrasonic.read();
    }
    if (loadCellEnabled) {
        // Use averaging for the final reading posted to the cloud
        currentWeight = readLoadCell(5); 
    }

    int rssi = WiFi.RSSI();

    // -------- Send data to Supabase --------
    if (WiFi.status() == WL_CONNECTED) {
        WiFiClientSecure client;
        client.setInsecure();
        HTTPClient http;
        http.begin(client, supabaseUrlWrite);
        http.addHeader("Content-Type", "application/json");
        http.addHeader("apikey", supabaseKey);
        http.addHeader("Authorization", String("Bearer ") + supabaseKey);

        // *** Using ArduinoJson for robust payload creation ***
        DynamicJsonDocument doc(256);
        
        if (ultrasonicEnabled) {
            doc["ultrasonic_distance_cm"] = currentDistance;
        }
        
        // Only include weight if the reading was successful (not the error value)
        if (loadCellEnabled && currentWeight != WEIGHT_ERROR_VALUE) {
            doc["load_cell_weight_kg"] = currentWeight;
        }
        
        doc["signal_strength"] = rssi;
        doc["bin_id"] = BIN_ID;

        String jsonPayload;
        serializeJson(doc, jsonPayload);
        // *** End ArduinoJson ***

        Serial.print("--- Supabase Post: "); Serial.print(jsonPayload); Serial.println(" ---");
        
        int httpResponseCode = http.POST(jsonPayload);
        if (httpResponseCode > 0) {
            Serial.println("Data sent successfully! Response: " + String(httpResponseCode));
        } else {
            Serial.println("Error sending data: " + String(httpResponseCode));
        }

        http.end();
    }
}


// -------- Function to get sensor config from Supabase --------
void getSensorSettings() {
    if (WiFi.status() == WL_CONNECTED) {
        WiFiClientSecure client;
        client.setInsecure();
        HTTPClient http;
        http.begin(client, supabaseUrlRead);
        http.addHeader("apikey", supabaseKey);
        http.addHeader("Authorization", String("Bearer ") + supabaseKey);

        int httpResponseCode = http.GET();
        if (httpResponseCode > 0) {
            String payload = http.getString();
            DynamicJsonDocument doc(512);
            deserializeJson(doc, payload);

            if (doc.size() > 0) {
                ultrasonicEnabled = doc[0]["ultrasonic_enabled"];
                loadCellEnabled = doc[0]["load_cell_enabled"];
            }
            Serial.print("NEW CONFIG: Ultrasonic Enabled: "); Serial.print(ultrasonicEnabled);
            Serial.print(", Load Cell Enabled: "); Serial.println(loadCellEnabled);
        } else {
            Serial.println("Failed to get sensor config. Status: " + String(httpResponseCode));
        }

        http.end();
    }
}
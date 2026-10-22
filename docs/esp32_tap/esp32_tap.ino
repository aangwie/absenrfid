/*
 * ============================================================================
 *  Absen RFID — Firmware ESP32 + MFRC522
 * ============================================================================
 *
 *  Cara kerja:
 *    1. Sambung ke WiFi.
 *    2. Baca UID kartu MIFARE (MFRC522).
 *    3. Kirim UID ke POST /api/tap dengan header X-API-Key.
 *    4. Tampilkan hasil dari server: MASUK / TERLAMBAT / PULANG / GAGAL.
 *
 *  Library yang perlu dipasang (Arduino IDE → Library Manager):
 *    - MFRC522 by GithubCommunity
 *    - ArduinoJson by Benoit Blanchon (v7)
 *
 *  Board: ESP32 Dev Module
 *
 *  Wiring MFRC522 (WAJIB 3.3V, jangan 5V):
 *    MFRC522   ESP32
 *    SDA/SS    5
 *    SCK       18
 *    MOSI      23
 *    MISO      19
 *    RST       22
 *    3.3V      3.3V
 *    GND       GND
 *    IRQ       (tidak dipakai)
 *
 *  Catatan penting:
 *    - Isi WIFI_SSID, WIFI_PASS, API_BASE_URL, dan API_KEY di bawah.
 *    - API_BASE_URL TANPA garis miring di akhir, contoh:
 *        https://absensi.sekolah.sch.id
 *    - Gunakan HTTPS di hosting. Untuk produksi yang lebih aman, ganti
 *      client.setInsecure() dengan sertifikat root CA (lihat catatan di bawah).
 *    - Format UID default = desimal big-endian, sama seperti data contoh
 *      proyek ini. Kalau kartu Anda didaftarkan dalam hex, ubah UID_FORMAT.
 * ============================================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>

// ------------------------------ KONFIGURASI --------------------------------
const char* WIFI_SSID     = "NAMA_WIFI";
const char* WIFI_PASS     = "PASSWORD_WIFI";

// Contoh: "https://absensi.sekolah.sch.id"  (tanpa "/" di akhir)
const char* API_BASE_URL  = "https://domain-anda.com";

// Salin dari Pengaturan -> API Perangkat (atau RFID_API_KEY di .env)
const char* API_KEY       = "GANTI_DENGAN_KUNCI_PERANGKAT_ANDA";

// 1 = kirim UID desimal (contoh: 2234749610)
// 0 = kirim UID hex    (contoh: 8532A6AA)
#define UID_FORMAT_DECIMAL 1

// Jeda minimum sebelum kartu yang sama boleh ditap lagi (milidetik).
// Mencegah satu kartu terkirim berkali-kali karena reader membaca terus.
const unsigned long DEBOUNCE_MS = 3000;

// ------------------------------ PIN MFRC522 --------------------------------
#define SS_PIN   5
#define RST_PIN  22

// --------------------------- PIN INDIKATOR (opsional) ----------------------
// Ubah ke -1 bila tidak dipakai.
const int PIN_LED_OK   = 2;    // LED hijau / onboard
const int PIN_LED_FAIL = 4;    // LED merah
const int PIN_BUZZER   = 15;   // buzzer aktif (opsional)

// ---------------------------------------------------------------------------

MFRC522 rfid(SS_PIN, RST_PIN);

String lastUid = "";
unsigned long lastTapAt = 0;

void setup() {
  Serial.begin(115200);
  delay(300);

  if (PIN_LED_OK >= 0)   { pinMode(PIN_LED_OK, OUTPUT);   digitalWrite(PIN_LED_OK, LOW); }
  if (PIN_LED_FAIL >= 0) { pinMode(PIN_LED_FAIL, OUTPUT); digitalWrite(PIN_LED_FAIL, LOW); }
  if (PIN_BUZZER >= 0)   { pinMode(PIN_BUZZER, OUTPUT);   digitalWrite(PIN_BUZZER, LOW); }

  Serial.println();
  Serial.println("=== Absen RFID ESP32 ===");

  SPI.begin();          // SCK=18, MISO=19, MOSI=23
  rfid.PCD_Init();

  byte versi = rfid.PCD_ReadRegister(MFRC522::VersionReg);
  Serial.printf("MFRC522 versi: 0x%02X\n", versi);
  if (versi == 0x00 || versi == 0xFF) {
    Serial.println("PERINGATAN: MFRC522 tidak terdeteksi. Cek wiring & tegangan 3.3V.");
  }

  sambungWifi();
}

void loop() {
  pastikanWifi();

  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String uid = uidKeString(rfid.uid.uidByte, rfid.uid.size);

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  if (uid == "") return;

  // Debounce: kartu sama dalam DEBOUNCE_MS diabaikan.
  if (uid == lastUid && (millis() - lastTapAt) < DEBOUNCE_MS) {
    Serial.println("(tap diabaikan, kartu sama)");
    return;
  }
  lastUid = uid;
  lastTapAt = millis();

  Serial.println("----------------------------------------");
  Serial.println("UID dibaca : " + uid);

  kirimTap(uid);
}

// ---------------------------------------------------------------------------
// WiFi
// ---------------------------------------------------------------------------
void sambungWifi() {
  Serial.print("Menghubungkan WiFi");
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long mulai = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - mulai < 20000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.print("WiFi tersambung. IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println();
    Serial.println("Gagal WiFi. Akan dicoba lagi di loop().");
  }
}

void pastikanWifi() {
  static unsigned long cekTerakhir = 0;
  if (millis() - cekTerakhir < 5000) return;
  cekTerakhir = millis();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi terputus, menyambung ulang...");
    WiFi.disconnect();
    sambungWifi();
  }
}

// ---------------------------------------------------------------------------
// UID -> String
// ---------------------------------------------------------------------------
String uidKeString(byte* uid, byte panjang) {
#if UID_FORMAT_DECIMAL
  // Desimal big-endian: byte pertama = paling signifikan.
  unsigned long long nilai = 0;
  for (byte i = 0; i < panjang; i++) {
    nilai = (nilai << 8) | uid[i];
  }
  char buf[24];
  snprintf(buf, sizeof(buf), "%llu", nilai);
  return String(buf);
#else
  String hasil = "";
  for (byte i = 0; i < panjang; i++) {
    if (uid[i] < 0x10) hasil += "0";
    hasil += String(uid[i], HEX);
  }
  hasil.toUpperCase();
  return hasil;
#endif
}

// ---------------------------------------------------------------------------
// Kirim ke API
// ---------------------------------------------------------------------------
void kirimTap(const String& uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Tidak ada WiFi, tap tidak dikirim.");
    sinyalGagal();
    return;
  }

  String url = String(API_BASE_URL) + "/api/tap";
  String body = "tag=" + uid;

  HTTPClient http;

  // Objek client harus hidup sampai http.end(), jadi dideklarasikan di sini.
  WiFiClientSecure secureClient;
  WiFiClient plainClient;

  if (url.startsWith("https")) {
    secureClient.setInsecure();   // lihat catatan keamanan di bawah
    http.begin(secureClient, url);
  } else {
    http.begin(plainClient, url);
  }

  http.setTimeout(8000);
  http.setReuse(false);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-API-Key", API_KEY);

  int httpCode = http.POST(body);

  if (httpCode <= 0) {
    Serial.printf("Gagal koneksi: %s\n", http.errorToString(httpCode).c_str());
    sinyalGagal();
    http.end();
    return;
  }

  String payload = http.getString();
  Serial.printf("HTTP %d\n", httpCode);
  Serial.println("Respons: " + payload);

  if (httpCode == 401) {
    Serial.println("API KEY SALAH. Cek RFID_API_KEY di server dan konstanta API_KEY.");
    sinyalGagal();
    http.end();
    return;
  }
  if (httpCode == 429) {
    Serial.println("Terlalu banyak request. Tunggu sebentar.");
    sinyalGagal();
    http.end();
    return;
  }
  if (httpCode != 200) {
    Serial.println("Server mengembalikan error.");
    sinyalGagal();
    http.end();
    return;
  }

  JsonDocument doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    Serial.printf("JSON tidak valid: %s\n", err.c_str());
    sinyalGagal();
    http.end();
    return;
  }

  bool ok = doc["ok"] | false;
  const char* kode  = doc["kode"]  | "?";
  const char* pesan = doc["pesan"] | "";
  const char* status = doc["status"] | "";

  Serial.println("========================================");
  Serial.printf("KODE   : %s\n", kode);
  Serial.printf("STATUS : %s\n", status);
  Serial.printf("PESAN  : %s\n", pesan);
  Serial.println("========================================");

  if (ok) {
    sinyalSukses();
  } else {
    sinyalGagal();
  }

  http.end();
}

// ---------------------------------------------------------------------------
// Indikator
// ---------------------------------------------------------------------------
void sinyalSukses() {
  if (PIN_LED_OK >= 0) digitalWrite(PIN_LED_OK, HIGH);
  if (PIN_BUZZER >= 0) {
    tone(PIN_BUZZER, 2000, 120);
  }
  delay(600);
  if (PIN_LED_OK >= 0) digitalWrite(PIN_LED_OK, LOW);
}

void sinyalGagal() {
  if (PIN_LED_FAIL >= 0) digitalWrite(PIN_LED_FAIL, HIGH);
  if (PIN_BUZZER >= 0) {
    tone(PIN_BUZZER, 500, 400);
  }
  delay(800);
  if (PIN_LED_FAIL >= 0) digitalWrite(PIN_LED_FAIL, LOW);
}

/*
 * CATATAN KEAMANAN HTTPS
 * ----------------------
 * client.setInsecure() mematikan verifikasi sertifikat, sehingga rentan
 * terhadap man-in-the-middle (API key bisa dicuri). Untuk produksi:
 *
 *   1. Unduh root CA server (mis. ISRG Root X1 untuk Let's Encrypt).
 *   2. Simpan sebagai string PEM di firmware.
 *   3. Ganti client.setInsecure() menjadi:
 *
 *        client.setCACert(ROOT_CA);
 *
 * Selama masih tahap uji di jaringan sekolah, setInsecure() dapat dipakai.
 */

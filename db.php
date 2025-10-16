<?php
// Session Güvenlik Ayarları (Bunlar en başta kalmalı)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
//ini_set('session.cookie_secure', 1); // Sadece HTTPS varsa açılır

try {
    // Sadece veritabanına bağlanır
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tablo oluşturma kodların aynen devam ediyor
    $pdo->exec("CREATE TABLE IF NOT EXISTS User (id INTEGER PRIMARY KEY, full_name TEXT, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, company_id INTEGER, balance INTEGER DEFAULT 800, created_at TEXT DEFAULT (datetime('now','localtime')), role TEXT DEFAULT 'user' CHECK(role IN ('user', 'comp_admin', 'admin')), FOREIGN KEY(company_id) REFERENCES Bus_Company(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS Bus_Company (id INTEGER PRIMARY KEY, name TEXT NOT NULL, logo_path TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS Coupons (id INTEGER PRIMARY KEY, code TEXT NOT NULL, discount REAL NOT NULL, usage_limit INTEGER NOT NULL, expire_date TEXT NOT NULL, company_id INTEGER, used_count INTEGER DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (company_id) REFERENCES Bus_Company(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS Trips (id INTEGER PRIMARY KEY, company_id INTEGER NOT NULL, departure_city TEXT NOT NULL, destination_city TEXT NOT NULL, arrival_time DATETIME NOT NULL, departure_time DATETIME NOT NULL, price INTEGER NOT NULL, seat_count INTEGER DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (company_id) REFERENCES Bus_Company(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS Tickets (id INTEGER PRIMARY KEY, trip_id INTEGER NOT NULL, user_id INTEGER NOT NULL, seat_number INTEGER NOT NULL, total_price INTEGER NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (trip_id) REFERENCES Trips(id), FOREIGN KEY (user_id) REFERENCES User(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS User_Coupons (id INTEGER PRIMARY KEY, coupon_id INTEGER NOT NULL, user_id INTEGER NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (coupon_id) REFERENCES Coupons(id), FOREIGN KEY (user_id) REFERENCES User(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS Booked_Seats (id INTEGER PRIMARY KEY, ticket_id INTEGER NOT NULL, seat_number INTEGER NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (ticket_id) REFERENCES Tickets(id))");

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Hata");
}
?>

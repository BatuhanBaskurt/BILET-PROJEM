#!/bin/sh
set -e

# Bu script, Docker container'ı her başlatıldığında çalışır.
# Amacı, SQLite veritabanı dosyasına yazma izinlerini ayarlamaktır 
# (Hata: "attempt to write a readonly database" hatasını gidermek için).

# 1. Veritabanı dosyasının adını ve yolunu tanımla. 
# Bu dosyanın, script'in çalıştığı (projenin kök) dizininde olduğunu varsayıyoruz.
DB_FILE="database.db"

# 2. Web sunucusu kullanıcısını bul. 
# Çoğu Debian/Ubuntu tabanlı Docker imajında bu 'www-data'dır.
# Bulunamazsa varsayılan olarak 'root' kullanılır (ama ideal olarak 'www-data' olmalı).
# Ancak bu komut, kullanıcının UID'sini döndürür, bu Docker için daha güvenlidir.
# Eğer Alpine Linux kullanıyorsanız, kullanıcı adı 'www-data' yerine 'nginx' veya başka bir şey olabilir.
# Standart PHP FPM imajlarında www-data geçerlidir.
WEB_USER=$(id -u www-data 2>/dev/null || echo "root")
WEB_GROUP=$(id -g www-data 2>/dev:null || echo "root")

echo "--- Docker Entrypoint Başlatılıyor ---"

# 3. Veritabanı dosyası mevcutsa izinleri ayarla
if [ -f "$DB_FILE" ]; then
    echo "Veritabanı dosyası ($DB_FILE) bulundu. İzinler ayarlanıyor..."
    
    # Sahipliği web sunucusu kullanıcısına ver
    chown $WEB_USER:$WEB_GROUP "$DB_FILE"
    
    # Yazma izni ver (664: Sahip/Grup Okuma/Yazma, Diğerleri Okuma)
    chmod 664 "$DB_FILE"
    
    echo "$DB_FILE dosyasına $WEB_USER kullanıcısı için sahiplik ve izinler başarıyla ayarlandı."
else
    echo "UYARI: $DB_FILE dosyası bulunamadı. Uygulama başlatıldığında veritabanı otomatik oluşacaktır."
fi

# 4. Asıl komutu çalıştır. 
# Bu, genellikle Dockerfile'daki CMD veya Docker Compose'daki command ile belirtilen ana programdır (örneğin: apache2-foreground veya php-fpm).
exec "$@"

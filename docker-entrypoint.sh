#!/bin/sh
set -e

DB_FILE="database.db"
# PHP Apache imajı www-data kullanıcısı ile çalışır.
WEB_USER="www-data"
WEB_GROUP="www-data" 

echo "--- Docker Entrypoint Başlatılıyor ---"

if [ -f "$DB_FILE" ]; then
    echo "Veritabanı dosyası ($DB_FILE) bulundu. Sahiplik ve izinler ayarlanıyor..."
    
    # 🚨 ÖNEMLİ: Volume nedeniyle sahipliği www-data'ya zorla ayarla.
    chown $WEB_USER:$WEB_GROUP "$DB_FILE"
    
    # Yazma iznini www-data kullanıcısına ve grubuna ver (664)
    chmod 664 "$DB_FILE"
    
    echo "$DB_FILE dosyasına $WEB_USER kullanıcısı için izinler başarıyla ayarlandı."
else
    echo "UYARI: $DB_FILE dosyası bulunamadı. Uygulama oluşturacaksa, klasör izni veriliyor..."
    # Eğer database.db yoksa ve uygulama oluşturacaksa, bulunduğu klasöre yazma izni verilir.
    chown $WEB_USER:$WEB_GROUP .
    chmod 775 .
fi

# Asıl komutu çalıştır (Apache'yi başlat)
exec "$@"

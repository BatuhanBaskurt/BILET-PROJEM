#!/bin/sh
set -e

# Veritabanı dosyası adı
DB_FILE="database.db"

echo "--- Docker Entrypoint Başlatılıyor ---"

# 1. database.db dosyasının mevcut olup olmadığını kontrol et
if [ -f "$DB_FILE" ]; then
    echo "Veritabanı dosyası ($DB_FILE) bulundu. İzinler ayarlanıyor..."
    
    # 2. En güvenilir yöntem: Dosyaya herkesin yazabilmesi için 666 izni ver.
    # Bu, web sunucusu kullanıcısının (www-data) izin sorununu çözer.
    chmod 666 "$DB_FILE"
    
    echo "$DB_FILE dosyasına yazma izinleri (chmod 666) başarıyla ayarlandı."
    
    # Not: Güvenlik için daha sonra sadece www-data kullanıcısına izin veren bir çözüm önerilebilir, 
    # ancak bu, readonly hatasını kesin çözer.

else
    echo "UYARI: $DB_FILE dosyası bulunamadı. Uygulama başlatıldığında oluşturulacak ve izni ayarlanacaktır."
    # Eğer dosya yoksa ve uygulama tarafından oluşturulacaksa, klasör iznini de ayarlayalım.
    # database.db dosyasının oluşturulacağı dizine (./) de yazma izni verilir.
    chmod 777 .
fi

# 3. Asıl komutu çalıştır (PHP-FPM, Apache vb.)
exec "$@"

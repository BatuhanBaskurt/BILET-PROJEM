#!/bin/sh

# Bu script, konteyner her başladığında çalışır.

# 1. 'uploads' klasörünün ve 'database.db' dosyasının sahibini
#    web sunucusunu çalıştıran 'www-data' kullanıcısı yap.
#    Bu, PHP'nin bu dosyalara yazma izni olmasını garantiler.
chown www-data:www-data /var/www/html/database.db
chown -R www-data:www-data /var/www/html/uploads

# 2. İzinleri düzelttikten sonra, asıl görevi (Apache'yi başlatmayı) devral.
exec apache2-foreground

#!/bin/sh
# Hata olursa script'i durdur
set -e

# Bu script, konteyner her başladığında çalışır.

# /var/www/html klasöründeki TÜM dosyaların ve klasörlerin
# sahibini web sunucusunu çalıştıran 'www-data' kullanıcısı yap.
# -R parametresi, "recursive" yani içindeki her şey dahil demektir.
chown -R www-data:www-data /var/www/html

# İzinleri düzelttikten sonra, Dockerfile'da belirtilen asıl komutu çalıştır.
exec "$@"

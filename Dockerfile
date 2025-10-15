# Resmi PHP-Apache imajını temel alıyoruz. PHP 8.1 ve Apache sunucusu hazır geliyor.
FROM php:8.1-apache

# Gerekli PHP eklentileri için bağımlılıkları kuruyoruz.
# SQLite ve cURL (TCPDF için) lazım olacak.
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentilerini aktif ediyoruz.
RUN docker-php-ext-install pdo_sqlite curl

# Proje dosyalarını imajın içine kopyalıyoruz.
# '.' demek, bu klasördeki her şeyi kopyala demek.
COPY . /var/www/html/

# 'uploads' klasörünün ve veritabanı dosyasının web sunucusu tarafından yazılabilir olmasını sağlıyoruz.
# Bu olmazsa logo yükleyemezsin veya veritabanına kayıt yapamazsın.
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chown www-data:www-data /var/www/html/database.db

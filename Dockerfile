# Resmi PHP-Apache imajını temel alıyoruz.
FROM php:8.1-apache

# Gerekli PHP eklentileri için bağımlılıkları kuruyoruz.
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentilerini aktif ediyoruz.
RUN docker-php-ext-install pdo_sqlite curl

# Proje dosyalarını imajın içine kopyalıyoruz.
COPY . /var/www/html/

# Otomatik izin script'ini konteynerin içine kopyala
COPY docker-entrypoint.sh /usr/local/bin/

# O script'i çalıştırılabilir yap
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Konteyner başladığında bu script'i çalıştır
ENTRYPOINT ["docker-entrypoint.sh"]

# ENTRYPOINT'ten sonra çalıştırılacak varsayılan komutu belirt
CMD ["apache2-foreground"]

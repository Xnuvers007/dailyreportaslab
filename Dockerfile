# ==============================================================================
# DOCKERFILE - SISTEM LAPORAN HARIAN ASISTEN LABORATORIUM (UNPAM)
# PHP 8.2 with Apache & Hardened Private Storage
# ==============================================================================

FROM php:8.2-apache

# Update package repository dan install dependensi sistem yang dibutuhkan
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_sqlite fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan Apache modules
RUN a2enmod rewrite headers

# Konfigurasi Apache ServerName dan AllowOverride
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Buat direktori penyimpanan foto privat DI LUAR DOCUMENT ROOT (/var/www/storage/uploads)
# Ini memastikan file foto secara fisik tidak dapat diakses langsung oleh publik/web server
RUN mkdir -p /var/www/storage/uploads /var/www/html \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 775 /var/www/storage

# Set working directory ke document root
WORKDIR /var/www/html

# Salin source code aplikasi
COPY . /var/www/html/

# Pastikan kepemilikan file dimiliki oleh user web server (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Set default Environment Variables
ENV UPLOADS_DIR=/var/www/storage/uploads \
    DB_HOST=db \
    DB_PORT=3306 \
    DB_NAME=laporan_aslab \
    DB_USER=aslab_user \
    DB_PASS=aslab_secret123

# Expose port HTTP
EXPOSE 80

# Jalankan Apache di foreground
CMD ["apache2-foreground"]

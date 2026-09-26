# استخدام نسخة PHP الرسمية مع خادم Apache
FROM php:8.2-apache

# إجبار السيرفر على الاستماع للمنفذ 80 بشكل داخلي صارم
ENV PORT=80
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# نسخ ملفات مشروعك
COPY . /var/www/html/

# فتح المنفذ للخارج
EXPOSE 80

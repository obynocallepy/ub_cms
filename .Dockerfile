# Use the official PHP image
FROM php:8.0-apache

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
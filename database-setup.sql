# MySQL Database Setup Commands

# Secure MySQL installation
mysql_secure_installation

# Connect to MySQL as root
mysql -u root -p

# Create database and user
CREATE DATABASE whatsmark_db;
CREATE USER 'whatsmark_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON whatsmark_db.* TO 'whatsmark_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
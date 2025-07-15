CREATE USER 'pbwuser'@'localhost' IDENTIFIED BY 'passwordku';
GRANT ALL PRIVILEGES ON *.* TO 'pbwuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;

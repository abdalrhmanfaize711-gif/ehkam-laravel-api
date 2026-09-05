-- Run as MySQL root/admin, not as the Ehkam application user.
-- Replace CHANGE_ME and database names before use.
-- Keep this file out of public web roots.

CREATE USER IF NOT EXISTS 'ehkam_app'@'%' IDENTIFIED BY 'CHANGE_ME';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
ON `ehkam`.* TO 'ehkam_app'@'%';
FLUSH PRIVILEGES;

-- If migrations are executed by a separate deployment user, use that user for
-- migrations and remove CREATE/ALTER/INDEX/REFERENCES from ehkam_app afterwards.

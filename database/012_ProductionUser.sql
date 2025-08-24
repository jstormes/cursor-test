-- Production Database User Setup
-- Version: 1.0
-- Description: Creates a production user with limited privileges for security
-- Generated: 2025-01-24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Create production user with secure credentials
-- --------------------------------------------------------

-- Create production user
CREATE USER IF NOT EXISTS 'app_prod'@'%' IDENTIFIED BY 'prod_secure_2025!';

-- Grant only necessary privileges to production user
-- Read/Write access to the app database only
GRANT SELECT, INSERT, UPDATE, DELETE ON `app`.* TO 'app_prod'@'%';

-- Allow the production user to create temporary tables for complex queries
GRANT CREATE TEMPORARY TABLES ON `app`.* TO 'app_prod'@'%';

-- Apply the privilege changes
FLUSH PRIVILEGES;

-- --------------------------------------------------------
-- Security notes and recommendations
-- --------------------------------------------------------

/*
PRODUCTION USER SECURITY FEATURES:

1. **Limited Privileges**: Only SELECT, INSERT, UPDATE, DELETE on app database
   - No CREATE, DROP, ALTER privileges
   - No administrative privileges
   - No access to other databases

2. **Secure Password**: Complex password with special characters
   - Should be changed in production environment
   - Consider using environment-specific passwords

3. **Host Restriction**: User can connect from any host (%)
   - In production, consider restricting to specific hosts
   - Example: CREATE USER 'app_prod'@'application-server-ip'

4. **Database Scope**: Access limited to 'app' database only
   - Cannot access mysql, information_schema, or other system databases
   - Prevents privilege escalation

5. **Temporary Tables**: Allows complex query operations
   - Needed for some application operations
   - Tables are automatically dropped on disconnect

PRODUCTION DEPLOYMENT RECOMMENDATIONS:

1. Change the password to environment-specific secure value
2. Restrict host access to application servers only
3. Use SSL connections in production
4. Monitor database access logs
5. Regularly rotate passwords
6. Consider using certificate-based authentication

Example production user creation with host restriction:
CREATE USER 'app_prod'@'10.0.1.100' IDENTIFIED BY 'environment_specific_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON `app`.* TO 'app_prod'@'10.0.1.100';
*/

COMMIT;
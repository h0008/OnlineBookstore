<?php
/**
 * Database Connection File
 * Uses environment-specific connection details
 */

// Check if we're in production environment
if (getenv('RENDER') == 'true') {
    // We're on Render, use production connection
    require_once 'connect_prod.php';
} else {
    // We're in development, use local connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "OnlineBookstore";

    // Create connection function
    function getConnection() {
        global $servername, $username, $password, $dbname;
        
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    }

    // For direct inclusion where a connection is immediately needed
    $conn = getConnection();
}
?>
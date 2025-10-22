<?php
/**
 * Helper functions for database operations compatible with GLPI 11
 */

if (!function_exists('ta_db_query')) {
    /**
     * Execute a query using GLPI 11 compatible method
     * 
     * @param string $query SQL query
     * @return mysqli_result|false
     */
    function ta_db_query($query) {
        global $DB;
        return $DB->doQuery($query);
    }
}

if (!function_exists('ta_db_fetch_assoc')) {
    /**
     * Fetch associative array from result
     * 
     * @param mysqli_result $result
     * @return array|null
     */
    function ta_db_fetch_assoc($result) {
        if ($result === false) {
            return null;
        }
        return $result->fetch_assoc();
    }
}

if (!function_exists('ta_db_num_rows')) {
    /**
     * Get number of rows in result
     * 
     * @param mysqli_result $result
     * @return int
     */
    function ta_db_num_rows($result) {
        if ($result === false) {
            return 0;
        }
        return $result->num_rows;
    }
}

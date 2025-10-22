<?php
/**
 * ---------------------------------------------------------------------
 * Ticket Answers - GLPI Plugin
 * Copyright (C) 2023-2025 by Jeferson Penna Alves
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of Ticket Answers.
 * Ticket Answers is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

class PluginTicketanswersDebug {
    
    /**
     * Log a message to the plugin log file
     * 
     * @param mixed $message Message to log (string, array, or object)
     * @param string $level Log level (INFO, WARNING, ERROR, DEBUG)
     * @return void
     */
    static function log($message, $level = 'INFO') {
        // Usar o sistema de log do GLPI 11
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }
        
        $log_message = "[$level] $message";
        Toolbox::logInFile('ticketanswers', $log_message . "\n");
    }
    
    /**
     * Log an error message
     * 
     * @param mixed $message Error message
     * @return void
     */
    static function error($message) {
        self::log($message, 'ERROR');
    }
    
    /**
     * Log a warning message
     * 
     * @param mixed $message Warning message
     * @return void
     */
    static function warning($message) {
        self::log($message, 'WARNING');
    }
    
    /**
     * Log a debug message
     * 
     * @param mixed $message Debug message
     * @return void
     */
    static function debug($message) {
        if (defined('GLPI_DEBUG') && GLPI_DEBUG) {
            self::log($message, 'DEBUG');
        }
    }
}

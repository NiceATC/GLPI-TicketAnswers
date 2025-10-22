<?php
// Não incluir includes.php aqui, pois será chamado pelo install.php

// Adicionar coluna ticket_id se não existir
if (!$DB->fieldExists('glpi_plugin_ticketanswers_views', 'ticket_id')) {
    $DB->doQuery("ALTER TABLE `glpi_plugin_ticketanswers_views` 
                ADD COLUMN `ticket_id` VARCHAR(255) NOT NULL AFTER `users_id`,
                ADD INDEX `ticket_id` (`ticket_id`)");
    
    // Log da alteração
    error_log("Adicionada coluna ticket_id à tabela glpi_plugin_ticketanswers_views");
}

// Modificar a coluna followup_id para permitir NULL se necessário
$query = "SHOW COLUMNS FROM `glpi_plugin_ticketanswers_views` LIKE 'followup_id'";
$result = $DB->doQuery($query);
if ($result && $result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    // Verificar se a coluna já permite NULL
    if (strpos(strtoupper($column_info['Null']), 'NO') !== false) {
        $DB->doQuery("ALTER TABLE `glpi_plugin_ticketanswers_views` 
                    MODIFY COLUMN `followup_id` VARCHAR(255) NOT NULL");
        
        // Log da alteração
        error_log("Modificada coluna followup_id para permitir NULL");
    }
}

// Adicionar coluna message_id se não existir
if (!$DB->fieldExists('glpi_plugin_ticketanswers_views', 'message_id')) {
    $DB->doQuery("ALTER TABLE `glpi_plugin_ticketanswers_views` 
                ADD COLUMN `message_id` VARCHAR(255) DEFAULT NULL AFTER `followup_id`");
    
    // Log da alteração
    error_log("Adicionada coluna message_id à tabela glpi_plugin_ticketanswers_views");
}

// Garantir que os tipos de dados estejam corretos
$DB->doQuery("ALTER TABLE `glpi_plugin_ticketanswers_views` 
            MODIFY `ticket_id` VARCHAR(255) NOT NULL,
            MODIFY `followup_id` VARCHAR(255) NOT NULL");

// Log da alteração
error_log("Atualizados tipos de dados para BIGINT");

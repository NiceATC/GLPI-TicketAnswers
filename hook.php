<?php
/**
 * Hook file for Ticket Answers plugin
 */

function plugin_ticketanswers_install() {
    /** @var DBmysql $DB */
    global $DB;
    
    // Criar a tabela de visualizações se não existir
    if (!$DB->tableExists('glpi_plugin_ticketanswers_views')) {
        $table = 'glpi_plugin_ticketanswers_views';
        
        $DB->doQuery("CREATE TABLE IF NOT EXISTS `$table` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `ticket_id` VARCHAR(255) NOT NULL,
            `users_id` int unsigned NOT NULL,
            `followup_id` VARCHAR(255) NOT NULL,
            `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `message_id` VARCHAR(20) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_view` (`users_id`, `ticket_id`, `followup_id`),
            KEY `idx_users_id` (`users_id`),
            KEY `idx_ticket_id` (`ticket_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    }
    
    // Instalar direitos de perfil
    PluginTicketanswersProfile::install();
    
    return true;
}

function plugin_ticketanswers_update($current_version) {
    /** @var DBmysql $DB */
    global $DB;
    
    $migration = new Migration(PLUGIN_TICKETANSWERS_VERSION);
    $table = 'glpi_plugin_ticketanswers_views';
    
    // Se estiver atualizando de uma versão anterior à 1.1.1
    if (version_compare($current_version, '1.1.1', '<')) {
        // Verificar se a coluna existe e alterar seu tipo
        if ($DB->fieldExists($table, 'message_id')) {
            $migration->changeField($table, 'message_id', 'message_id', 'varchar(20)');
            $migration->migrationOneTable($table);
            
            // Registrar a alteração no log
            Toolbox::logInFile('plugin_ticketanswers', "Coluna message_id alterada para VARCHAR(20)\n");
        }
    }
    
    // Atualizar para versão 2.0.0 (GLPI 11)
    if (version_compare($current_version, '2.0.0', '<')) {
        // Adicionar índices se não existirem
        if ($DB->tableExists($table)) {
            // Verificar e adicionar índice users_id
            if (!$DB->indexExists($table, 'idx_users_id')) {
                $migration->addKey($table, 'users_id', 'idx_users_id');
            }
            
            // Verificar e adicionar índice ticket_id
            if (!$DB->indexExists($table, 'idx_ticket_id')) {
                $migration->addKey($table, 'ticket_id', 'idx_ticket_id');
            }
            
            // Executar migração
            $migration->migrationOneTable($table);
        }
        
        // Atualizar direitos de perfil
        PluginTicketanswersProfile::install();
        
        Toolbox::logInFile('plugin_ticketanswers', "Plugin atualizado para versão 2.0.0 (GLPI 11)\n");
    }
    
    return true;
}

function plugin_ticketanswers_uninstall() {
    /** @var DBmysql $DB */
    global $DB;
    
    // Remover direitos de perfil
    PluginTicketanswersProfile::uninstall();
    
    // Remover tabela
    if ($DB->tableExists("glpi_plugin_ticketanswers_views")) {
       $DB->doQuery("DROP TABLE `glpi_plugin_ticketanswers_views`");
    }
    
    return true;
}

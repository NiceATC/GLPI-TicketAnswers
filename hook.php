<?php
/**
 * Hook file for Ticket Answers plugin
 */

function plugin_ticketanswers_install() {
    global $DB;
    
    // Criar a tabela de visualizações se não existir
if (!$DB->tableExists('glpi_plugin_ticketanswers_views')) {
   $query = "CREATE TABLE `glpi_plugin_ticketanswers_views` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `ticket_id` VARCHAR(255) NOT NULL,
       `users_id` int(11) unsigned NOT NULL,
       `followup_id` VARCHAR(255) NOT NULL,
       `viewed_at` timestamp NOT NULL,
       `message_id` VARCHAR(255) DEFAULT NULL,
       PRIMARY KEY (`id`),
       UNIQUE KEY `unique_view` (`users_id`, `ticket_id`, `followup_id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
   
   $DB->query($query);
   
   if ($DB->error()) {
       error_log("Erro ao criar tabela glpi_plugin_ticketanswers_views: " . $DB->error());
       return false;
   }
}

    
    // Resto do código de instalação...
    
    return true;
}
function plugin_ticketanswers_update($old_version) {
   global $DB;
    
   // Se estiver atualizando de uma versão anterior à 1.1.1
   if (version_compare($old_version, '1.1.1', '<')) {
       // Verificar se a coluna existe e alterar seu tipo
       if ($DB->fieldExists('glpi_plugin_ticketanswers_views', 'message_id')) {
           $DB->query("ALTER TABLE `glpi_plugin_ticketanswers_views` 
                       MODIFY COLUMN `message_id` VARCHAR(20) DEFAULT NULL");
            
           // Registrar a alteração no log
           error_log("Plugin TicketAnswers: Coluna message_id alterada para VARCHAR(20)");
       }
   }
    
   return true;
}

function plugin_ticketanswers_uninstall() {
   global $DB;
    
   if ($DB->tableExists("glpi_plugin_ticketanswers_views")) {
       $query = "DROP TABLE `glpi_plugin_ticketanswers_views`";
       $DB->query($query) or die("Error dropping glpi_plugin_ticketanswers_views " . $DB->error());
   }
    
   return true;
}

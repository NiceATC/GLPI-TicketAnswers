<?php
/*
  -------------------------------------------------------------------------
  Ticket Answers
  Copyright (C) 2023 by Jeferson Penna Alves
  -------------------------------------------------------------------------
  LICENSE
  This file is part of Ticket Answers.
  Ticket Answers is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.
  Ticket Answers is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with Ticket Answers. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
*/

/**
 * Função de instalação do plugin
 */
function plugin_ticketanswers_install() {
    global $DB;
    
    // Criar tabela de visualizações se não existir
    if (!$DB->tableExists('glpi_plugin_ticketanswers_views')) {
        $query = "CREATE TABLE `glpi_plugin_ticketanswers_views` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ticket_id` INT(11) NULL DEFAULT NULL,
            `users_id` int(11) NOT NULL,
            `followup_id` int(11) NULL DEFAULT NULL,
            `viewed_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`),
            KEY `followup_id` (`followup_id`),
            KEY `ticket_id` (`ticket_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        
        $DB->doQuery($query) or die("Error creating glpi_plugin_ticketanswers_views table: " . $DB->error());
    } else {
        // Atualizar esquema se a tabela já existir
        include_once(__DIR__ . '/install/update_schema.php');
    }
    
    return true;
}

/**
 * Função de desinstalação do plugin
 */
function plugin_ticketanswers_uninstall() {
    global $DB;
    
    // Remover tabela de visualizações
    if ($DB->tableExists('glpi_plugin_ticketanswers_views')) {
        $query = "DROP TABLE `glpi_plugin_ticketanswers_views`";
        $DB->doQuery($query) or die("Error dropping glpi_plugin_ticketanswers_views table");
    }
    
    return true;
}

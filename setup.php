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

define('PLUGIN_TICKETANSWERS_VERSION', '1.1.0');

/**
  * Inicialização do plugin
  */
function plugin_init_ticketanswers() {
    global $PLUGIN_HOOKS;
   
    $PLUGIN_HOOKS['csrf_compliant']['ticketanswers'] = true;
   
    if (Session::getLoginUserID()) {
        // Opção 1: Usando array para múltiplos scripts
        $PLUGIN_HOOKS['add_javascript']['ticketanswers'] = [
            'js/unified_notifications.js',
            'js/notification_bell.js',
            'js/fix_layout.js'
        ];
        
        // OU Opção 2: Usando string para um único script principal
        // $PLUGIN_HOOKS['add_javascript']['ticketanswers'] = 'js/unified_notifications.js';
        
        $PLUGIN_HOOKS['add_css']['ticketanswers'][] = 'css/self_service_fixes.css';
        
        Plugin::registerClass('PluginTicketanswersProfile', ['addtabon' => 'Profile']);
        $PLUGIN_HOOKS['menu_toadd']['ticketanswers'] = ['plugins' => 'PluginTicketanswersMenu'];
    }
}
/**
  * Informações do plugin
  */
function plugin_version_ticketanswers() {
    return [
       'name'           => 'Ticket Answers',
       'version'        => PLUGIN_TICKETANSWERS_VERSION,
       'author'         => 'Jeferson Penna Alves',
       'license'        => 'GPLv2+',
       'homepage'       => 'https://github.com/jefersonalves/ticketanswers',
       'minGlpiVersion' => '9.5'
    ];
}

/**
  * Verificação de requisitos
  */
function plugin_ticketanswers_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '9.5', 'lt')) {
       return false;
    }
    return true;
}

/**
  * Verificação de configuração
  */
function plugin_ticketanswers_check_config() {
    return true;
}

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

define('PLUGIN_TICKETANSWERS_VERSION', '2.0.1');

// Definir diretório raiz do plugin
define('PLUGIN_TICKETANSWERS_DIR', Plugin::getPhpDir('ticketanswers'));
define('PLUGIN_TICKETANSWERS_WEB_DIR', Plugin::getWebDir('ticketanswers'));

/**
  * Inicialização do plugin
  */
function plugin_init_ticketanswers() {
    global $PLUGIN_HOOKS;
   
    $PLUGIN_HOOKS['csrf_compliant']['ticketanswers'] = true;
    
    // Registrar as classes do plugin
    Plugin::registerClass('PluginTicketanswersProfile', ['addtabon' => 'Profile']);
   
    if (Session::getLoginUserID()) {
        // Adicionar menu ao GLPI
        $PLUGIN_HOOKS['menu_toadd']['ticketanswers'] = ['plugins' => 'PluginTicketanswersMenu'];
        
        // JS e CSS serão carregados apenas nas páginas do plugin
        // para evitar conflitos com o GLPI core
    }
}
/**
  * Informações do plugin
  */
function plugin_version_ticketanswers() {
    return [
       'name'           => 'Respostas de Tickets e Notificações',
       'version'        => PLUGIN_TICKETANSWERS_VERSION,
       'author'         => 'Jeferson Penna Alves',
       'license'        => 'GPLv2+',
       'homepage'       => 'https://github.com/jefersonalves/ticketanswers',
       'requirements'   => [
           'glpi' => [
               'min' => '11.0',
               'dev' => false
           ]
       ]
    ];
}

/**
  * Verificação de requisitos
  */
function plugin_ticketanswers_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '11.0', 'lt')) {
       echo "Este plugin requer GLPI >= 11.0";
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

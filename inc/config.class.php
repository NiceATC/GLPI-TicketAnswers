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

class PluginTicketanswersConfig extends CommonDBTM {
    
   static protected $notable = true;
   
   /**
    * @see CommonGLPI::getMenuName()
   **/
   static function getMenuName() {
      return 'Ticket Answers';
   }
   
   /**
    *  @see CommonGLPI::getMenuContent()
   **/
   static function getMenuContent() {
      $menu = [];

      $menu['title'] = 'Ticket Answers';
      $menu['page']  = Plugin::getWebDir('ticketanswers', false) . '/front/index.php';
      $menu['icon']  = 'ti ti-bell'; // Ícone compatível com GLPI 11
      
      return $menu;
   }
}

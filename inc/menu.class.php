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

class PluginTicketanswersMenu extends CommonGLPI {
   
   static $rightname = 'plugin_ticketanswers';

   static function getMenuName() {
      return "Ticket Answers";
   }

   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = Plugin::getWebDir('ticketanswers', false) . '/front/index.php';
      $menu['icon']  = 'ti ti-bell'; // Ícone compatível com GLPI 11 (Tabler Icons)
      
      // Adicionar submenus se necessário
      // $menu['options'] = [
      //    'stats' => [
      //       'title' => __('Estatísticas', 'ticketanswers'),
      //       'page'  => Plugin::getWebDir('ticketanswers', false) . '/front/stats.php',
      //       'icon'  => 'ti ti-chart-bar',
      //    ],
      // ];
      
      return $menu;
   }
   
   // Adicione estes métodos para a funcionalidade de aba em tickets
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Ticket') {
         return 'Ticket Answers';
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Ticket') {
         // Código para exibir o conteúdo da aba
         echo "<div class='center'>";
         echo "<h3>Ticket Answers</h3>";
         // Seu código aqui
         echo "</div>";
      }
      return true;
   }
}

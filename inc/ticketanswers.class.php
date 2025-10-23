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

class PluginTicketanswersTicketanswers extends CommonGLPI {
   
   static $rightname = 'plugin_ticketanswers';

   static function getTypeName($nb = 0) {
      return __('Ticket Answers', 'ticketanswers');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Ticket') {
         return self::getTypeName();
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Ticket') {
         // Código para exibir o conteúdo da aba
         echo "<div class='center'>";
         echo "<h3>" . __('Ticket Answers', 'ticketanswers') . "</h3>";
         // Seu código aqui
         echo "</div>";
      }
      return true;
   }
   
   function getForbiddenStandardMassiveAction() {
      return [];
   }
   
   static function getMenuShorcut() {
      return 't';
   }
   
   // Este método é crucial para o menu principal
   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getTypeName(2);
      $menu['page']  = Plugin::getWebDir('ticketanswers', false) . '/front/index.php';
      $menu['icon']  = 'ti ti-bell'; // Ícone compatível com GLPI 11 (Tabler Icons)
      
      // Adicionar submenus se necessário
      // $menu['options'] = [
      //    'stats' => [
      //       'title' => __('Statistics', 'ticketanswers'),
      //       'page'  => Plugin::getWebDir('ticketanswers', false) . '/front/stats.php',
      //       'icon'  => 'ti ti-chart-bar',
      //    ],
      // ];
      
      return $menu;
  }
}

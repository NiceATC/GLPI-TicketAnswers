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

class PluginTicketanswersProfile extends Profile {
   
   static $rightname = 'plugin_ticketanswers';

   static function getTypeName($nb = 0) {
      return 'Ticket Answers';
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         return self::getTypeName();
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         $profile = new self();
         $profile->showForm($item->getID());
      }
      return true;
   }

   function showForm($profiles_id = 0, $openform = true, $closeform = true) {
      echo "<div class='firstbloc'>";
      
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $rights = [
         [
            'itemtype'  => 'PluginTicketanswersTicketanswers',
            'label'     => 'Ticket Answers',
            'field'     => 'plugin_ticketanswers',
            'rights'    => [READ => 'Read']
         ]
      ];
      
      $profile->displayRightsChoiceMatrix($rights, [
         'canedit'       => $canedit,
         'default_class' => 'tab_bg_2'
      ]);

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }

   static function install() {
      global $DB;
      
      $default_rights = READ;
      
      // Adicionar permissão padrão para perfis existentes
      foreach ($DB->request('glpi_profiles') as $profile) {
         $profileRight = new ProfileRight();
         if (!$profileRight->getFromDBByCrit([
            'profiles_id' => $profile['id'],
            'name'        => 'plugin_ticketanswers'
         ])) {
            $profileRight->add([
               'profiles_id' => $profile['id'],
               'name'        => 'plugin_ticketanswers',
               'rights'      => $default_rights
            ]);
         }
      }
      
      return true;
   }

   static function uninstall() {
      global $DB;
      
      $profileRight = new ProfileRight();
      $profileRight->deleteByCriteria(['name' => 'plugin_ticketanswers'], 1);
      
      return true;
   }
}

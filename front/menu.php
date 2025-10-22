<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

Html::header("Ticket Answers", $_SERVER['PHP_SELF'], "plugins", "PluginTicketanswers");

echo "<div class='center'>";
echo "<h1>Ticket Answers</h1>";
echo "<p>Bem-vindo ao plugin Ticket Answers</p>";
echo "</div>";

Html::footer();

<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

Html::header("Ticket Answers", $_SERVER['PHP_SELF'], "plugins", "pluginticketanswersmenu", "stats");

echo "<div class='center'>";
echo "<h1>" . __("Estatísticas do Ticket Answers", "ticketanswers") . "</h1>";

// Obter estatísticas
$users_id = Session::getLoginUserID();

// Total de notificações recebidas
$query = "SELECT COUNT(*) as total FROM glpi_plugin_ticketanswers_views WHERE users_id = $users_id";
$result = $DB->query($query);
$total_views = 0;
if ($result && $DB->numrows($result) > 0) {
    $data = $DB->fetchAssoc($result);
    $total_views = $data['total'];
}

// Notificações por período
$query = "SELECT 
    DATE(viewed_at) as view_date,
    COUNT(*) as count
FROM 
    glpi_plugin_ticketanswers_views
WHERE 
    users_id = $users_id
GROUP BY 
    DATE(viewed_at)
ORDER BY 
    view_date DESC
LIMIT 10";

$result = $DB->query($query);
$daily_stats = [];
if ($result) {
    while ($data = $DB->fetchAssoc($result)) {
        $daily_stats[] = $data;
    }
}

// Exibir estatísticas
echo "<div class='tab_cadre_fixe'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __("Resumo de Notificações", "ticketanswers") . "</th></tr>";
echo "<tr><td>" . __("Total de notificações visualizadas", "ticketanswers") . "</td><td>$total_views</td></tr>";
echo "</table>";
echo "</div>";

// Exibir estatísticas diárias
if (count($daily_stats) > 0) {
    echo "<div class='tab_cadre_fixe'>";
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>" . __("Notificações por dia", "ticketanswers") . "</th></tr>";
    echo "<tr><th>" . __("Data", "ticketanswers") . "</th><th>" . __("Quantidade", "ticketanswers") . "</th></tr>";
    
    foreach ($daily_stats as $stat) {
        echo "<tr><td>" . $stat['view_date'] . "</td><td>" . $stat['count'] . "</td></tr>";
    }
    
    echo "</table>";
    echo "</div>";
}

echo "</div>";

Html::footer();

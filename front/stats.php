<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

Html::header("Ticket Answers", $_SERVER['PHP_SELF'], "plugins", "pluginticketanswersmenu", "stats");

echo "<div class='center'>";
echo "<h1>Estatísticas do Ticket Answers</h1>";

// Obter estatísticas
$users_id = Session::getLoginUserID();
global $DB;

// Total de notificações recebidas
$query = "SELECT COUNT(*) as total FROM glpi_plugin_ticketanswers_views WHERE users_id = $users_id";
$result = $DB->doQuery($query);
$total_views = 0;
if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
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

$result = $DB->doQuery($query);
$daily_stats = [];
if ($result) {
    while ($data = $result->fetch_assoc()) {
        $daily_stats[] = $data;
    }
}

// Exibir estatísticas
echo "<div class='tab_cadre_fixe'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>Resumo de Notificações</th></tr>";
echo "<tr><td>Total de notificações visualizadas</td><td>$total_views</td></tr>";
echo "</table>";
echo "</div>";

// Exibir estatísticas diárias
if (count($daily_stats) > 0) {
    echo "<div class='tab_cadre_fixe'>";
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr><th colspan='2'>Notificações por dia</th></tr>";
    echo "<tr><th>Data</th><th>Quantidade</th></tr>";
    
    foreach ($daily_stats as $stat) {
        echo "<tr><td>" . $stat['view_date'] . "</td><td>" . $stat['count'] . "</td></tr>";
    }
    
    echo "</table>";
    echo "</div>";
}

echo "</div>";

Html::footer();

<?php
/**
 * Teste simples do endpoint de notificações
 */

include ("../../../inc/includes.php");

Session::checkLoginUser();

echo "<h1>Teste de Notificações</h1>";
echo "<p>Usuário logado: " . Session::getLoginUserID() . "</p>";

// Chamar o endpoint
$url = GLPI_ROOT . '/plugins/ticketanswers/ajax/get_notifications_modal.php';
echo "<h2>Chamando endpoint...</h2>";

// Simular a chamada
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

ob_start();
include('get_notifications_modal.php');
$output = ob_get_clean();

echo "<h2>Resposta do Endpoint:</h2>";
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

echo "<h2>JSON Decodificado:</h2>";
echo "<pre>";
$decoded = json_decode($output, true);
print_r($decoded);
echo "</pre>";

if (isset($decoded['notifications'])) {
    echo "<h3>Tipo de 'notifications': " . gettype($decoded['notifications']) . "</h3>";
    echo "<h3>É array? " . (is_array($decoded['notifications']) ? 'SIM' : 'NÃO') . "</h3>";
    echo "<h3>Quantidade: " . count($decoded['notifications']) . "</h3>";
}

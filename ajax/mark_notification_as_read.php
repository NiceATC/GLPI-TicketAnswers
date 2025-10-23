<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

global $DB;

// Verificar se os parâmetros necessários foram fornecidos
$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
$followup_id = isset($_GET['followup_id']) ? intval($_GET['followup_id']) : 0;
$notification_type = isset($_GET['type']) ? $DB->escape($_GET['type']) : 'followup';
$message_id = isset($_GET['message_id']) ? intval($_GET['message_id']) : null;
$users_id = Session::getLoginUserID();
$success = false;

error_log("Marcando notificação como lida: tipo=$notification_type, ticket=$ticket_id, followup=$followup_id, user=$users_id, message_id=$message_id");

if ($ticket_id > 0) {
    global $DB;
    
    // Determinar o valor de followup_id com base no tipo de notificação
    $actual_followup_id = $followup_id;
    
    if ($notification_type === 'group') {
        // Para notificações de grupo, usamos um valor positivo baseado no ID do ticket
        $actual_followup_id = $ticket_id + 10000000;
    } else if ($notification_type === 'observer' || $notification_type === 'group_observer') {
        $actual_followup_id = $ticket_id + 20000000;
    } else if ($notification_type === 'assigned') {
        $actual_followup_id = $ticket_id + 30000000;
    } else if ($notification_type === 'validation') {
        // Para notificações de validação, usar o ID real da validação
        $actual_followup_id = intval($followup_id);
    } else if ($notification_type === 'status_change') {
        // Para notificações de status, usar formato de string específico
        $actual_followup_id = "status_{$ticket_id}_" . ($followup_id > 0 ? $followup_id : "any");
        error_log("DEBUG: Usando formato padronizado para status_change: $actual_followup_id");
    } else if ($notification_type === 'pending_reason') {
        // Para motivos de pendência, criar um ID específico
        $actual_followup_id = $ticket_id + 50000000;
    } else if ($notification_type === 'technician_response') {
        // Para respostas de técnicos, usar o ID do followup diretamente
        $actual_followup_id = intval($followup_id);
    } else {
        // Para outros tipos, garantir que o followup_id seja tratado como número
        $actual_followup_id = intval($followup_id);
    }
    
    // Adicionar log para depuração detalhada
    error_log("DEBUG: Após cálculo - notification_type=$notification_type, ticket_id=$ticket_id, original_followup_id=$followup_id, actual_followup_id=$actual_followup_id");
    
    // Para notificações de status, tratamento especial com aspas
    if ($notification_type === 'status_change') {
        try {
            $current_datetime = date('Y-m-d H:i:s');
            
            // Preparar o valor do message_id para a query
            $message_id_value = $message_id ? "'" . $DB->escape($message_id) . "'" : "NULL";
            
            // Construir a query com aspas para o formato de string do status_change
            $query = "INSERT INTO glpi_plugin_ticketanswers_views
                     (ticket_id, users_id, followup_id, viewed_at, message_id)
                     VALUES ('$ticket_id', $users_id, '$actual_followup_id', '$current_datetime', $message_id_value)
                     ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
            
            error_log("Executando query para status_change: $query");
            
            // Executar a query
            $insertResult = $DB->doQuery($query);
            
            if ($insertResult) {
                error_log("Registro de status_change inserido ou atualizado com sucesso");
                
                // Tentar também o formato alternativo
                $alternative_id = $ticket_id + 40000000;
                $query = "INSERT INTO glpi_plugin_ticketanswers_views
                         (ticket_id, users_id, followup_id, viewed_at, message_id)
                         VALUES ('$ticket_id', $users_id, '$alternative_id', '$current_datetime', $message_id_value)
                         ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
                         
                $DB->doQuery($query); // Executar mas não verificar resultado, é apenas uma tentativa adicional
                
                $success = true;
            } else {
                $error = $DB->error();
                error_log("Erro ao inserir registro de status_change: " . $error);
                $success = false;
            }
        } catch (Exception $e) {
            error_log("Exceção ao processar notificação de status: " . $e->getMessage());
            $success = false;
        }
    } else {
        // Para outros tipos de notificação, usar o código original
        try {
            // Preparar a data atual
            $current_datetime = date('Y-m-d H:i:s');
            
            // Preparar o valor do message_id para a query
            $message_id_value = $message_id ? "'" . $DB->escape($message_id) . "'" : "NULL";
            
            // Construir a query - sem alteração para tipos não-status
            $query = "INSERT INTO glpi_plugin_ticketanswers_views
                     (ticket_id, users_id, followup_id, viewed_at, message_id)
                     VALUES ($ticket_id, $users_id, $actual_followup_id, '$current_datetime', $message_id_value)
                     ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
            
            error_log("Executando query: $query");
            
            // Executar a query
            $insertResult = $DB->doQuery($query);
            
            if ($insertResult) {
                error_log("Registro inserido ou atualizado com sucesso");
                $success = true;
            } else {
                $error = $DB->error();
                error_log("Erro ao inserir/atualizar registro: " . $error);
                $success = false;
            }
        } catch (Exception $e) {
            error_log("Exceção ao inserir/atualizar registro: " . $e->getMessage());
            $success = false;
        }
    }
}

// Função auxiliar para marcar notificação como lida
function markNotificationAsRead($DB, $ticket_id, $users_id, $followup_id, $current_datetime = null, $message_id = null) {
    if ($current_datetime === null) {
        $current_datetime = date('Y-m-d H:i:s');
    }
    
    // Preparar o valor do message_id para a query
    $message_id_value = $message_id ? "'" . $DB->escape($message_id) . "'" : "NULL";
    
    // Verificar se followup_id é uma string que deve ser citada
    $followup_id_value = is_numeric($followup_id) ? $followup_id : "'$followup_id'";
    
    // Construir a query com ON DUPLICATE KEY UPDATE
    $query = "INSERT INTO glpi_plugin_ticketanswers_views
             (ticket_id, users_id, followup_id, viewed_at, message_id)
             VALUES ($ticket_id, $users_id, $followup_id_value, '$current_datetime', $message_id_value)
             ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    error_log("Executando query: $query");
    
    // Executar a query
    $insertResult = $DB->doQuery($query);
    
    if ($insertResult) {
        error_log("Registro inserido ou atualizado com sucesso para followup_id=$followup_id");
        return true;
    } else {
        $error = $DB->error();
        error_log("Erro ao inserir/atualizar registro para followup_id=$followup_id: " . $error);
        return false;
    }
}

// Se for uma requisição AJAX, retornar JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit();
}

// Se não for AJAX, redirecionar para a página do ticket
if ($ticket_id > 0) {
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $ticket_id);
} else {
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/ticketanswers/front/index.php");
}

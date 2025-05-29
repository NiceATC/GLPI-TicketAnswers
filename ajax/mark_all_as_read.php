<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

// Obtém o ID do usuário logado
$users_id = Session::getLoginUserID();
$success = false;
$total_count = 0;

global $DB;

// Gerar um message_id único para esta operação em lote
$batch_message_id = 'batch_' . time();

// Registrar início da operação
error_log("Iniciando operação de marcar todas as notificações como lidas para o usuário $users_id (message_id: $batch_message_id)");

try {
    // 1. Primeiro, vamos identificar todas as notificações não lidas
    $notifications_query = "
    (
        -- Notificações de acompanhamento (followup)
        SELECT 
            t.id AS ticket_id,
            tf.id AS followup_id,
            'followup' AS notification_type
        FROM 
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
            INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = tf.id
            )
        WHERE 
            tf.users_id <> $users_id
            AND v.id IS NULL
            AND t.status != 6
    )
    UNION
    (
        -- Notificações de grupo
        SELECT 
            t.id AS ticket_id,
            t.id AS followup_id,
            'group' AS notification_type
        FROM 
            glpi_tickets t
            INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
            INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
            LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 2
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = -t.id
            )
        WHERE 
            tu.id IS NULL
            AND v.id IS NULL
            AND t.status IN (1, 2)
    )";
    
    $result = $DB->query($notifications_query);
    $count = 0;
    
    // 2. Para cada notificação, marcar como lida usando a mesma lógica dos botões individuais
    while ($data = $DB->fetchAssoc($result)) {
        $ticket_id = $data['ticket_id'];
        $followup_id = $data['followup_id'];
        $notification_type = $data['notification_type'];
        
        // Determinar o valor correto de followup_id com base no tipo
        $actual_followup_id = $followup_id;
        if ($notification_type === 'group') {
            $actual_followup_id = -$ticket_id;
        } else if ($notification_type === 'observer') {
            $actual_followup_id = -($ticket_id + 1000000);
        } else if ($notification_type === 'assigned') {
            $actual_followup_id = -($ticket_id + 2000000);
        }
        
        // Verificar se já existe um registro para esta notificação
        $check_query = "SELECT id FROM glpi_plugin_ticketanswers_views
                        WHERE users_id = $users_id
                        AND ticket_id = $ticket_id
                        AND followup_id = $actual_followup_id";
        $check_result = $DB->query($check_query);
        
        // Se não existir, inserir
        if ($DB->numrows($check_result) == 0) {
            $insert_result = $DB->insert('glpi_plugin_ticketanswers_views', [
                'ticket_id' => $ticket_id,
                'users_id' => $users_id,
                'followup_id' => $actual_followup_id,
                'viewed_at' => date('Y-m-d H:i:s'),
                'message_id' => $batch_message_id
            ]);
            
            if ($insert_result) {
                $count++;
                error_log("Marcada notificação como lida: ticket=$ticket_id, followup=$followup_id, tipo=$notification_type, actual_followup=$actual_followup_id");
            } else {
                error_log("Falha ao marcar notificação como lida: ticket=$ticket_id, followup=$followup_id, tipo=$notification_type");
            }
        } else {
            error_log("Notificação já marcada como lida: ticket=$ticket_id, followup=$followup_id, tipo=$notification_type");
        }
    }
    
    $total_count = $count;
    $success = ($count > 0);
    
    error_log("Marcadas $count notificações como lidas no total (message_id: $batch_message_id)");
    
} catch (Exception $e) {
    error_log("Erro ao marcar notificações como lidas: " . $e->getMessage());
    $success = false;
}

// Se for uma requisição AJAX, retornar JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    echo json_encode([
        'success' => $success, 
        'count' => $total_count,
        'message_id' => $batch_message_id
    ]);
    exit();
}

// Se não for AJAX, redirecionar para a página de notificações
Html::redirect($CFG_GLPI["root_doc"] . "/plugins/ticketanswers/front/index.php");
?>

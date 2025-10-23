<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

header("Content-Type: application/json");

global $DB;

$users_id = Session::getLoginUserID();
$current_datetime = date("Y-m-d H:i:s");

try {
    // Marcar followups regulares como lidos
    $query1 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, tf.id, '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id AND v.followup_id = tf.id
               WHERE v.id IS NULL AND tf.itemtype = 'Ticket'
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query1);
    
    // Marcar soluções recusadas como lidas
    $query2 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, its.id, '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_itilsolutions its ON t.id = its.items_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id AND v.followup_id = its.id
               WHERE v.id IS NULL AND its.itemtype = 'Ticket' AND its.status = 4
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query2);
    
    // Marcar tickets de grupo como lidos (followup_id = ticket_id + 10000000)
    $query3 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, t.id + 10000000, '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
               INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id AND v.followup_id = t.id + 10000000
               WHERE v.id IS NULL
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query3);
    
    // Marcar tickets de observador como lidos (followup_id = ticket_id + 20000000)
    $query4 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, t.id + 20000000, '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 3 AND tu.users_id = $users_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id AND v.followup_id = t.id + 20000000
               WHERE v.id IS NULL
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query4);
    
    // Marcar tickets atribuídos ao técnico como lidos (followup_id = ticket_id + 30000000)
    $query5 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, t.id + 30000000, '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id AND v.followup_id = t.id + 30000000
               WHERE v.id IS NULL
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query5);
    
    // Marcar mudanças de status como lidas (followup_id = CONCAT('status_', ticket_id, '_', status))
    $query6 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, CONCAT('status_', t.id, '_', t.status), '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id 
                   AND v.followup_id = CONCAT('status_', t.id, '_', t.status)
               WHERE v.id IS NULL AND t.status IN (2, 3, 4)
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query6);
    
    // Marcar motivos de pendência como lidos (followup_id = CONCAT('pending_', ticket_id))
    $query7 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, CONCAT('pending_', t.id), '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id 
                   AND v.followup_id = CONCAT('pending_', t.id)
               WHERE v.id IS NULL AND t.status = 3 AND t.waiting_duration > 0
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query7);
    
    // Marcar validações pendentes como lidas
    $query8 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, tv.id, '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id AND v.followup_id = tv.id
               WHERE v.id IS NULL AND tv.users_id_validate = $users_id AND tv.status = 2
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query8);
    
    // Marcar respostas de validação como lidas (followup_id = CONCAT('validation_response_', validation_id))
    $query9 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
               SELECT t.id, $users_id, CONCAT('validation_response_', tv.id), '$current_datetime'
               FROM glpi_tickets t
               INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
               LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id 
                   AND v.followup_id = CONCAT('validation_response_', tv.id)
               WHERE v.id IS NULL AND tv.status IN (3, 4)
               ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query9);
    
    // Marcar tickets sem atribuição como lidos (followup_id = CONCAT('unassigned_', ticket_id))
    $query10 = "INSERT INTO glpi_plugin_ticketanswers_views (ticket_id, users_id, followup_id, viewed_at)
                SELECT t.id, $users_id, CONCAT('unassigned_', t.id), '$current_datetime'
                FROM glpi_tickets t
                LEFT JOIN glpi_tickets_users tech ON t.id = tech.tickets_id AND tech.type = 2
                LEFT JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
                LEFT JOIN glpi_plugin_ticketanswers_views v ON v.users_id = $users_id 
                    AND v.followup_id = CONCAT('unassigned_', t.id)
                WHERE tech.id IS NULL 
                    AND gt.id IS NULL 
                    AND v.id IS NULL 
                    AND t.status IN (1, 2)
                    AND t.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND EXISTS (
                        SELECT 1 
                        FROM glpi_profiles_users pu 
                        INNER JOIN glpi_profiles p ON pu.profiles_id = p.id 
                        WHERE pu.users_id = $users_id 
                        AND (p.name LIKE '%admin%' OR p.name LIKE '%tecn%' OR p.name LIKE '%super%')
                    )
                ON DUPLICATE KEY UPDATE viewed_at = '$current_datetime'";
    
    $DB->doQuery($query10);
    
    echo json_encode(["success" => true, "message" => "Todas as notificações foram marcadas como lidas"]);
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

exit();

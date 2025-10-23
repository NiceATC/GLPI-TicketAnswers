<?php
/**
 * Retorna notificações para o dropdown
 */

include ("../../../inc/includes.php");

Session::checkLoginUser();

global $DB;

// Forçar header JSON
header('Content-Type: application/json');

$users_id = Session::getLoginUserID();

// Limitar a 10 notificações mais recentes
$limit = 10;

// Query COMPLETA com TODOS os tipos de notificação
$combined_query = "
    SELECT * FROM (
        -- Notificações de respostas em chamados atribuídos (followup)
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            tf.id AS followup_id,
            tf.date AS notification_date,
            tf.content AS content,
            u.name AS user_name,
            'followup' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
            INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
            LEFT JOIN glpi_users u ON tf.users_id = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = tf.id
            )
        WHERE
            tf.users_id <> $users_id
            AND v.id IS NULL
            AND t.status != 6
            AND tf.is_private = 0
            AND tf.date > (
                SELECT COALESCE(MAX(date), '1970-01-01')
                FROM glpi_itilfollowups tf2
                WHERE tf2.items_id = t.id AND tf2.itemtype = 'Ticket' AND tf2.users_id = $users_id
            )

        UNION

        -- Notificações de respostas de técnicos em chamados do usuário
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            tf.id AS followup_id,
            tf.date AS notification_date,
            tf.content AS content,
            u.name AS user_name,
            'technician_response' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
            INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
            LEFT JOIN glpi_users u ON tf.users_id = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = tf.id
            )
        WHERE
            tf.users_id <> $users_id
            AND v.id IS NULL
            AND t.status != 6
            AND tf.is_private = 0
            AND EXISTS (
                SELECT 1 FROM glpi_tickets_users tech_user
                WHERE tech_user.tickets_id = t.id
                AND tech_user.users_id = tf.users_id
                AND tech_user.type = 2
            )

        UNION

        -- Notificações de solução recusada
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            tf.id AS followup_id,
            tf.date AS notification_date,
            tf.content AS content,
            u.name AS user_name,
            'refused' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
            INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
            LEFT JOIN glpi_users u ON tf.users_id = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = tf.id
            )
        WHERE
            v.id IS NULL
            AND t.status = 4
            AND tf.is_private = 0
            AND tf.content LIKE '%recusada%'
            AND tf.date > (
                SELECT COALESCE(MAX(date), '1970-01-01')
                FROM glpi_itilfollowups tf2
                WHERE tf2.items_id = t.id AND tf2.itemtype = 'Ticket' AND tf2.users_id = $users_id
            )

        UNION

        -- Notificações de chamados de grupo
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            t.id + 10000000 AS followup_id,
            t.date_creation AS notification_date,
            t.content AS content,
            u.name AS user_name,
            'group' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
            INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
            LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 2
            LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = t.id + 10000000
            )
        WHERE
            tu.id IS NULL
            AND v.id IS NULL
            AND t.status IN (1, 2)
            AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)

        UNION

        -- Notificações de chamados atribuídos ao técnico
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            t.id + 30000000 AS followup_id,
            t.date_mod AS notification_date,
            t.content AS content,
            u.name AS user_name,
            'assigned_tech' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
            LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = t.id + 30000000
            )
        WHERE
            v.id IS NULL
            AND t.status IN (1, 2)
            AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION
        
        -- Notificações de tickets sem atribuição
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            CONCAT('unassigned_', t.id) AS followup_id,
            t.date_creation AS notification_date,
            t.content AS content,
            'Sistema' AS user_name,
            'unassigned' AS type
        FROM
            glpi_tickets t
            LEFT JOIN glpi_tickets_users tech ON t.id = tech.tickets_id AND tech.type = 2
            LEFT JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('unassigned_', t.id)
            )
        WHERE
            tech.id IS NULL
            AND gt.id IS NULL
            AND v.id IS NULL
            AND t.status IN (1, 2)
            AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND EXISTS (
                SELECT 1 FROM glpi_profiles_users pu
                INNER JOIN glpi_profiles p ON pu.profiles_id = p.id
                WHERE pu.users_id = $users_id
                AND (p.name LIKE '%admin%' OR p.name LIKE '%tecn%' OR p.name LIKE '%super%')
            )

        UNION

        -- Notificações de chamados onde o usuário é observador
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            t.id + 20000000 AS followup_id,
            t.date_creation AS notification_date,
            t.content AS content,
            u.name AS user_name,
            'observer' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 3 AND tu.users_id = $users_id
            LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = t.id + 20000000
            )
        WHERE
            v.id IS NULL
            AND t.status IN (1, 2)
            AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)

        UNION

        -- Notificações de chamados onde o grupo do usuário é observador
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            t.id + 20000000 AS followup_id,
            tf.date AS notification_date,
            tf.content AS content,
            u.name AS user_name,
            'group_observer' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 3
            INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
            INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
            LEFT JOIN glpi_users u ON tf.users_id = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = t.id + 20000000
            )
        WHERE
            v.id IS NULL
            AND t.status != 6
            AND tf.date > (
                SELECT COALESCE(MAX(date), '1970-01-01')
                FROM glpi_itilfollowups tf2
                WHERE tf2.items_id = t.id AND tf2.itemtype = 'Ticket' AND tf2.users_id = $users_id
            )

        UNION

        -- Notificações de mudanças de status em chamados do usuário
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            CONCAT('status_', t.id, '_', t.status) AS followup_id,
            t.date_mod AS notification_date,
            CONCAT('Status alterado para: ', 
                CASE t.status
                    WHEN 1 THEN 'Novo'
                    WHEN 2 THEN 'Em Atendimento'
                    WHEN 3 THEN 'Pendente'
                    WHEN 4 THEN 'Solucionado'
                    WHEN 5 THEN 'Fechado'
                    WHEN 6 THEN 'Cancelado'
                    ELSE 'Desconhecido'
                END
            ) AS content,
            'Sistema' AS user_name,
            'status_change' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('status_', t.id, '_', t.status)
            )
        WHERE
            v.id IS NULL
            AND t.status IN (2, 3, 4)
            AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)

        UNION

        -- Notificações de validações pendentes
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            tv.id AS followup_id,
            tv.submission_date AS notification_date,
            CONCAT('Solicitação de validação: ', 
                IF(tv.comment_submission IS NOT NULL AND tv.comment_submission != '', 
                   tv.comment_submission, 
                   'Sem comentários adicionais.')
            ) AS content,
            u.name AS user_name,
            'validation' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
            LEFT JOIN glpi_users u ON tv.users_id = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = tv.id
            )
        WHERE
            tv.users_id_validate = $users_id
            AND tv.status = 2
            AND t.status != 6
            AND v.id IS NULL

        UNION

        -- Notificações de respostas a validações solicitadas
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            CONCAT('validation_response_', tv.id) AS followup_id,
            tv.validation_date AS notification_date,
            CASE
                WHEN tv.status = 3 THEN CONCAT('Validação APROVADA', 
                    IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '', 
                       CONCAT(': ', tv.comment_validation), 
                       ': Sem comentários.'))
                WHEN tv.status = 4 THEN CONCAT('Validação RECUSADA', 
                    IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '', 
                       CONCAT(': ', tv.comment_validation), 
                       ': Sem comentários.'))
                ELSE 'Status de validação desconhecido'
            END AS content,
            u.name AS user_name,
            'validation_response' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
            LEFT JOIN glpi_users u ON tv.users_id_validate = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('validation_response_', tv.id)
            )
        WHERE
            t.status != 6
            AND (tv.status = 3 OR tv.status = 4)
            AND v.id IS NULL
            AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)

        UNION

        -- Notificações de respostas a solicitações de validação
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            CONCAT('validation_request_response_', tv.id) AS followup_id,
            tv.validation_date AS notification_date,
            CASE
                WHEN tv.status = 3 THEN CONCAT('Validação APROVADA pelo validador', 
                    IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '', 
                       CONCAT(': ', tv.comment_validation), 
                       ': Sem comentários.'))
                WHEN tv.status = 4 THEN CONCAT('Validação RECUSADA pelo validador', 
                    IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '', 
                       CONCAT(': ', tv.comment_validation), 
                       ': Sem comentários.'))
                ELSE 'Status de validação desconhecido'
            END AS content,
            u.name AS user_name,
            'validation_request_response' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 1
            INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
            LEFT JOIN glpi_users u ON tv.users_id_validate = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('validation_request_response_', tv.id)
            )
        WHERE
            t.status != 6
            AND (tv.status = 3 OR tv.status = 4)
            AND v.id IS NULL
            AND tv.users_id <> $users_id
            AND tv.users_id_validate <> $users_id
            AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)

        UNION

        -- Notificações de motivos de pendência
        SELECT
            t.id AS ticket_id,
            t.name AS ticket_name,
            CONCAT('pending_', t.id) AS followup_id,
            t.date_mod AS notification_date,
            'Chamado colocado em pendência' AS content,
            'Sistema' AS user_name,
            'pending_reason' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('pending_', t.id)
            )
        WHERE
            v.id IS NULL
            AND t.status = 3
            AND t.waiting_duration > 0
            AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ) AS combined_notifications
    ORDER BY notification_date DESC
    LIMIT $limit
";

$result = $DB->doQuery($combined_query);

$notifications = [];

if ($result && $result->num_rows > 0) {
    while ($data = $result->fetch_assoc()) {
        // Limpar HTML do conteúdo
        $content = strip_tags($data['content'] ?? '');
        $content = html_entity_decode($content);
        $content = mb_substr($content, 0, 100);
        
        // Calcular "tempo atrás"
        $date = new DateTime($data['notification_date']);
        $now = new DateTime();
        $interval = $now->diff($date);
        
        if ($interval->y > 0) {
            $time_ago = $interval->y . ' ano' . ($interval->y > 1 ? 's' : '') . ' atrás';
        } elseif ($interval->m > 0) {
            $time_ago = $interval->m . ' mês' . ($interval->m > 1 ? 'es' : '') . ' atrás';
        } elseif ($interval->d > 0) {
            $time_ago = $interval->d . ' dia' . ($interval->d > 1 ? 's' : '') . ' atrás';
        } elseif ($interval->h > 0) {
            $time_ago = $interval->h . ' hora' . ($interval->h > 1 ? 's' : '') . ' atrás';
        } elseif ($interval->i > 0) {
            $time_ago = $interval->i . ' minuto' . ($interval->i > 1 ? 's' : '') . ' atrás';
        } else {
            $time_ago = 'Agora mesmo';
        }
        
        $notifications[] = [
            'ticket_id' => $data['ticket_id'],
            'ticket_name' => $data['ticket_name'] ?? 'Sem título',
            'followup_id' => $data['followup_id'],
            'date' => $data['notification_date'],
            'time_ago' => $time_ago,
            'content' => $content,
            'user_name' => $data['user_name'] ?? 'Desconhecido',
            'type' => $data['type']
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications,
    'debug' => [
        'query_rows' => $result ? $result->num_rows : 0,
        'user_id' => $users_id
    ]
]);
exit();

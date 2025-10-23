/**
 * Dropdown de Notificações - Estilo Facebook
 * Ticket Answers Plugin
 */

window.NotificationDropdown = {
    isOpen: false,
    
    init: function() {
        // Criar estrutura do dropdown se não existir
        if (!document.getElementById('notification-dropdown')) {
            this.createDropdown();
        }
        
        // Bind eventos
        this.bindEvents();
    },
    
    createDropdown: function() {
        const dropdown = document.createElement('div');
        dropdown.id = 'notification-dropdown';
        dropdown.className = 'notification-dropdown';
        
        dropdown.innerHTML = `
            <div class="notification-dropdown-header">
                <h3>Notificações</h3>
            </div>
            <div class="notification-dropdown-body">
                <div class="notification-loading">
                    <div class="notification-spinner"></div>
                </div>
            </div>
            <div class="notification-dropdown-footer">
                <button class="btn-mark-all-read">Marcar como lidas</button>
                <button class="btn-view-all">Ver todas</button>
            </div>
        `;
        
        document.body.appendChild(dropdown);
    },
    
    bindEvents: function() {
        const dropdown = document.getElementById('notification-dropdown');
        if (!dropdown) return;
        
        // Marcar todas como lidas
        const markAllBtn = dropdown.querySelector('.btn-mark-all-read');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.markAllAsRead();
            });
        }
        
        // Ver todas
        const viewAllBtn = dropdown.querySelector('.btn-view-all');
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.viewAll();
            });
        }
        
        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (this.isOpen && !dropdown.contains(e.target) && !e.target.closest('.notification-bell')) {
                this.close();
            }
        });
        
        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    },
    
    toggle: function() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    },
    
    open: function() {
        const dropdown = document.getElementById('notification-dropdown');
        if (!dropdown) return;
        
        dropdown.classList.add('active');
        this.isOpen = true;
        this.loadNotifications();
    },
    
    close: function() {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            dropdown.classList.remove('active');
        }
        this.isOpen = false;
    },
    
    loadNotifications: function() {
        const dropdownBody = document.querySelector('.notification-dropdown-body');
        if (!dropdownBody) return;
        
        // Mostrar loading
        dropdownBody.innerHTML = `
            <div class="notification-loading">
                <div class="notification-spinner"></div>
            </div>
        `;
        
        // Fazer requisição AJAX
        jQuery.ajax({
            url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/get_notifications_modal.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                console.log('📦 Notificações recebidas:', response);
                
                // Verificar se a resposta tem o formato esperado
                if (response && response.notifications) {
                    this.renderNotifications(response.notifications);
                } else if (Array.isArray(response)) {
                    // Se a resposta já for um array direto
                    this.renderNotifications(response);
                } else {
                    console.warn('⚠️ Resposta inesperada:', response);
                    dropdownBody.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Nenhuma notificação</p>
                        </div>
                    `;
                }
            },
            error: (xhr, status, error) => {
                console.error('❌ Erro ao carregar notificações:', error, xhr.responseText);
                dropdownBody.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Erro ao carregar notificações</p>
                    </div>
                `;
            }
        });
    },
    
    renderNotifications: function(notifications) {
        const dropdownBody = document.querySelector('.notification-dropdown-body');
        if (!dropdownBody) return;
        
        // Verificar se é um array
        if (!Array.isArray(notifications)) {
            console.error('❌ Notificações não é um array:', notifications);
            dropdownBody.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erro no formato das notificações</p>
                </div>
            `;
            return;
        }
        
        if (notifications.length === 0) {
            dropdownBody.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Nenhuma notificação</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        notifications.forEach(notif => {
            const icon = this.getIconForType(notif.type);
            const title = this.getTitleForType(notif.type);
            
            html += `
                <div class="notification-item" data-id="${notif.followup_id}" data-type="${notif.type}" data-ticket="${notif.ticket_id}">
                    <div class="notification-icon type-${notif.type}">
                        <i class="${icon}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${title}</div>
                        <div class="notification-ticket">Chamado #${notif.ticket_id}</div>
                        <div class="notification-text">${notif.content || ''}</div>
                        <span class="notification-time">${notif.time_ago || ''}</span>
                    </div>
                </div>
            `;
        });
        
        dropdownBody.innerHTML = html;
        
        // Adicionar evento de clique em cada notificação
        dropdownBody.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.getAttribute('data-id');
                const type = item.getAttribute('data-type');
                const ticketId = item.getAttribute('data-ticket');
                this.markAsReadAndRedirect(id, type, ticketId);
            });
        });
    },
    
    getIconForType: function(type) {
        const icons = {
            'followup': 'fas fa-comment',
            'technician_response': 'fas fa-reply',
            'refused': 'fas fa-times-circle',
            'validation': 'fas fa-check-circle',
            'assigned': 'fas fa-user-check',
            'assigned_tech': 'fas fa-user-check',
            'group': 'fas fa-users',
            'observer': 'fas fa-eye',
            'status_change': 'fas fa-exchange-alt',
            'pending_reason': 'fas fa-clock',
            'unassigned': 'fas fa-exclamation-triangle'
        };
        return icons[type] || 'fas fa-bell';
    },
    
    getTitleForType: function(type) {
        const titles = {
            'followup': 'Nova resposta',
            'technician_response': 'Técnico respondeu',
            'refused': 'Solução recusada',
            'validation': 'Solicitação de validação',
            'assigned': 'Chamado atribuído',
            'assigned_tech': 'Chamado atribuído',
            'group': 'Chamado do grupo',
            'observer': 'Você é observador',
            'status_change': 'Status alterado',
            'pending_reason': 'Chamado pendente',
            'unassigned': 'Chamado sem atribuição'
        };
        return titles[type] || 'Notificação';
    },
    
    markAsReadAndRedirect: function(followupId, type, ticketId) {
        jQuery.ajax({
            url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/mark_notification_as_read.php',
            method: 'POST',
            data: {
                followup_id: followupId,
                type: type
            },
            dataType: 'json',
            success: (response) => {
                // Redirecionar imediatamente para o chamado
                window.location.href = CFG_GLPI.root_doc + '/front/ticket.form.php?id=' + ticketId;
            },
            error: (xhr, status, error) => {
                console.error('❌ Erro ao marcar como lida:', xhr.responseText);
                // Mesmo com erro, redireciona
                window.location.href = CFG_GLPI.root_doc + '/front/ticket.form.php?id=' + ticketId;
            }
        });
    },
    
    markAllAsRead: function() {
        // Diálogo de confirmação
        if (!confirm('Tem certeza que deseja marcar todas as notificações como lidas?')) {
            return;
        }
        
        jQuery.ajax({
            url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/mark_all_as_read.php',
            method: 'POST',
            dataType: 'json',
            success: (response) => {
                this.close();
                
                // Atualizar contador de notificações
                if (window.checkNotifications) {
                    window.checkNotifications(false);
                }
            },
            error: (xhr, status, error) => {
                console.error('❌ Erro ao marcar todas como lidas:', error);
            }
        });
    },
    
    viewAll: function() {
        window.location.href = CFG_GLPI.root_doc + '/plugins/ticketanswers/front/index.php';
    }
};

// Inicializar quando o DOM estiver pronto
jQuery(document).ready(function() {
    NotificationDropdown.init();
});

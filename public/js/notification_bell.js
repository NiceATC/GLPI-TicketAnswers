/**
 * Notification Bell JavaScript
 * Handles the notification bell icon in the GLPI interface
 */

// Função para adicionar o sino de notificações à interface
function addNotificationBell() {
    
    // Verificar se o sino já existe
    if ($('.notification-bell').length > 0) {
        return;
    }
    
    // Tentar várias abordagens em sequência
    let success = false;
    
    // 1. Método padrão: campo de pesquisa global
    if (!success) {
        const global_search = $('input[name="globalsearch"], input.form-control-search, .search-input');
        if (global_search.length > 0) {
            let container = global_search.closest('.input-group');
            if (container.length === 0) {
                container = global_search.parent();
            }
            if (container.length > 0) {
                injectNotificationButton(global_search, container);
                success = true;
            }
        }
    }
    
    // 2. FormCreator: tentar encontrar elementos específicos do FormCreator
    if (!success) {
        const formcreatorHeader = $('.plugin_formcreator_userForm_header, .plugin_formcreator_header');
        if (formcreatorHeader.length > 0) {
            
            // Criar um contêiner para o sino e som
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center; gap: 5px;"></div>');
            const bellButton = getNotificationButton();
            const soundButton = getSoundToggleButton();
            
            bellContainer.append(bellButton);
            bellContainer.append(soundButton);
            
            // Adicionar ao cabeçalho do FormCreator
            formcreatorHeader.append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
        }
    }
    
    // 3. Menu de usuário no canto superior direito
    if (!success) {
        const userMenu = $('.navbar .navbar-nav:last-child, .navbar .ms-auto, header .navbar-nav:last-child, .user-menu');
        if (userMenu.length > 0) {
            
            // Criar um novo item de menu para o sino
            const bellItem = $('<li class="nav-item" style="display: flex; align-items: center; gap: 5px; margin-right: 10px; flex-direction: row;"></li>');
            const bellButton = getNotificationButton();
            const soundButton = getSoundToggleButton();
            
            bellItem.append(bellButton);
            bellItem.append(soundButton);
            
            // Adicionar antes do menu de usuário
            userMenu.prepend(bellItem);
            
            // Configurar eventos de clique
            setupBellEvents(bellItem);
            success = true;
        }
    }
    
    // 4. Cabeçalho principal
    if (!success) {
        const header = $('header, .navbar, .main-header, #header_top, .top-bar');
        if (header.length > 0) {
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center; gap: 5px;"></div>');
            const bellButton = getNotificationButton();
            const soundButton = getSoundToggleButton();
            
            bellContainer.append(bellButton);
            bellContainer.append(soundButton);
            
            // Adicionar ao cabeçalho
            header.first().append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
        }
    }
    
    // 5. Interface simplificada (self-service)
    if (!success) {
        const selfServiceHeader = $('.navbar.self-service, .self-service .navbar, .self-service-header');
        if (selfServiceHeader.length > 0) {
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center; gap: 5px;"></div>');
            const bellButton = getNotificationButton();
            const soundButton = getSoundToggleButton();
            
            bellContainer.append(bellButton);
            bellContainer.append(soundButton);
            
            // Adicionar ao cabeçalho da interface simplificada
            selfServiceHeader.append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
        }
    }
    
    // 6. Último recurso: adicionar como elemento flutuante
    if (!success) {
        
        // Criar um contêiner flutuante para o sino
        const floatingBell = $(`
            <div class="floating-notification-container" style="
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 9999;
                display: flex;
                gap: 5px;
                background-color: #f8f9fa;
                padding: 5px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            "></div>
        `);
        
        const bellButton = getNotificationButton();
        const soundButton = getSoundToggleButton();
        
        floatingBell.append(bellButton);
        floatingBell.append(soundButton);
        
        // Adicionar ao corpo da página
        $('body').append(floatingBell);
        
        // Configurar eventos de clique
        setupBellEvents(floatingBell);
        success = true;
    }
    
    if (!success) {
        console.error('Não foi possível adicionar o sino de notificações em nenhum local');
    }
}

// Função para obter o botão de notificação
function getNotificationButton() {
    return $(`
        <div class="notification-bell-container" style="position: relative; display: inline-block;">
            <button type="button" class="notification-bell btn btn-outline-secondary" title="Notificações">
                <i class="fas fa-bell fa-lg"></i>
            </button>
            <span class="notification-badge hidden">0</span>
        </div>`);
}

// Função para obter o botão de toggle de som
function getSoundToggleButton() {
    const soundEnabled = getSoundEnabledState();
    const icon = soundEnabled ? 'fa-volume-up' : 'fa-volume-mute';
    const title = soundEnabled ? 'Desativar som de notificações' : 'Ativar som de notificações';
    return $(`
        <button type="button" class="sound-toggle btn btn-outline-secondary" title="${title}">
            <i class="fas ${icon} fa-lg"></i>
        </button>`);
}

// Função para injetar o botão de notificação
function injectNotificationButton(input_element, container = undefined) {
    if (input_element !== undefined && input_element.length > 0) {
        if (container !== undefined) {
            container.append(getNotificationButton());
            // Adicionar botão de toggle de som ao lado do sino
            container.find('.notification-bell').after(getSoundToggleButton());
        } else {
            input_element.after(getNotificationButton());
            container = input_element.parent();
            // Adicionar botão de toggle de som ao lado do sino
            container.find('.notification-bell').after(getSoundToggleButton());
        }
        // Configurar eventos de clique
        setupBellEvents(container);
    }
}

// Método auxiliar para configurar eventos de clique
function setupBellEvents(container) {
    // Clique no sino abre o DROPDOWN
    container.find('.notification-bell').on('click', function(e) {
        e.preventDefault();
        
        // Verificar se o dropdown existe
        if (window.NotificationDropdown && window.NotificationDropdown.toggle) {
            window.NotificationDropdown.toggle();
        } else {
            console.error('❌ Dropdown não encontrado, redirecionando...');
            window.location.href = CFG_GLPI.root_doc + '/plugins/ticketanswers/front/index.php';
        }
    });
    
    container.find('.sound-toggle').on('click', function(e) {
        e.preventDefault();
        toggleNotificationSound();
    });
}

// Função para alternar o som de notificações
function toggleNotificationSound() {
    // Obter o estado atual
    let soundEnabled = getSoundEnabledState();
    
    // Inverter o estado
    soundEnabled = !soundEnabled;
    
    // Atualizar o ícone e o título do botão
    const button = $('.sound-toggle');
    const icon = button.find('i');
    
    if (soundEnabled) {
        icon.removeClass('fa-volume-mute').addClass('fa-volume-up');
        button.attr('title', 'Desativar som de notificações');
        // Tocar um som curto para confirmar que está ativado
        playTestSound();
    } else {
        icon.removeClass('fa-volume-up').addClass('fa-volume-mute');
        button.attr('title', 'Ativar som de notificações');
    }
    
    // Salvar preferência no localStorage
    try {
        localStorage.setItem('ticketAnswersSoundEnabled', soundEnabled ? 'true' : 'false');
    } catch (e) {
        console.error('Não foi possível salvar a preferência de som:', e);
    }
    
}

// Função para obter o estado atual do som
function getSoundEnabledState() {
    // Verificar se há uma preferência salva no localStorage
    try {
        const savedSoundPreference = localStorage.getItem('ticketAnswersSoundEnabled');
        if (savedSoundPreference !== null) {
            return savedSoundPreference === 'true';
        }
    } catch (e) {
        console.error('Erro ao carregar preferência de som:', e);
    }
    
    // Se não houver preferência salva, usar a configuração global ou o padrão
    return window.ticketAnswersConfig && typeof window.ticketAnswersConfig.enableSound !== 'undefined'
        ? window.ticketAnswersConfig.enableSound
        : true; // Som habilitado por padrão
}

// Função para tocar um som de teste
function playTestSound() {
    try {
        const audio = new Audio(CFG_GLPI.root_doc + '/plugins/ticketanswers/public/sound/notification.mp3');
        audio.volume = 0.2; // Volume mais baixo para o teste
        audio.play().catch(error => {
            console.error('Erro ao reproduzir som de teste:', error);
        });
    } catch (e) {
        console.error('Exceção ao tentar tocar som de teste:', e);
    }
}

// Função para tocar o som de notificação
function playNotificationSound() {
    
    // Verificar se o som está habilitado
    const soundEnabled = getSoundEnabledState();
    
    if (!soundEnabled) {
        return;
    }
    
    try {
        // Verificar se já tocou som recentemente (nos últimos 5 segundos)
        const now = Date.now();
        const lastPlayed = window.lastSoundPlayed || 0;
        
        if ((now - lastPlayed) < 5000) {
            return;
        }
        
        window.lastSoundPlayed = now;
        
        // Criar um novo elemento de áudio a cada vez
        const soundPath = CFG_GLPI.root_doc + '/plugins/ticketanswers/public/sound/notification.mp3';
        
        var audioElement = new Audio(soundPath);
        
        // Definir volume
        var volume = (window.ticketAnswersConfig && window.ticketAnswersConfig.soundVolume)
            ? window.ticketAnswersConfig.soundVolume / 100
            : 0.5;
        audioElement.volume = volume;
        
        // Tentar reproduzir
        var playPromise = audioElement.play();
        if (playPromise !== undefined) {
            playPromise.then(() => {
            }).catch(error => {
            });
        }
    } catch (e) {
        console.error('❌ Exceção ao tentar tocar som:', e);
    }
}

// Adicionar estilos CSS necessários
function addNotificationStyles() {
    
    const css = `
        /* Animação de balançar o sino */
        @keyframes bell-shake {
            0% { transform: rotate(0); }
            5% { transform: rotate(15deg); }
            10% { transform: rotate(-15deg); }
            15% { transform: rotate(10deg); }
            20% { transform: rotate(-10deg); }
            25% { transform: rotate(5deg); }
            30% { transform: rotate(-5deg); }
            35% { transform: rotate(0); }
            100% { transform: rotate(0); }
        }
        
        /* Aplicar animação de balançar */
        .notification-bell.animate-bell i {
            animation: bell-shake 2s ease-in-out;
            transform-origin: top center;
        }
        
        /* Animação de pulsar */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        /* Aplicar animação de pulsar */
        .pulse-animation {
            animation: pulse 0.5s 3;
        }
        
        /* Estilos básicos do sino */
        .notification-bell {
            position: relative;
        }
        
        /* Sino com notificações */
        .notification-bell .has-notifications,
        .notification-bell i.has-notifications {
            color: #ff0000 !important;
        }
        
        /* Indicador numérico */
        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color:rgb(236, 79, 17);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
        }
        
        /* Indicador de som */
        .notification-bell.sound-disabled {
            opacity: 0.8;
        }
        
        .notification-bell.sound-disabled:after {
            content: '';
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 8px;
            height: 8px;
            background-color: #ccc;
            border-radius: 50%;
        }
        
        .notification-bell.sound-enabled:after {
            content: '';
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 8px;
            height: 8px;
            background-color: #4CAF50;
            border-radius: 50%;
        }
    `;
    
    $('<style>').prop('type', 'text/css').html(css).appendTo('head');
}


// Verificar se deve mostrar o sino
function shouldShowBell() {
    
    // Verificar se há uma configuração explícita
    if (window.ticketAnswersConfig && typeof window.ticketAnswersConfig.showBellEverywhere !== 'undefined') {
        console.log('Configuração explícita encontrada:', window.ticketAnswersConfig.showBellEverywhere);
        return window.ticketAnswersConfig.showBellEverywhere;
    }
    
    // Por padrão, mostrar o sino em todas as interfaces (incluindo self-service)
    return true;
}

// Função para verificar notificações
function checkNotifications() {
    
    // Armazenar o valor atual antes da verificação
    const previousCount = window.lastNotificationCount;
    const isFirstCheck = (typeof previousCount === 'undefined');
    
    $.ajax({
        url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/check_all_notifications.php',
        type: 'GET',
        dataType: 'json',
        success: (data) => {
            // Log condensado - sobrescreve o anterior
            console.clear();
            console.log('🔔 Contadores recebidos:', {
                combined_count: data.combined_count || 0,
                count: data.count || 0,
                followup: data.followup_count || 0,
                technician_response: data.technician_response_count || 0,
                refused: data.refused_count || 0,
                group: data.group_count || 0,
                observer: data.observer_count || 0,
                group_observer: data.group_observer_count || 0,
                assigned_tech: data.assigned_tech_count || 0,
                validation: data.validation_count || 0,
                validation_response: data.validation_response_count || 0,
                validation_request_response: data.validation_request_response_count || 0,
                status_change: data.status_change_count || 0,
                pending_reason: data.pending_reason_count || 0,
                unassigned: data.unassigned_count || 0
            });
            
            // Somar para verificar discrepância
            const soma_individual = (data.followup_count || 0) + 
                                    (data.technician_response_count || 0) + 
                                    (data.refused_count || 0) + 
                                    (data.group_count || 0) + 
                                    (data.observer_count || 0) + 
                                    (data.group_observer_count || 0) + 
                                    (data.assigned_tech_count || 0) + 
                                    (data.validation_count || 0) + 
                                    (data.validation_response_count || 0) + 
                                    (data.validation_request_response_count || 0) + 
                                    (data.status_change_count || 0) + 
                                    (data.pending_reason_count || 0) + 
                                    (data.unassigned_count || 0);
            
            console.log('📊 Análise:', {
                combined_count: data.combined_count || 0,
                soma_individual: soma_individual,
                diferenca: (data.combined_count || 0) - soma_individual
            });
            
            if (data.debug_phantom && data.debug_phantom.length > 0) {
                console.log('👻 Notificações fantasma:', data.debug_phantom);
            }
            
            // Atualizar o contador visual
            const currentCount = data.combined_count || data.count || 0;
            updateNotificationCount(currentCount);
            
            // Verificar se há novas notificações
            if (currentCount > previousCount && !isFirstCheck) {
                console.log('🆕 Nova notificação detectada!');
                
                // Aplicar a animação de pulso ao sino
                $('.notification-bell').addClass('animate-bell');
                setTimeout(() => {
                    $('.notification-bell').removeClass('animate-bell');
                }, 3000);
                
                // Tocar som de notificação
                playNotificationSound();
            }
            
            // Armazena o número atual de notificações para a próxima verificação
            window.lastNotificationCount = currentCount;
        },
        error: (xhr, status, error) => {
            console.error('❌ Erro ao verificar notificações:', error);
        }
    });
}

// Função para atualizar o contador de notificações
function updateNotificationCount(count) {
    // Garantir que count seja um número válido
    count = parseInt(count) || 0;
    
    
    const bell = $('.notification-bell');
    const badge = $('.notification-bell-container .notification-badge');
    const bellIcon = bell.find('i');
    
    if (count > 0) {
        // Mostrar badge com contador
        badge.text(count > 99 ? '99+' : count);
        badge.removeClass('hidden');
        
        // Sino vermelho quando há notificações
        bell.removeClass('btn-outline-secondary').addClass('btn-danger');
        bellIcon.addClass('text-white has-notifications');
        
    } else {
        // Esconder badge
        badge.addClass('hidden');
        
        // Sino normal quando não há notificações
        bell.removeClass('btn-danger').addClass('btn-outline-secondary');
        bellIcon.removeClass('text-white has-notifications');
        
    }
}

// Inicialização
console.log('========== NOTIFICATION BELL SCRIPT LOADED ==========');

// Garantir que as funções estejam disponíveis globalmente
window.addNotificationBell = addNotificationBell;
window.playNotificationSound = playNotificationSound;
window.checkNotifications = checkNotifications;

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
    console.log('========== DOCUMENT READY TRIGGERED ==========');
    console.log('CFG_GLPI disponível:', typeof CFG_GLPI !== 'undefined');
    
    
    // Verificar se elementos importantes existem
    console.log('Campo de pesquisa global:', $('input[name="globalsearch"], input.form-control-search, .search-input').length);
    console.log('Cabeçalho:', $('header, .navbar, .main-header, #header_top, .top-bar').length);
    
    // Adicionar estilos CSS
    addNotificationStyles();
    
    // Verificar se deve mostrar o sino
    if (shouldShowBell()) {
        // Adicionar o sino com um pequeno atraso para garantir que a página esteja carregada
        setTimeout(function() {
            try {
                addNotificationBell();
                
                // Verificar notificações imediatamente
                setTimeout(checkNotifications, 2000);
                
                // Configurar verificação periódica
                const checkInterval = (window.ticketAnswersConfig && window.ticketAnswersConfig.checkInterval) 
                    ? window.ticketAnswersConfig.checkInterval * 1000 
                    : 3000; // Padrão: 3 segundos
                
                window.notificationInterval = setInterval(checkNotifications, checkInterval);
                
            } catch (e) {
                console.error('Erro ao adicionar sino de notificações:', e);
            }
        }, 1000);
        
        // Adicionar evento de interação inicial para "desbloquear" o áudio
        $(document).one('click', function() {
            // Criar e reproduzir um áudio silencioso para "desbloquear" a API de áudio
            try {
                var unlockAudio = new Audio();
                unlockAudio.play().catch(function(e) {
                });
            } catch (e) {
            }
        });
    } else {
    }
});


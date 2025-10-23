/**
 * Notification Bell JavaScript
 * Handles the notification bell icon in the GLPI interface
 */

// Função para adicionar o sino de notificações à interface
function addNotificationBell() {
    console.log('Tentando adicionar o sino de notificações...');
    
    // Verificar se o sino já existe
    if ($('.notification-bell').length > 0) {
        console.log('Sino já existe, não adicionando novamente');
        return;
    }
    
    // Tentar várias abordagens em sequência
    let success = false;
    
    // 1. Método padrão: campo de pesquisa global
    if (!success) {
        const global_search = $('input[name="globalsearch"], input.form-control-search, .search-input');
        if (global_search.length > 0) {
            console.log('Campo de pesquisa global encontrado:', global_search.length);
            let container = global_search.closest('.input-group');
            if (container.length === 0) {
                container = global_search.parent();
            }
            if (container.length > 0) {
                injectNotificationButton(global_search, container);
                success = true;
                console.log('Sino adicionado ao campo de pesquisa global');
            }
        }
    }
    
    // 2. FormCreator: tentar encontrar elementos específicos do FormCreator
    if (!success) {
        const formcreatorHeader = $('.plugin_formcreator_userForm_header, .plugin_formcreator_header');
        if (formcreatorHeader.length > 0) {
            console.log('Cabeçalho do FormCreator encontrado');
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center;"></div>');
            bellContainer.append(getNotificationButton());
            bellContainer.append(getSoundToggleButton());
            
            // Adicionar ao cabeçalho do FormCreator
            formcreatorHeader.append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
            console.log('Sino adicionado ao cabeçalho do FormCreator');
        }
    }
    
    // 3. Menu de usuário no canto superior direito
    if (!success) {
        const userMenu = $('.navbar .navbar-nav:last-child, .navbar .ms-auto, header .navbar-nav:last-child, .user-menu');
        if (userMenu.length > 0) {
            console.log('Menu de usuário encontrado');
            
            // Criar um novo item de menu para o sino
            const bellItem = $('<li class="nav-item" style="display: flex; align-items: center; margin-right: 10px;"></li>');
            bellItem.append(getNotificationButton());
            bellItem.append(getSoundToggleButton());
            
            // Adicionar antes do menu de usuário
            userMenu.prepend(bellItem);
            
            // Configurar eventos de clique
            setupBellEvents(bellItem);
            success = true;
            console.log('Sino adicionado ao menu de usuário');
        }
    }
    
    // 4. Cabeçalho principal
    if (!success) {
        const header = $('header, .navbar, .main-header, #header_top, .top-bar');
        if (header.length > 0) {
            console.log('Cabeçalho principal encontrado');
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center;"></div>');
            bellContainer.append(getNotificationButton());
            bellContainer.append(getSoundToggleButton());
            
            // Adicionar ao cabeçalho
            header.first().append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
            console.log('Sino adicionado ao cabeçalho principal');
        }
    }
    
    // 5. Interface simplificada (self-service)
    if (!success) {
        const selfServiceHeader = $('.navbar.self-service, .self-service .navbar, .self-service-header');
        if (selfServiceHeader.length > 0) {
            console.log('Cabeçalho da interface simplificada encontrado');
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center;"></div>');
            bellContainer.append(getNotificationButton());
            bellContainer.append(getSoundToggleButton());
            
            // Adicionar ao cabeçalho da interface simplificada
            selfServiceHeader.append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
            console.log('Sino adicionado ao cabeçalho da interface simplificada');
        }
    }
    
    // 6. Último recurso: adicionar como elemento flutuante
    if (!success) {
        console.log('Nenhum local adequado encontrado, adicionando sino flutuante');
        
        // Criar um contêiner flutuante para o sino
        const floatingBell = $(`
            <div class="floating-notification-container" style="
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 9999;
                display: flex;
                background-color: #f8f9fa;
                padding: 5px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            "></div>
        `);
        
        floatingBell.append(getNotificationButton());
        floatingBell.append(getSoundToggleButton());
        
        // Adicionar ao corpo da página
        $('body').append(floatingBell);
        
        // Configurar eventos de clique
        setupBellEvents(floatingBell);
        success = true;
        console.log('Sino adicionado como elemento flutuante');
    }
    
    if (!success) {
        console.error('Não foi possível adicionar o sino de notificações em nenhum local');
    }
}

// Função para obter o botão de notificação
function getNotificationButton() {
    return $(`
        <button type="button" class="notification-bell btn btn-outline-secondary" title="Notificações">
            <i class="fas fa-bell fa-lg"></i>
        </button>`);
}

// Função para obter o botão de toggle de som
function getSoundToggleButton() {
    const soundEnabled = getSoundEnabledState();
    const icon = soundEnabled ? 'fa-volume-up' : 'fa-volume-mute';
    const title = soundEnabled ? 'Desativar som de notificações' : 'Ativar som de notificações';
    return $(`
        <button type="button" class="sound-toggle btn btn-sm btn-outline-secondary" title="${title}" style="margin-left: 5px;">
            <i class="fas ${icon}"></i>
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
    container.find('.notification-bell').on('click', function() {
        window.location.href = CFG_GLPI.root_doc + '/plugins/ticketanswers/front/index.php';
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
    
    console.log('Som de notificações ' + (soundEnabled ? 'ativado' : 'desativado'));
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
        const audio = new Audio(CFG_GLPI.root_doc + '/plugins/ticketanswers/sound/notification.mp3');
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
    console.log('Reproduzindo som de notificação...');
    
    // Verificar se o som está habilitado
    if (!getSoundEnabledState()) {
        console.log('Som de notificação desabilitado nas configurações');
        return;
    }
    
    try {
        // Verificar se já tocou som recentemente (nos últimos 5 segundos)
        const now = Date.now();
        const lastPlayed = window.lastSoundPlayed || 0;
        
        if ((now - lastPlayed) < 5000) {
            console.log('Som já tocado recentemente, ignorando');
            return;
        }
        
        window.lastSoundPlayed = now;
        
        // Usar um elemento de áudio existente ou criar um novo
        var audioElement = document.getElementById('notification-sound');
        if (!audioElement) {
            audioElement = document.createElement('audio');
            audioElement.id = 'notification-sound';
            audioElement.src = CFG_GLPI.root_doc + '/plugins/ticketanswers/sound/notification.mp3';
            document.body.appendChild(audioElement);
        }
        
        // Definir volume
        var volume = (window.ticketAnswersConfig && window.ticketAnswersConfig.soundVolume)
            ? window.ticketAnswersConfig.soundVolume / 100
            : 0.5;
        audioElement.volume = volume;
        
        // Forçar o reinício do áudio
        audioElement.currentTime = 0;
        
        // Tentar reproduzir
        var playPromise = audioElement.play();
        if (playPromise !== undefined) {
            playPromise.then(() => {
                console.log('Som de notificação tocado com sucesso');
            }).catch(error => {
                console.error('Erro ao tocar som de notificação:', error);
            });
        }
    } catch (e) {
        console.error('Exceção ao tentar tocar som:', e);
    }
}

// Adicionar estilos CSS necessários
function addNotificationStyles() {
    console.log('Adicionando estilos CSS para notificações');
    
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
    console.log('Verificando se deve mostrar o sino...');
    
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
    console.log('Verificando notificações...');
    
    // Armazenar o valor atual antes da verificação
    const previousCount = window.lastNotificationCount || 0;
    
    $.ajax({
        url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/check_all_notifications.php',
        type: 'GET',
        dataType: 'json',
        success: (data) => {
            console.log('Notificações verificadas:', data);
            
            // Atualizar o contador visual
            const currentCount = data.combined_count || data.count || 0;
            updateNotificationCount(currentCount);
            
            // Verificar se há novas notificações (contagem atual > contagem anterior)
            if (currentCount > previousCount) {
                console.log('Novas notificações detectadas! Anterior:', previousCount, 'Atual:', currentCount);
                
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
            console.error('Erro ao verificar notificações:', error);
        }
    });
}

// Função para atualizar o contador de notificações
function updateNotificationCount(count) {
    console.log('Atualizando indicador de notificações no sino');
    if (count > 0) {
        // Apenas indicar que há notificações (sem número)
        $('.notification-bell i').addClass('has-notifications');
        
        // Remover o contador numérico
        $('.notification-count').remove();
    } else {
        // Remover indicação visual
        $('.notification-bell i').removeClass('has-notifications');
        $('.notification-count').remove();
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
    
    // Verificar se o jQuery está funcionando corretamente
    console.log('jQuery versão:', $.fn.jquery);
    
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
                
                console.log('Configurando verificação periódica a cada', checkInterval/1000, 'segundos');
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
                    console.log('Áudio desbloqueado após interação do usuário');
                });
            } catch (e) {
                console.error('Erro ao desbloquear áudio:', e);
            }
        });
    } else {
        console.log('Sino de notificações desativado para esta interface');
    }
});


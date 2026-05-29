// assets/js/recepcionista-mensagens.js

document.addEventListener("DOMContentLoaded", function() {
    loadConversations();
    
    // Auto refresh chat list and messages
    setInterval(() => {
        loadConversations();
        if(currentChatUserId) {
            loadMessages(currentChatUserId);
        }
    }, 5000);

    const sendBtn = document.getElementById('btn-send-message');
    if(sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    const msgInput = document.getElementById('recep-chat-input');
    if(msgInput) {
        msgInput.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') sendMessage();
        });
    }
});

let currentChatUserId = null;

async function loadConversations() {
    try {
        const response = await fetch('../api/recepcionista/conversas.php');
        const data = await response.json();
        
        const listBody = document.getElementById('chat-list-body');
        if(!listBody) return;

        listBody.innerHTML = '';
        
        if (response.ok) {
            if (data.length === 0) {
                listBody.innerHTML = '<p class="text-center p-4 small text-muted">Nenhuma conversa ativa.</p>';
            } else {
                data.forEach(conv => {
                    const div = document.createElement('div');
                    div.className = `chat-item ${currentChatUserId == conv.id_usuario ? 'active' : ''}`;
                    div.onclick = () => selectConversation(conv.id_usuario, conv.nome);
                    
                    div.innerHTML = `
                        <div class="d-flex align-items-center">
                            <img src="../assets/image/img/user.png" class="rounded-circle mr-3" style="width: 40px;">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-truncate">${conv.nome}</h6>
                                    <small class="text-muted" style="font-size: 10px;">${formatTime(conv.data_ultima)}</small>
                                </div>
                                <p class="mb-0 small text-muted text-truncate">${conv.ultima_mensagem || 'Inicie uma conversa...'}</p>
                            </div>
                        </div>
                    `;
                    listBody.appendChild(div);
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar conversas:", e);
    }
}

function formatTime(dateStr) {
    if(!dateStr) return '';
    const date = new Date(dateStr);
    return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
}

async function selectConversation(userId, userName) {
    currentChatUserId = userId;
    document.getElementById('chat-with-name').textContent = userName;
    
    document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
    
    if(window.event && window.event.currentTarget) {
        window.event.currentTarget.classList.add('active');
    }
    
    loadMessages(userId);
}

async function loadMessages(userId) {
    try {
        const response = await fetch(`../api/mensagens/chat.php?destinatario_id=${userId}`);
        const data = await response.json();
        
        const chatBox = document.getElementById('chat-messages-body');
        if(!chatBox) return;

        chatBox.innerHTML = '';
        
        if (response.ok) {
            data.forEach(msg => {
                const isSentByMe = msg.remetente_id != userId;
                const div = document.createElement('div');
                div.className = `message ${isSentByMe ? 'sent' : 'received'}`;
                div.innerHTML = `
                    <div class="message-content">${msg.conteudo}</div>
                    <div class="message-time">${formatTime(msg.data_envio)}</div>
                `;
                chatBox.appendChild(div);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    } catch (e) {
        console.error("Erro ao carregar mensagens:", e);
    }
}

async function sendMessage() {
    if(!currentChatUserId) {
        notify.warning('Selecione uma conversa primeiro.');
        return;
    }

    const input = document.getElementById('recep-chat-input');
    const msg = input.value.trim();
    if(!msg) return;

    try {
        const response = await fetch('../api/mensagens/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conteudo: msg,
                destinatario_id: currentChatUserId
            })
        });
        
        if(response.ok) {
            input.value = '';
            loadMessages(currentChatUserId);
            loadConversations();
        }
    } catch(e) {
        notify.error("Erro ao enviar mensagem.");
    }
}

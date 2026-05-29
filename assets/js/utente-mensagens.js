document.addEventListener("DOMContentLoaded", function() {
    loadMessages();

    // Atualiza mensagens automaticamente a cada 5 segundos
    setInterval(loadMessages, 5000);

    const form = document.getElementById('chat-form');
    if(form) {
        form.addEventListener('submit', sendMessage);
    }
});

async function loadMessages() {
    try {
        const response = await fetch(`../api/mensagens/chat.php?t=${new Date().getTime()}`);
        const mensagens = await response.json();
        
        const container = document.getElementById('chat-messages-container');
        container.innerHTML = '';
        
        if (response.ok) {
            if (mensagens.length === 0) {
                container.innerHTML = '<div class="text-center text-muted"><small>Nenhuma mensagem ainda. Envie a primeira!</small></div>';
            } else {
                mensagens.forEach(m => {
                    const div = document.createElement('div');
                    div.className = `message ${m.tipo}`;
                    div.textContent = m.conteudo;
                    container.appendChild(div);
                });
                // Scroll to bottom
                container.scrollTop = container.scrollHeight;
            }
        } else {
            container.innerHTML = '<div class="text-center text-danger"><small>Erro ao carregar mensagens.</small></div>';
        }
    } catch (e) {
        console.error("Erro ao carregar mensagens:", e);
    }
}

async function sendMessage(e) {
    e.preventDefault();
    
    const input = document.getElementById('chat-input-text');
    const conteudo = input.value.trim();
    
    if(!conteudo) return;

    try {
        const payload = { conteudo };

        const response = await fetch(`../api/mensagens/chat.php?t=${new Date().getTime()}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        console.log("Resposta do Chat:", data);
        
        if (response.ok) {
            input.value = '';
            loadMessages();
        } else {
            notify.error(data.message || 'Erro ao enviar mensagem.');
            console.error("Erro no chat:", data);
        }
    } catch(e) {
        notify.error('Erro de conexão com o servidor.');
        console.error(e);
    }
}

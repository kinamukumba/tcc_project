document.addEventListener("DOMContentLoaded", function() {
    loadProfileData();

    const form = document.getElementById('form-perfil');
    if(form) {
        form.addEventListener('submit', handleProfileSubmit);
    }

    const btnDelete = document.getElementById('btn-delete-account');
    if(btnDelete) {
        btnDelete.addEventListener('click', handleDeleteAccount);
    }
});

async function loadProfileData() {
    try {
        const response = await fetch('/tcc_project/api/utente/perfil.php');
        const data = await response.json();
        
        if (response.ok) {
            const fullName = `${data.nome || ''} ${data.sobrenome || ''}`.trim();
            
            document.getElementById('profile-name-display').textContent = fullName;
            document.getElementById('profile-total-stays').textContent = data.total_estadias < 10 ? '0' + data.total_estadias : data.total_estadias;
            
            document.getElementById('input-nome').value = data.nome || '';
            document.getElementById('input-sobrenome').value = data.sobrenome || '';
            document.getElementById('input-email').value = data.email || '';
            document.getElementById('input-telefone').value = data.telemovel || '';
            document.getElementById('input-bi').value = data.bi || '';
            
            // Update auth UI user name
            const userNameElements = document.querySelectorAll('.user-name-display');
            userNameElements.forEach(el => el.textContent = fullName);
            
        } else {
            notify.error('Não foi possível carregar os dados do perfil.');
        }
    } catch (e) {
        console.error("Erro ao carregar perfil:", e);
    }
}

async function handleProfileSubmit(e) {
    e.preventDefault();
    
    const formElements = e.target.elements;
    const nome = formElements['nome'].value;
    const sobrenome = formElements['sobrenome'].value;
    const email = formElements['email'].value;
    const telemovel = formElements['telemovel'].value;
    const senha_atual = formElements['senha_atual'].value;
    const nova_senha = formElements['nova_senha'].value;

    if(!nome || !email) {
        notify.warning('Nome e e-mail são obrigatórios!');
        return;
    }

    if(nova_senha && !senha_atual) {
        notify.warning('Para alterar a senha, informe a senha atual.');
        return;
    }

    try {
        const payload = { nome, sobrenome, email, telemovel, senha_atual, nova_senha };

        const response = await fetch('/tcc_project/api/utente/perfil.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            notify.success(data.message || 'Perfil atualizado com sucesso!');
            const fullName = `${nome} ${sobrenome}`.trim();
            document.getElementById('profile-name-display').textContent = fullName;
            
            formElements['senha_atual'].value = '';
            formElements['nova_senha'].value = '';
            
            const userNameElements = document.querySelectorAll('.user-name-display');
            userNameElements.forEach(el => el.textContent = fullName);
        } else {
            notify.error(data.message || 'Erro ao atualizar perfil.');
        }
    } catch(e) {
        notify.error('Erro de conexão com o servidor.');
    }
}

async function handleDeleteAccount() {
    notify.confirm('ATENÇÃO: Esta ação é irreversível. Todas as suas reservas e mensagens serão apagadas permanentemente. Deseja mesmo eliminar sua conta?', async function() {
        try {
            const response = await fetch('/tcc_project/api/utente/perfil.php', {
                method: 'DELETE'
            });
            const data = await response.json();

            if (response.ok) {
                notify.success(data.message);
                setTimeout(() => {
                    window.location.href = '../index.html';
                }, 2000);
            } else {
                notify.error(data.message);
            }
        } catch (e) {
            notify.error('Erro ao conectar com o servidor.');
        }
    });
}

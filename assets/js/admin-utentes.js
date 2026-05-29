// assets/js/admin-utentes.js
// Universal User Management (Clients, Receptionists, Managers, Admins)

document.addEventListener("DOMContentLoaded", function() {
    loadUsuarios();

    const searchInput = document.querySelector('input[placeholder^="Pesquisar"]');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('#utentes-table-body tr');
            rows.forEach(row => {
                row.toggleAttribute('hidden', !row.textContent.toLowerCase().includes(term));
            });
        });
    }

    const form = document.getElementById('formUtente');
    if(form) {
        form.addEventListener('submit', saveUsuario);
    }
});

async function loadUsuarios() {
    try {
        const response = await fetch('/tcc_project/api/admin/usuarios.php');
        const data = await response.json();
        
        const tableBody = document.getElementById('utentes-table-body');
        if(!tableBody) return;

        tableBody.innerHTML = '';
        
        if (response.ok) {
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Nenhum utilizador registado.</td></tr>';
            } else {
                data.forEach(user => {
                    const row = document.createElement('tr');
                    
                    var roleBadge = '';
                    switch (user.tipo_usuario) {
                        case 'admin':
                            roleBadge = '<span class="badge badge-danger">ADMIN</span>';
                            break;
                        case 'gerente':
                            roleBadge = '<span class="badge badge-warning text-dark">GESTOR</span>';
                            break;
                        case 'recepcionista':
                            roleBadge = '<span class="badge badge-info">RECEÇÃO</span>';
                            break;
                        case 'utente':
                            roleBadge = '<span class="badge badge-success">CLIENTE</span>';
                            break;
                    }

                    row.innerHTML = `
                        <td>#${user.id_usuario}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="../assets/image/img/user.png" class="rounded-circle mr-2" style="width: 30px;">
                                <div>
                                    <div class="font-weight-bold">${user.nome}</div>
                                    <small class="text-muted">${user.email}</small>
                                </div>
                            </div>
                        </td>
                        <td>${user.telefone || '-'}</td>
                        <td>${roleBadge}</td>
                        <td><span class="badge badge-success">Ativo</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="editUsuario(${user.id_usuario})" title="Editar">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUsuario(${user.id_usuario})" title="Excluir">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar utilizadores:", e);
    }
}

function prepareCreate() {
    document.getElementById('modalTitle').textContent = 'Novo Utilizador';
    document.getElementById('formUtente').reset();
    document.getElementById('u_id').value = '';
    document.getElementById('pass_container').style.display = 'block';
    document.getElementById('u_senha').required = true;
    document.getElementById('u_role').disabled = false;
}

var allUsersLocal = [];
async function editUsuario(id) {
    try {
        const response = await fetch('/tcc_project/api/admin/usuarios.php');
        const list = await response.json();
        
        const user = list.find(u => u.id_usuario == id);
        
        if(user) {
            document.getElementById('modalTitle').textContent = 'Editar Utilizador';
            document.getElementById('u_id').value = user.id_usuario;
            document.getElementById('u_nome').value = user.nome;
            document.getElementById('u_email').value = user.email;
            document.getElementById('u_tel').value = user.telefone || '';
            document.getElementById('u_role').value = user.tipo_usuario;
            document.getElementById('u_role').disabled = true; // Impedir alteração de role direta
            document.getElementById('pass_container').style.display = 'block';
            document.getElementById('u_senha').required = false; // Senha não obrigatória ao editar
            document.getElementById('u_senha').value = ''; // Limpar para digitação
            
            $('#modalUtente').modal('show');
        }
    } catch(e) {
        notify.error("Erro ao carregar dados do utilizador.");
    }
}

async function saveUsuario(e) {
    e.preventDefault();
    const id = document.getElementById('u_id').value;
    const method = id ? 'PUT' : 'POST';
    
    const data = {
        id_usuario: id,
        nome: document.getElementById('u_nome').value.trim(),
        email: document.getElementById('u_email').value.trim(),
        telefone: document.getElementById('u_tel').value.trim(),
        tipo_usuario: document.getElementById('u_role').value
    };
    
    const senhaVal = document.getElementById('u_senha').value;
    if(senhaVal) data.senha = senhaVal;
    
    try {
        const response = await fetch('/tcc_project/api/admin/usuarios.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await response.json();
        if(response.ok) {
            notify.success(res.message);
            $('#modalUtente').modal('hide');
            loadUsuarios();
        } else {
            notify.error(res.message);
        }
    } catch(e) {
        notify.error("Erro ao salvar utilizador.");
    }
}

async function deleteUsuario(id) {
    if(!confirm('Deseja excluir permanentemente este utilizador?')) return;
    try {
        const response = await fetch(`/tcc_project/api/admin/usuarios.php?id=${id}`, {
            method: 'DELETE'
        });
        const res = await response.json();
        if(response.ok) {
            notify.success(res.message);
            loadUsuarios();
        } else {
            notify.error(res.message);
        }
    } catch(e) {
        notify.error("Erro ao conectar com o servidor.");
    }
}

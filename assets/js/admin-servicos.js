// assets/js/admin-servicos.js

document.addEventListener("DOMContentLoaded", function() {
    loadServices();

    // Search bar functionality
    const searchInput = document.getElementById('searchServiceInput');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('#services-table-body tr');
            rows.forEach(row => {
                if(row.cells.length > 1) { // Skip empty row message
                    const text = row.cells[1].textContent.toLowerCase() + ' ' + row.cells[2].textContent.toLowerCase();
                    row.toggleAttribute('hidden', !text.includes(term));
                }
            });
        });
    }

    // Submit handler
    const form = document.getElementById('formServico');
    if(form) {
        form.addEventListener('submit', handleFormSubmit);
    }
});

let loadedServicesList = [];

async function loadServices() {
    try {
        const response = await fetch('/tcc_project/api/admin/servicos.php');
        const data = await response.json();
        
        const tableBody = document.getElementById('services-table-body');
        if(!tableBody) return;

        tableBody.innerHTML = '';
        
        if (response.ok) {
            loadedServicesList = data;
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Nenhum serviço cadastrado.</td></tr>';
            } else {
                data.forEach(s => {
                    const row = document.createElement('tr');
                    const statusBadge = s.status === 'ocupado' 
                        ? '<span class="badge badge-danger px-2 py-1">Ocupado</span>' 
                        : '<span class="badge badge-success px-2 py-1">Desocupado</span>';
                    
                    row.innerHTML = `
                        <td>#${s.id_serviço}</td>
                        <td><strong>${s.tipos_servicos}</strong></td>
                        <td>${s.descrição}</td>
                        <td><strong>${parseFloat(s.preço).toLocaleString('pt-PT')} KZ</strong></td>
                        <td>${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="prepareEdit(${s.id_serviço})" data-toggle="modal" data-target="#modalServico"><i class="fa fa-edit"></i> Editar</button>
                            <button class="btn btn-sm btn-outline-danger ml-1" onclick="deleteService(${s.id_serviço})"><i class="fa fa-trash"></i> Excluir</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        } else {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Falha ao ler dados do servidor.</td></tr>';
        }
    } catch (e) {
        console.error("Erro ao carregar serviços:", e);
    }
}

function prepareCreate() {
    document.getElementById('modalTitle').textContent = 'Novo Serviço / Quarto';
    document.getElementById('formServico').reset();
    document.getElementById('s_id').value = '';
    document.getElementById('s_status').value = 'desocupado';
}

function prepareEdit(id) {
    const s = loadedServicesList.find(item => item.id_serviço == id);
    if(s) {
        document.getElementById('modalTitle').textContent = 'Editar Serviço / Quarto';
        document.getElementById('s_id').value = s.id_serviço;
        document.getElementById('s_tipo').value = s.tipos_servicos;
        document.getElementById('s_descricao').value = s.descrição;
        document.getElementById('s_preco').value = parseFloat(s.preço);
        document.getElementById('s_status').value = s.status || 'desocupado';
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const id = document.getElementById('s_id').value;
    const tipo = document.getElementById('s_tipo').value.trim();
    const descricao = document.getElementById('s_descricao').value.trim();
    const preco = parseFloat(document.getElementById('s_preco').value);
    const status = document.getElementById('s_status').value;

    const payload = {
        tipos_servicos: tipo,
        descricao: descricao,
        preco: preco,
        status: status
    };

    let url = '/tcc_project/api/admin/servicos.php';
    let method = 'POST';

    if(id) {
        payload.id_servico = id;
        method = 'PUT';
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        
        if(response.ok) {
            notify.success(data.message);
            $('#modalServico').modal('hide');
            loadServices();
        } else {
            notify.error(data.message || 'Erro ao salvar serviço.');
        }
    } catch(e) {
        notify.error('Erro de conexão com o servidor.');
    }
}

async function deleteService(id) {
    if(!confirm("Tem certeza que deseja excluir este serviço? Esta ação pode apagar as reservas vinculadas.")) return;

    try {
        const response = await fetch(`/tcc_project/api/admin/servicos.php?id=${id}`, {
            method: 'DELETE'
        });
        const data = await response.json();
        
        if(response.ok) {
            notify.success(data.message);
            loadServices();
        } else {
            notify.error(data.message || 'Erro ao excluir serviço.');
        }
    } catch(e) {
        notify.error('Erro de conexão com o servidor.');
    }
}

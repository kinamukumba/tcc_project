document.addEventListener("DOMContentLoaded", function() {
    loadReservations();
});

let allReservations = [];

async function loadReservations() {
    try {
        const response = await fetch('/tcc_project/api/utente/reservas.php');
        const data = await response.json();
        
        const tableBody = document.getElementById('reservas-table-body');
        if(!tableBody) return;
        
        tableBody.innerHTML = '';
        
        if (response.ok) {
            allReservations = data;
            if (allReservations.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Você ainda não tem nenhuma reserva. <br><a href="nova-reserva.html" class="btn btn-sm btn-link">Clique aqui para fazer a primeira!</a></td></tr>';
            } else {
                allReservations.forEach(res => {
                    const row = document.createElement('tr');
                    
                    const statusClass = {
                        'pendente': 'badge-warning',
                        'aprovada': 'badge-success',
                        'rejeitada': 'badge-danger',
                        'concluida': 'badge-info'
                    }[res.status_reserva] || 'badge-secondary';

                    row.innerHTML = `
                        <td>#${res.id_reserva}</td>
                        <td>${res.servico}</td>
                        <td>${formatDate(res.data_checkin)}</td>
                        <td>${formatDate(res.data_checkout)}</td>
                        <td><strong>${res.preco_total}</strong></td>
                        <td><span class="badge ${statusClass}">${res.status_reserva.toUpperCase()}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${res.id_reserva})" title="Ver Detalhes">
                                <i class="fa fa-eye"></i>
                            </button>
                            ${res.status_reserva === 'pendente' ? `
                                <button class="btn btn-sm btn-outline-warning" onclick="changeDates(${res.id_reserva}, '${res.data_checkin}', '${res.data_checkout}')" title="Alterar Datas" style="margin-left: 2px;">
                                    <i class="fa fa-calendar"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation(${res.id_reserva})" title="Cancelar Reserva" style="margin-left: 2px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            ` : ''}
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        } else {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Erro: ${data.message || 'Erro ao carregar reservas.'}</td></tr>`;
        }
    } catch (e) {
        console.error("Erro ao carregar reservas:", e);
        const tableBody = document.getElementById('reservas-table-body');
        if(tableBody) tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Erro de conexão com o servidor.</td></tr>';
    }
}

function formatDate(dateStr) {
    if(!dateStr) return '-';
    // Corrige problema de fuso horário em strings YYYY-MM-DD
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('pt-PT');
}

function viewDetails(id) {
    const res = allReservations.find(r => r.id_reserva == id);
    if(!res) return;

    const content = `
        <div class="text-left py-2">
            <p class="mb-2"><strong>ID da Reserva:</strong> #${res.id_reserva}</p>
            <p class="mb-2"><strong>Serviço:</strong> ${res.servico}</p>
            <p class="mb-2"><strong>Hóspedes:</strong> ${res.n_pessoa} pessoa(s)</p>
            <p class="mb-2"><strong>Check-in:</strong> ${formatDate(res.data_checkin)}</p>
            <p class="mb-2"><strong>Check-out:</strong> ${formatDate(res.data_checkout)}</p>
            <p class="mb-2"><strong>Total Pago:</strong> <span class="text-success font-weight-bold">${res.preco_total}</span></p>
            <p class="mb-0"><strong>Status:</strong> <span class="badge badge-info">${res.status_reserva.toUpperCase()}</span></p>
        </div>
    `;

    notify.confirm(content, null, null); 
    // Removemos os botões de confirmação visualmente no CSS se necessário, 
    // mas por enquanto serve como um modal de visualização.
}

async function deleteReservation(id) {
    notify.confirm('Tem certeza que deseja cancelar esta reserva pendente?', async function() {
        try {
            const response = await fetch(`/tcc_project/api/utente/reservas.php?id=${id}`, {
                method: 'DELETE'
            });
            const data = await response.json();

            if (response.ok) {
                notify.success(data.message);
                loadReservations();
            } else {
                notify.error(data.message);
            }
        } catch (e) {
            notify.error('Erro ao conectar com o servidor.');
        }
    });
}

async function changeDates(id, oldCheckin, oldCheckout) {
    var checkin = prompt("Digite a nova data de chegada (AAAA-MM-DD):", oldCheckin);
    if (!checkin) return;
    
    var checkout = prompt("Digite a nova data de partida (AAAA-MM-DD):", oldCheckout);
    if (!checkout) return;

    if(new Date(checkout) <= new Date(checkin)) {
        notify.error("A data de partida deve ser posterior à data de chegada.");
        return;
    }

    try {
        const response = await fetch('/tcc_project/api/utente/reservas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_reserva: id, data_checkin: checkin, data_checkout: checkout })
        });
        const res = await response.json();
        if(response.ok) {
            notify.success(res.message);
            loadReservations();
        } else {
            notify.error(res.message);
        }
    } catch(e) {
        notify.error("Erro ao conectar com o servidor.");
    }
}

// admin-feedbacks.js

document.addEventListener("DOMContentLoaded", function() {
    loadFeedbacks();
});

async function loadFeedbacks() {
    try {
        const response = await fetch('/tcc_project/api/admin/feedbacks.php');
        const data = await response.json();
        
        const tableBody = document.getElementById('feedbacks-table-body');
        if(!tableBody) return;

        tableBody.innerHTML = '';
        
        if (response.ok) {
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Nenhum feedback recebido.</td></tr>';
            } else {
                data.forEach(fb => {
                    const row = document.createElement('tr');
                    
                    let stars = '';
                    for(let i=1; i<=5; i++) {
                        stars += `<i class="fa fa-star${i <= fb.nota ? '' : '-o'}" style="color: #d4ab04;"></i>`;
                    }

                    row.innerHTML = `
                        <td>#${fb.id_avaliação}</td>
                        <td><strong>${fb.utente}</strong></td>
                        <td><div class="stars">${stars}</div></td>
                        <td><p class="mb-0 small text-muted">${fb.comentario || 'Sem comentário.'}</p></td>
                        <td>${formatDate(fb.data_avaliação)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteFeedback(${fb.id_avaliação})" title="Remover">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar feedbacks:", e);
    }
}

async function deleteFeedback(id) {
    notify.confirm('Deseja remover este feedback permanentemente?', async function() {
        try {
            const response = await fetch(`/tcc_project/api/admin/feedbacks.php?id=${id}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            if(response.ok) {
                notify.success(data.message);
                loadFeedbacks();
            } else {
                notify.error(data.message);
            }
        } catch(e) {
            notify.error("Erro ao conectar com o servidor.");
        }
    });
}

function formatDate(dateStr) {
    if(!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-PT');
}

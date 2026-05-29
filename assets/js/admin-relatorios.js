// admin-relatorios.js

document.addEventListener("DOMContentLoaded", function() {
    loadReports();
});

async function loadReports() {
    try {
        const response = await fetch('/tcc_project/api/admin/relatorios.php');
        const data = await response.json();
        
        if (response.ok) {
            // Preencher tabela de Receita Mensal
            const receitaBody = document.getElementById('report-receita-body');
            if(receitaBody) {
                receitaBody.innerHTML = '';
                data.receita_mensal.forEach(item => {
                    const row = `<tr><td>${item.mes}</td><td>${item.valor.toLocaleString('pt-PT')} KZ</td></tr>`;
                    receitaBody.innerHTML += row;
                });
            }

            // Preencher tabela de Ocupação
            const ocupacaoBody = document.getElementById('report-ocupacao-body');
            if(ocupacaoBody) {
                ocupacaoBody.innerHTML = '';
                data.ocupacao_servicos.forEach(item => {
                    const row = `<tr><td>${item.tipos_servicos}</td><td>${item.total} reservas</td></tr>`;
                    ocupacaoBody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar relatórios:", e);
    }
}

function exportPDF() {
    notify.info("A gerar PDF... Esta função requer biblioteca adicional (jsPDF).");
}

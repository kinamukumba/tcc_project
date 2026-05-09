// auth.js
// Verifica a sessão ativa ao carregar a página
document.addEventListener("DOMContentLoaded", function() {
    checkSession();
});

async function checkSession() {
    try {
        const response = await fetch('/tcc_project/api/auth/session.php');
        const data = await response.json();
        
        const path = window.location.pathname;
        const isAuthPage = path.includes('/auth/login.html') || path.includes('/auth/register.html');
        
        if(response.ok && data.authenticated) {
            // Se estiver na página de login e já estiver logado, redireciona
            if(isAuthPage) {
                redirectUser(data.user.role);
            }
            
            // Controle de acesso baseado em role
            if(path.includes('/utente') && data.user.role !== 'utente') {
                window.location.href = '/tcc_project/auth/login.html';
            } else if (path.includes('/admin') && data.user.role !== 'admin') {
                window.location.href = '/tcc_project/auth/login.html';
            } else if (path.includes('/gerente') && data.user.role !== 'gerente') {
                window.location.href = '/tcc_project/auth/login.html';
            }

            // Popula os dados do usuário na interface
            const userNameElements = document.querySelectorAll('.user-name-display');
            userNameElements.forEach(el => el.textContent = data.user.nome);
            
            const userRoleElements = document.querySelectorAll('.user-role-display');
            userRoleElements.forEach(el => el.textContent = data.user.role.toUpperCase());
            
        } else {
            // Se não estiver logado e não estiver nas páginas publicas ou de auth, manda pro login
            if(!isAuthPage && (path.includes('/utente') || path.includes('/admin') || path.includes('/gerente'))) {
                window.location.href = '/tcc_project/auth/login.html';
            }
        }
    } catch(error) {
        console.error("Erro ao verificar sessão:", error);
    }
}

function redirectUser(role) {
    if(role === 'utente') {
        window.location.href = '/tcc_project/utente/index.html';
    } else if (role === 'admin') {
        window.location.href = '/tcc_project/admin/index.html';
    } else if (role === 'gerente') {
        window.location.href = '/tcc_project/gerente/index.html';
    }
}

async function logout() {
    try {
        const response = await fetch('/tcc_project/api/auth/logout.php');
        if(response.ok) {
            window.location.href = '/tcc_project/auth/login.html';
        }
    } catch(error) {
        console.error("Erro no logout:", error);
    }
}

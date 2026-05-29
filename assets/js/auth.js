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
        // Definimos quais páginas são consideradas "Páginas de Login/Registo"
        const isUtenteAuthPage = path.includes('/auth/login.html') || path.includes('/auth/register.html');
        const isAdminAuthPage = path.includes('/admin/index.html');
        const isAuthPage = isUtenteAuthPage || isAdminAuthPage;
        
        if(response.ok && data.authenticated) {
            const userRole = data.user.role;

            // 1. Se o utilizador já está logado e tenta aceder a uma página de login, redireciona para o seu dashboard
            if(isAuthPage) {
                // EXCEPÇÃO: Se eu for utente e estiver na página de login do ADMIN, 
                // talvez eu queira trocar de conta? Mas por padrão, redirecionamos para o dashboard atual.
                redirectUser(userRole);
                return;
            }
            
            // 2. Proteção de Rotas: Verifica se o utilizador tem permissão para estar na pasta atual
            if(path.includes('/utente/') && userRole !== 'utente') {
                window.location.href = '/tcc_project/auth/login.html';
            } 
            else if (path.includes('/admin/') && userRole !== 'admin') {
                // Se tentar entrar no dashboard admin sem ser admin, manda para o login do admin
                window.location.href = '/tcc_project/admin/index.html';
            } 
            else if (path.includes('/gerente/') && userRole !== 'gerente') {
                window.location.href = '/tcc_project/auth/login.html';
            }

            // 3. Popula os dados do usuário na interface
            const userNameElements = document.querySelectorAll('.user-name-display');
            userNameElements.forEach(el => el.textContent = data.user.nome);
            
            const userRoleElements = document.querySelectorAll('.user-role-display');
            userRoleElements.forEach(el => el.textContent = data.user.role.toUpperCase());
            
        } else {
            // 4. Caso NÃO esteja autenticado
            // Se tentar aceder a uma pasta restrita, redireciona para o login correspondente
            if(!isAuthPage) {
                if(path.includes('/admin/')) {
                    window.location.href = '/tcc_project/admin/index.html';
                } 
                else if (path.includes('/utente/') || path.includes('/gerente/')) {
                    window.location.href = '/tcc_project/auth/login.html';
                }
                // Se for apenas "/admin" ou "/utente" (sem a barra final)
                else if (path.endsWith('/admin')) {
                    window.location.href = '/tcc_project/admin/index.html';
                }
                else if (path.endsWith('/utente')) {
                    window.location.href = '/tcc_project/auth/login.html';
                }
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
        window.location.href = '/tcc_project/admin/dashboard.html';
    } else if (role === 'gerente') {
        window.location.href = '/tcc_project/gerente/index.html';
    }
}

async function logout() {
    try {
        const path = window.location.pathname;
        const response = await fetch('/tcc_project/api/auth/logout.php');
        if(response.ok) {
            if(path.includes('/admin')) {
                window.location.href = '/tcc_project/admin/index.html';
            } else {
                window.location.href = '/tcc_project/auth/login.html';
            }
        }
    } catch(error) {
        console.error("Erro no logout:", error);
    }
}

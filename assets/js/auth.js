// auth.js — Gestão de sessão e protecção de rotas
document.addEventListener("DOMContentLoaded", function () {
    checkSession();
});

async function checkSession() {
    try {
        const response = await fetch('/tcc_project/api/auth/session.php');
        const data = await response.json();

        const path = window.location.pathname;

        // Páginas de autenticação (não protegidas)
        const isAdminLoginPage = path.includes('/admin/index.html');
        const isPublicAuthPage = path.includes('/auth/login.html') || path.includes('/auth/register.html');
        const isAuthPage = isAdminLoginPage || isPublicAuthPage;

        if (response.ok && data.authenticated) {
            const userRole = data.user.role;

            // Se já logado e está numa página de login → redireciona para o dashboard
            if (isAuthPage) {
                redirectUser(userRole);
                return;
            }

            // Protecção de rotas por pasta
            if (path.includes('/admin/') && userRole !== 'admin') {
                window.location.href = '/tcc_project/admin/index.html';
                return;
            }
            if (path.includes('/gerente/') && userRole !== 'gerente') {
                window.location.href = '/tcc_project/auth/login.html';
                return;
            }
            if (path.includes('/utente/') && userRole !== 'utente') {
                window.location.href = '/tcc_project/auth/login.html';
                return;
            }
            if (path.includes('/recepcionista/') && userRole !== 'recepcionista') {
                window.location.href = '/tcc_project/auth/login.html';
                return;
            }

            // Popula os dados do utilizador na UI
            document.querySelectorAll('.user-name-display').forEach(el => el.textContent = data.user.nome);
            document.querySelectorAll('.user-role-display').forEach(el => el.textContent = data.user.role.toUpperCase());

        } else {
            // Não autenticado — redireciona para o login correcto
            if (!isAuthPage) {
                if (path.includes('/admin/')) {
                    window.location.href = '/tcc_project/admin/index.html';
                } else if (
                    path.includes('/utente/') ||
                    path.includes('/gerente/') ||
                    path.includes('/recepcionista/')
                ) {
                    window.location.href = '/tcc_project/auth/login.html';
                }
            }
        }
    } catch (error) {
        console.error("Erro ao verificar sessão:", error);
    }
}

function redirectUser(role) {
    switch (role) {
        case 'admin':
            window.location.href = '/tcc_project/admin/dashboard.html';
            break;
        case 'gerente':
            window.location.href = '/tcc_project/gerente/index.html';
            break;
        case 'recepcionista':
            window.location.href = '/tcc_project/recepcionista/index.html';
            break;
        case 'utente':
            window.location.href = '/tcc_project/utente/index.html';
            break;
        default:
            window.location.href = '/tcc_project/auth/login.html';
    }
}

async function logout() {
    try {
        const path = window.location.pathname;
        const response = await fetch('/tcc_project/api/auth/logout.php');
        if (response.ok) {
            if (path.includes('/admin/')) {
                window.location.href = '/tcc_project/admin/index.html';
            } else {
                window.location.href = '/tcc_project/auth/login.html';
            }
        }
    } catch (error) {
        console.error("Erro no logout:", error);
    }
}

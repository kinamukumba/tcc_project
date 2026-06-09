// auth.js - Gestao de sessao e proteccao de rotas
document.addEventListener("DOMContentLoaded", function () {
    checkSession();
});

async function checkSession() {
    try {
        var response = await fetch('/tcc_project/api/auth/session.php');
        var data = await response.json();
        var path = window.location.pathname;

        // Paginas de login (publicas - nao protegidas)
        var isAdminLogin  = path.includes('/admin/index.html');
        var isGerenteLogin = path.includes('/gerente/index.html');
        var isRecepLogin  = path.includes('/recepcionista/index.html');
        var isPublicAuth  = path.includes('/auth/login.html') || path.includes('/auth/register.html');
        var isLoginPage   = isAdminLogin || isGerenteLogin || isRecepLogin || isPublicAuth;

        if (response.ok && data.authenticated) {
            var userRole = data.user.role;

            // Ja autenticado numa pagina de login -> redireciona para dashboard
            if (isLoginPage) {
                redirectUser(userRole);
                return;
            }

            // Proteccao de rotas: verificar se o role do utilizador coincide com a pasta
            if (path.includes('/admin/') && userRole !== 'admin') {
                window.location.href = '/tcc_project/admin/index.html';
                return;
            }
            if (path.includes('/gerente/') && userRole !== 'gerente') {
                window.location.href = '/tcc_project/gerente/index.html';
                return;
            }
            if (path.includes('/recepcionista/') && userRole !== 'recepcionista') {
                window.location.href = '/tcc_project/recepcionista/index.html';
                return;
            }
            if (path.includes('/utente/') && userRole !== 'utente') {
                window.location.href = '/tcc_project/auth/login.html';
                return;
            }

            // Popula dados do utilizador na UI
            document.querySelectorAll('.user-name-display').forEach(function(el) {
                el.textContent = data.user.nome;
            });
            document.querySelectorAll('.user-role-display').forEach(function(el) {
                el.textContent = data.user.role.toUpperCase();
            });

        } else {
            // Nao autenticado - redireciona para login correcto
            if (!isLoginPage) {
                if (path.includes('/admin/')) {
                    window.location.href = '/tcc_project/admin/index.html';
                } else if (path.includes('/gerente/')) {
                    window.location.href = '/tcc_project/gerente/index.html';
                } else if (path.includes('/recepcionista/')) {
                    window.location.href = '/tcc_project/recepcionista/index.html';
                } else if (path.includes('/utente/')) {
                    window.location.href = '/tcc_project/auth/login.html';
                }
            }
        }
    } catch (error) {
        console.error("Erro ao verificar sessao:", error);
    }
}

function redirectUser(role) {
    switch (role) {
        case 'admin':
            window.location.href = '/tcc_project/admin/dashboard.html';
            break;
        case 'gerente':
            window.location.href = '/tcc_project/gerente/dashboard.html';
            break;
        case 'recepcionista':
            window.location.href = '/tcc_project/recepcionista/dashboard.html';
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
        var path = window.location.pathname;
        await fetch('/tcc_project/api/auth/logout.php');
        if (path.includes('/admin/')) {
            window.location.href = '/tcc_project/admin/index.html';
        } else if (path.includes('/gerente/')) {
            window.location.href = '/tcc_project/gerente/index.html';
        } else if (path.includes('/recepcionista/')) {
            window.location.href = '/tcc_project/recepcionista/index.html';
        } else {
            window.location.href = '/tcc_project/auth/login.html';
        }
    } catch (error) {
        console.error("Erro no logout:", error);
    }
}
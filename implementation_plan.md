# Reestruturação e Implementação do Sistema (Utente, Admin, Gerente)

Este documento detalha o plano de implementação para organizar, limpar e desenvolver as novas funcionalidades do projeto, seguindo as melhores práticas e utilizando HTML/CSS/JS no frontend e PHP (PDO) no backend com comunicação via API (Fetch).

## ⚠️ User Review Required

Por favor, revise os seguintes pontos antes de prosseguirmos com a execução:
1. **Arquivos a serem deletados**: Planejo deletar arquivos do site institucional que parecem não ter utilidade para o fluxo principal agora (ex: `blog.html`, `blog-single.html`, `gallery.html`, `checkin.html`). Você aprova esta exclusão?
2. **Estrutura de Banco de Dados**: Abaixo apresento uma proposta inicial para as tabelas do banco de dados MySQL. Verifique se atende a todas as necessidades.

## Open Questions

- Qual é o nome do banco de dados que devemos utilizar para a configuração do PDO?
- Você tem alguma preferência de estilo (CSS) específico para o painel do Gerente, ou devo seguir a mesma identidade visual dos painéis de Admin e Utente já existentes?

## Proposed Changes

### 1. Limpeza e Organização de Arquivos (`Assets` e Raiz)

Vamos limpar a raiz do projeto e unificar os recursos estáticos.

#### [NEW] `assets/` (Diretório)
- Moveremos as pastas `css/`, `fonts/`, `image/`, `js/`, `scss/` e `vendors/` para dentro de uma nova pasta `assets/` para manter a raiz limpa.

#### [DELETE] Arquivos Desnecessários
- `blog.html`
- `blog-single.html`
- `gallery.html`
- `checkin.html`
- (Outros que você confirmar que não serão usados)

---

### 2. Criação do Banco de Dados (SQL)

Criação de um arquivo `database.sql` na raiz para configurar o banco de dados.

#### [NEW] `database.sql`
Tabelas propostas:
- `usuarios` (id, nome, email, senha, tipo_perfil ENUM('utente', 'admin', 'gerente'), data_criacao)
- `reservas` (id, usuario_id, data_entrada, data_saida, status ENUM('pendente', 'aprovada', 'rejeitada', 'concluida'), valor_total, data_reserva)
- `feedbacks` (id, reserva_id, nota, comentario, data_feedback)
- `mensagens` (id, remetente_id, destinatario_id, conteudo, lida BOOLEAN, data_envio)

---

### 3. Backend - Estrutura de APIs PHP (PDO)

Criaremos uma pasta `api/` para concentrar todo o backend. Os arquivos retornarão exclusivamente JSON.

#### [NEW] `api/config/database.php`
- Conexão PDO segura com o banco de dados MySQL.

#### [NEW] `api/auth/`
- `login.php`: Validação de credenciais e inicialização de sessão PHP.
- `register.php`: Cadastro de novos utentes.
- `session.php`: Endpoint para o JS verificar quem está logado em todas as páginas.
- `logout.php`: Encerramento da sessão.

#### [NEW] `api/utente/`
- `reservas.php`: Criar e listar reservas do próprio usuário.
- `perfil.php`: Ler e atualizar dados do perfil.
- `feedback.php`: Enviar feedback de uma reserva.

#### [NEW] `api/admin/`
- `dashboard_stats.php`: Total de reservas, utentes, receita.
- `gerenciar_reservas.php`: Listar todas e alterar status (aprovar, rejeitar).
- `gerenciar_utentes.php`: CRUD de usuários tipo 'utente'.
- `relatorios.php`: Geração de dados para gráficos e exportação.

#### [NEW] `api/gerente/`
- `dashboard_stats.php`: Receitas totais, operações do sistema.
- `credenciais_admin.php`: Criar novos usuários com perfil 'admin'.

#### [NEW] `api/mensagens/`
- `chat.php`: Enviar e receber mensagens (Utente <-> Admin).

---

### 4. Frontend - Lógica e Painéis (HTML/JS)

Iremos adaptar as páginas HTML atuais para consumirem as APIs criadas e criar o painel do Gerente.

#### [MODIFY] Páginas em `/utente` e `/admin`
- Atualizar os links de CSS/JS para apontar para `/assets/...`.
- Injetar o JavaScript que fará `fetch()` para `api/auth/session.php` para garantir que a página só seja acessada por usuários logados (redirecionando para login caso contrário) e para carregar os dados no header/sidebar.
- Conectar os formulários e tabelas com as respectivas APIs via `fetch()`.

#### [NEW] `/gerente/` (Diretório e Páginas)
- `index.html`: Dashboard principal com estatísticas de receita e notificações.
- `admins.html`: Página para gerenciar e criar credenciais de administradores.
- `relatorios.html`: Relatório final das operações.

## Verification Plan

### Testes Manuais
1. **Autenticação**: Cadastrar um Utente, logar com ele, tentar acessar páginas de Admin/Gerente (deve ser bloqueado). Logar como Admin e Gerente e testar os acessos.
2. **Fluxo de Reserva**: 
   - Utente faz uma reserva.
   - Admin vê a reserva e altera o status para 'aprovada'.
   - Gerente recebe notificação visual no dashboard do aumento de receita/reservas aprovadas.
   - Utente vê o status atualizado e deixa feedback.
3. **Comunicação**: Enviar mensagem do Utente para o Admin e vice-versa, verificando se aparece nas respectivas caixas de entrada.
4. **Gerência**: Criar um novo Admin logado como Gerente. Testar o login com o novo Admin.

### Banco de Dados
- Verificar se as queries PDO estão utilizando `prepared statements` para evitar SQL Injection.

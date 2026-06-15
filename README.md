# 🎫 HelpDesk - Sistema de Chamados Técnicos

Sistema web de gerenciamento de chamados técnicos com controle de acesso RBAC, notificações por e-mail e esteira CI/CD.

**Stack:** PHP 8.2 · Symfony 7 · Doctrine ORM · Bootstrap 5 · MySQL 8 · Docker

## 🚀 Início Rápido

### Pré-requisitos
- Docker e Docker Compose
- Git

### Setup

```bash
# Clonar repositório
git clone <seu-repo> && cd helpdesk

# Copiar variáveis de ambiente (já configuradas)
cp .env.example .env

# Subir containers
make build

# Instalar dependências Composer
make composer-install

# Criar banco e rodar migrações
make db-reset

# Acessar a aplicação
# http://localhost:8080
# E-mails capturados em: http://localhost:8025
```

### Credenciais de Teste

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | admin@helpdesk.local | admin123 |
| Técnico | tech@helpdesk.local | tech123 |
| Usuário | user@helpdesk.local | user123 |

## 📋 Funcionalidades Implementadas (Fase 1)

- [x] Autenticação com e-mail e senha
- [x] Controle de acesso por roles (Admin, Técnico, Usuário)
- [x] Layout responsivo com Bootstrap 5
- [x] Entidades base (User, Ticket, Category, Comment)
- [x] Dashboard inicial

## 📚 Comandos Úteis

```bash
make up              # Subir containers
make down            # Parar containers
make logs            # Ver logs em tempo real
make bash            # Acessar terminal do PHP
make migrate         # Executar migrações
make fixtures        # Carregar dados de teste
make db-reset        # Resetar banco (cria do zero)
make cs-fix          # Formatar código
make phpstan         # Análise estática
make test            # Rodar testes
```

## 🛣️ Roadmap

### Fase 2 - Módulo de Chamados
- CRUD de chamados
- Listagem com filtros
- Atribuição de técnico
- Status e prioridade

### Fase 3 - Notificações
- E-mails automáticos
- Webhooks (opcional)

### Fase 4 - Painel Admin
- Gerenciamento de usuários
- Gerenciamento de categorias
- Dashboard com métricas

### Fase 5 - Qualidade
- Testes automatizados
- CI/CD com GitHub Actions

### Fase 6 - Deploy
- Polimento UI
- Deploy em produção

## 📖 Documentação

Ver `PLAN.md` para detalhes completos do projeto e arquitetura.

## 📝 Licença

MIT

# 🎫 Plano de Desenvolvimento — Sistema de Chamados Técnicos (HelpDesk)

> **Stack:** PHP 8.2+ · Symfony 7 · Doctrine ORM · Bootstrap 5 · MySQL 8 · Docker  
> **Objetivo:** Portfólio com controle de acesso RBAC, notificações por e-mail, ambiente Docker e esteira CI/CD

---

## 📋 Índice

1. [Visão Geral do Projeto](#visão-geral)
2. [Arquitetura e Estrutura de Pastas](#arquitetura)
3. [Banco de Dados e Entidades](#banco-de-dados)
4. [Controle de Acesso (RBAC)](#controle-de-acesso)
5. [Funcionalidades por Módulo](#funcionalidades)
6. [Notificações por E-mail](#notificações)
7. [Interface (Bootstrap 5)](#interface)
8. [CI/CD com GitHub Actions](#cicd)
9. [Ambiente Docker](#docker)
10. [Fases de Desenvolvimento](#fases)
11. [Checklist Final](#checklist)

---

## 1. Visão Geral do Projeto <a name="visão-geral"></a>

O **HelpDesk** é um sistema web de gerenciamento de chamados técnicos com três perfis de acesso:

| Perfil       | Permissões principais                                             |
|--------------|-------------------------------------------------------------------|
| **Admin**    | Gerencia usuários, categorias, relatórios e todos os chamados     |
| **Técnico**  | Visualiza, assume e atualiza o status dos chamados atribuídos     |
| **Usuário**  | Abre chamados, acompanha o status e adicipa comentários           |

**Fluxo de status de um chamado:**

```
Aberto → Em Atendimento → Aguardando Usuário → Resolvido → Fechado
                                    ↑___________|
```

---

## 2. Arquitetura e Estrutura de Pastas <a name="arquitetura"></a>

```
helpdesk/
├── .github/
│   └── workflows/
│       └── ci.yml                  # Esteira GitHub Actions
├── docker/
│   ├── nginx/
│   │   └── default.conf            # Configuração do servidor Nginx
│   └── php/
│       └── php.ini                 # Customizações do PHP
├── assets/
│   ├── styles/
│   │   └── app.css
│   └── bootstrap.js
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── mailer.yaml
│   │   └── security.yaml           # Configuração RBAC
│   └── routes.yaml
├── migrations/                     # Migrações do Doctrine
├── src/
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── UserController.php
│   │   │   └── CategoryController.php
│   │   ├── Ticket/
│   │   │   ├── TicketController.php
│   │   │   └── CommentController.php
│   │   └── SecurityController.php  # Login / Logout
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Ticket.php
│   │   ├── Comment.php
│   │   └── Category.php
│   ├── Enum/
│   │   ├── TicketStatus.php        # PHP 8.1 Enum
│   │   └── TicketPriority.php
│   ├── Form/
│   │   ├── TicketType.php
│   │   ├── CommentType.php
│   │   └── UserType.php
│   ├── Repository/
│   │   ├── TicketRepository.php
│   │   └── UserRepository.php
│   ├── EventListener/
│   │   └── TicketStatusListener.php # Dispara e-mail ao mudar status
│   └── Service/
│       └── MailerService.php
├── templates/
│   ├── base.html.twig
│   ├── security/
│   │   └── login.html.twig
│   ├── admin/
│   │   ├── dashboard.html.twig
│   │   └── users/
│   ├── ticket/
│   │   ├── list.html.twig
│   │   ├── show.html.twig
│   │   └── new.html.twig
│   └── email/
│       └── ticket_status_changed.html.twig
├── tests/
│   ├── Unit/
│   └── Functional/
├── .env
├── .env.test
├── .php-cs-fixer.php               # Config do PHP CS Fixer
├── phpstan.neon                    # Config do PHPStan
├── Dockerfile                      # Imagem PHP-FPM customizada
├── docker-compose.yml              # Orquestração dos serviços
├── Makefile                        # Atalhos de comandos Docker
└── composer.json
```

---

## 3. Banco de Dados e Entidades <a name="banco-de-dados"></a>

### Diagrama de Entidades (ERD simplificado)

```
┌─────────────┐       ┌──────────────────┐       ┌─────────────┐
│    users    │       │     tickets      │       │  categories │
│─────────────│       │──────────────────│       │─────────────│
│ id (PK)     │──1──┐ │ id (PK)          │ ┌──FK─│ id (PK)     │
│ name        │     ├─│ requester_id(FK) │ │     │ name        │
│ email       │     │ │ technician_id(FK)│ │     │ description │
│ password    │     └─│ category_id (FK) │─┘     └─────────────┘
│ roles (JSON)│       │ title            │
└─────────────┘       │ description      │       ┌─────────────┐
                      │ status (enum)    │       │  comments   │
                      │ priority (enum)  │       │─────────────│
                      │ created_at       │──1───<│ id (PK)     │
                      │ updated_at       │       │ ticket_id   │
                      └──────────────────┘       │ author_id   │
                                                 │ content     │
                                                 │ created_at  │
                                                 └─────────────┘
```

### Entidade `Ticket` — campos e Enum de status

```php
// src/Enum/TicketStatus.php
enum TicketStatus: string
{
    case Open            = 'open';
    case InProgress      = 'in_progress';
    case WaitingUser     = 'waiting_user';
    case Resolved        = 'resolved';
    case Closed          = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open         => 'Aberto',
            self::InProgress   => 'Em Atendimento',
            self::WaitingUser  => 'Aguardando Usuário',
            self::Resolved     => 'Resolvido',
            self::Closed       => 'Fechado',
        };
    }
}
```

### Comandos Doctrine essenciais

```bash
# Criar a entidade interativamente
php bin/console make:entity Ticket

# Gerar migração após alterar entidades
php bin/console doctrine:migrations:diff

# Executar migrações
php bin/console doctrine:migrations:migrate

# Verificar status das migrações
php bin/console doctrine:migrations:status
```

---

## 4. Controle de Acesso (RBAC) <a name="controle-de-acesso"></a>

### Hierarquia de roles (`config/packages/security.yaml`)

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN:    [ROLE_TECHNICIAN, ROLE_USER]
        ROLE_TECHNICIAN: [ROLE_USER]

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                default_target_path: app_dashboard
            logout:
                path: app_logout

    access_control:
        - { path: ^/admin,     roles: ROLE_ADMIN }
        - { path: ^/ticket/manage, roles: ROLE_TECHNICIAN }
        - { path: ^/ticket,    roles: ROLE_USER }
        - { path: ^/login,     roles: PUBLIC_ACCESS }
```

### Proteção adicional nos Controllers

```php
// Nos controllers, verificação programática:
$this->denyAccessUnlessGranted('ROLE_ADMIN');

// Ou via atributo PHP 8:
#[IsGranted('ROLE_TECHNICIAN')]
public function assignTicket(Ticket $ticket): Response { ... }
```

### Voter customizado (exemplo — editar chamado)

Para lógica mais fina (ex: "técnico só pode editar chamados atribuídos a ele"), crie um **Voter**:

```bash
php bin/console make:voter TicketVoter
```

O Voter centraliza as regras de autorização e mantém os controllers limpos.

---

## 5. Funcionalidades por Módulo <a name="funcionalidades"></a>

### 5.1 Módulo de Autenticação

- [x] Login com e-mail e senha (formulário nativo do Symfony)
- [x] Logout
- [x] Redirecionamento baseado na role após login
- [x] Proteção de rotas via `access_control`

**Comando de scaffold:**
```bash
php bin/console make:auth          # Gera SecurityController e LoginForm
php bin/console make:user          # Gera a entidade User com hash de senha
```

### 5.2 Módulo de Chamados (Tickets)

| Ação                         | Role mínima  | Descrição                                  |
|------------------------------|--------------|--------------------------------------------|
| Abrir chamado                | ROLE_USER    | Formulário com título, descrição, categoria|
| Listar meus chamados         | ROLE_USER    | Filtro por status                          |
| Ver detalhes + comentários   | ROLE_USER    | Timeline de atualizações                   |
| Listar todos os chamados     | ROLE_TECHNICIAN | Com filtros avançados                   |
| Assumir / Reatribuir chamado | ROLE_TECHNICIAN | Atribuição de técnico responsável       |
| Alterar status               | ROLE_TECHNICIAN | Dispara notificação por e-mail          |
| Editar / Excluir qualquer    | ROLE_ADMIN   | Gestão completa                            |

### 5.3 Módulo de Comentários

- Usuário e Técnico podem adicionar comentários em um chamado
- Comentários exibidos em ordem cronológica (estilo chat/timeline)
- Admin pode excluir comentários inadequados

### 5.4 Módulo Admin

- CRUD completo de usuários (com atribuição de roles)
- CRUD de categorias de chamados
- Dashboard com métricas:
  - Total de chamados por status (gráfico simples com CSS ou Chart.js)
  - Chamados abertos há mais de X dias (SLA)
  - Técnicos com maior volume de atendimento

---

## 6. Notificações por E-mail <a name="notificações"></a>

### Configuração do Symfony Mailer

```bash
# Instalar o componente
composer require symfony/mailer

# Para testes em dev, usar o Mailtrap ou MailHog
# .env
MAILER_DSN=smtp://username:password@sandbox.smtp.mailtrap.io:2525
```

### Estratégia de envio com Event Listener

Ao invés de acoplar o envio de e-mail diretamente no controller, utilize um **Event Listener** que reage ao salvamento da entidade `Ticket`:

```php
// src/EventListener/TicketStatusListener.php
class TicketStatusListener
{
    public function __construct(private MailerService $mailer) {}

    #[AsEntityListener(event: Events::preUpdate, entity: Ticket::class)]
    public function preUpdate(Ticket $ticket, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('status')) {
            $this->mailer->sendStatusChangedNotification($ticket);
        }
    }
}
```

### Template de e-mail

```twig
{# templates/email/ticket_status_changed.html.twig #}
<!DOCTYPE html>
<html>
<body>
    <h2>Atualização no seu chamado #{{ ticket.id }}</h2>
    <p>Olá, <strong>{{ ticket.requester.name }}</strong>!</p>
    <p>
        O status do seu chamado <strong>"{{ ticket.title }}"</strong>
        foi atualizado para: <strong>{{ ticket.status.label() }}</strong>
    </p>
    <a href="{{ url('app_ticket_show', {id: ticket.id}) }}">
        Ver chamado
    </a>
</body>
</html>
```

### E-mails disparados

| Evento                        | Destinatário         |
|-------------------------------|----------------------|
| Chamado aberto                | Admin + Técnicos     |
| Status alterado               | Solicitante          |
| Técnico atribuído ao chamado  | Técnico responsável  |
| Chamado resolvido/fechado     | Solicitante          |

---

## 7. Interface com Bootstrap 5 <a name="interface"></a>

### Instalação via Webpack Encore

```bash
composer require symfony/webpack-encore-bundle
npm install bootstrap @popperjs/core
```

Em `assets/app.js`:
```javascript
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
```

### Layout base (`templates/base.html.twig`)

O layout deve incluir:
- Navbar responsiva com nome do usuário e botão de logout
- Menu lateral (sidebar) diferente por role
- Área de flash messages (sucesso/erro/aviso) com alertas Bootstrap
- Footer simples

### Telas previstas

| Tela                     | Rota sugerida              |
|--------------------------|----------------------------|
| Login                    | `/login`                   |
| Dashboard (role-aware)   | `/dashboard`               |
| Novo chamado             | `/ticket/new`              |
| Lista de chamados        | `/ticket`                  |
| Detalhes do chamado      | `/ticket/{id}`             |
| Admin — Usuários         | `/admin/users`             |
| Admin — Categorias       | `/admin/categories`        |
| Admin — Dashboard geral  | `/admin/dashboard`         |

---

## 8. CI/CD com GitHub Actions <a name="cicd"></a>

### Arquivo `.github/workflows/ci.yml`

```yaml
name: CI — HelpDesk Symfony

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  quality:
    name: Code Quality
    runs-on: ubuntu-latest

    steps:
      - name: Checkout código
        uses: actions/checkout@v4

      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: intl, pdo_mysql, mbstring
          coverage: none

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}

      - name: Instalar dependências
        run: composer install --prefer-dist --no-progress

      - name: PHP CS Fixer (verificar formatação)
        run: vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.php

      - name: PHPStan (análise estática nível 6)
        run: vendor/bin/phpstan analyse src tests --level=6

  tests:
    name: Testes Automatizados
    runs-on: ubuntu-latest
    needs: quality

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: helpdesk_test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: intl, pdo_mysql, mbstring

      - name: Instalar dependências
        run: composer install --prefer-dist --no-progress

      - name: Criar banco de testes e executar migrações
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/helpdesk_test
        run: |
          php bin/console doctrine:migrations:migrate --no-interaction --env=test
          php bin/console doctrine:fixtures:load --no-interaction --env=test

      - name: Executar testes (PHPUnit)
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/helpdesk_test
        run: php bin/phpunit --testdox
```

### Configuração do PHP CS Fixer (`.php-cs-fixer.php`)

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'                   => true,
        '@PHP82Migration'            => true,
        'array_syntax'               => ['syntax' => 'short'],
        'ordered_imports'            => true,
        'no_unused_imports'          => true,
        'declare_strict_types'       => true,
    ])
    ->setFinder($finder);
```

### Configuração do PHPStan (`phpstan.neon`)

```neon
parameters:
    level: 6
    paths:
        - src
        - tests
    symfony:
        containerXmlPath: var/cache/dev/App_KernelDevDebugContainer.xml
```

---

## 9. Ambiente Docker <a name="docker"></a>

O ambiente é composto por **quatro serviços** orquestrados via Docker Compose:

| Serviço      | Imagem base          | Porta local | Função                              |
|--------------|----------------------|-------------|-------------------------------------|
| **php**      | `php:8.2-fpm`        | —           | Executa o PHP-FPM                   |
| **nginx**    | `nginx:1.25-alpine`  | `8080:80`   | Servidor web / proxy reverso        |
| **mysql**    | `mysql:8.0`          | `3306:3306` | Banco de dados principal            |
| **mailhog**  | `mailhog/mailhog`    | `8025:8025` | Captura e-mails em dev (UI web)     |

```
Navegador :8080 → nginx → php-fpm (socket) → Symfony
                               └──────────────→ mysql :3306
                                                mailhog :8025
```

---

### 9.1 Dockerfile (`Dockerfile`)

```dockerfile
FROM php:8.2-fpm

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libicu-dev libpng-dev \
    libonig-dev libxml2-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensões PHP necessárias para o Symfony
RUN docker-php-ext-install \
    intl pdo pdo_mysql mbstring \
    xml zip opcache

# Configuração do OPcache para desenvolvimento
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.validate_timestamps=1\n\
opcache.revalidate_freq=0" \
    >> /usr/local/etc/php/conf.d/opcache.ini

# Composer (via imagem oficial)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# Permissões para os diretórios do Symfony
RUN chown -R www-data:www-data var/ && chmod -R 775 var/

EXPOSE 9000
CMD ["php-fpm"]
```

---

### 9.2 docker-compose.yml

```yaml
services:

  php:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: helpdesk_php
    volumes:
      - .:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    depends_on:
      mysql:
        condition: service_healthy
    networks:
      - helpdesk_net

  nginx:
    image: nginx:1.25-alpine
    container_name: helpdesk_nginx
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php
    networks:
      - helpdesk_net

  mysql:
    image: mysql:8.0
    container_name: helpdesk_mysql
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: helpdesk
      MYSQL_USER: helpdesk
      MYSQL_PASSWORD: helpdesk
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-uroot", "-proot"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - helpdesk_net

  mailhog:
    image: mailhog/mailhog
    container_name: helpdesk_mailhog
    ports:
      - "1025:1025"   # SMTP
      - "8025:8025"   # Interface web
    networks:
      - helpdesk_net

volumes:
  mysql_data:

networks:
  helpdesk_net:
    driver: bridge
```

---

### 9.3 Nginx (`docker/nginx/default.conf`)

```nginx
server {
    listen 80;
    server_name localhost;

    root /var/www/html/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/helpdesk_access.log;
    error_log  /var/log/nginx/helpdesk_error.log;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass   php:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

### 9.4 PHP customizado (`docker/php/php.ini`)

```ini
; Configurações úteis para desenvolvimento
display_errors = On
error_reporting = E_ALL
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 120
date.timezone = America/Sao_Paulo
```

---

### 9.5 Variáveis de Ambiente (`.env`)

Com o Docker, o host dos serviços é o **nome do container** definido no `docker-compose.yml`:

```dotenv
# Banco de dados — aponta para o serviço "mysql" do Compose
DATABASE_URL="mysql://helpdesk:helpdesk@mysql:3306/helpdesk?serverVersion=8.0&charset=utf8mb4"

# MailHog — porta SMTP do serviço "mailhog"
MAILER_DSN=smtp://mailhog:1025

# Ambiente
APP_ENV=dev
APP_SECRET=sua_chave_secreta_aqui
```

> ⚠️ Nunca versione o `.env` com credenciais reais. Mantenha um `.env.example` no repositório e adicione `.env` ao `.gitignore`.

---

### 9.6 Makefile — Atalhos do dia a dia

O `Makefile` centraliza os comandos mais usados, evitando digitar `docker compose exec php ...` repetidamente:

```makefile
# Makefile
DOCKER_PHP = docker compose exec php

## ─── Ambiente ────────────────────────────────────────────────────
up:           ## Sobe todos os serviços em background
	docker compose up -d

down:         ## Derruba todos os serviços
	docker compose down

build:        ## Reconstrói as imagens (após alterar o Dockerfile)
	docker compose up -d --build

logs:         ## Acompanha os logs em tempo real
	docker compose logs -f

## ─── Symfony / PHP ───────────────────────────────────────────────
bash:         ## Abre um terminal dentro do container PHP
	$(DOCKER_PHP) bash

composer-install: ## Instala dependências
	$(DOCKER_PHP) composer install

migrate:      ## Executa as migrações do banco
	$(DOCKER_PHP) php bin/console doctrine:migrations:migrate --no-interaction

fixtures:     ## Carrega os dados de demonstração
	$(DOCKER_PHP) php bin/console doctrine:fixtures:load --no-interaction

cache-clear:  ## Limpa o cache do Symfony
	$(DOCKER_PHP) php bin/console cache:clear

## ─── Qualidade de Código ─────────────────────────────────────────
cs-fix:       ## Formata o código com PHP CS Fixer
	$(DOCKER_PHP) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php

cs-check:     ## Verifica formatação (sem alterar arquivos)
	$(DOCKER_PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.php

phpstan:      ## Análise estática com PHPStan
	$(DOCKER_PHP) vendor/bin/phpstan analyse src tests --level=6

test:         ## Executa a suíte de testes
	$(DOCKER_PHP) php bin/phpunit --testdox

## ─── Banco de Dados ──────────────────────────────────────────────
db-reset:     ## Apaga e recria o banco (dev)
	$(DOCKER_PHP) php bin/console doctrine:database:drop --force --if-exists
	$(DOCKER_PHP) php bin/console doctrine:database:create
	$(DOCKER_PHP) php bin/console doctrine:migrations:migrate --no-interaction
	$(DOCKER_PHP) php bin/console doctrine:fixtures:load --no-interaction
```

Com o Makefile, o fluxo de setup do projeto fica assim:

```bash
git clone https://github.com/seu-usuario/helpdesk.git && cd helpdesk
cp .env.example .env          # Ajustar credenciais se necessário
make build                    # Constrói e sobe os containers
make composer-install         # Instala dependências PHP
make db-reset                 # Cria banco, migra e popula com fixtures
# Acesse: http://localhost:8080
# E-mails capturados em: http://localhost:8025
```

---

### 9.7 Comandos Docker mais usados

```bash
# Subir / parar o ambiente
docker compose up -d
docker compose down

# Ver logs de um serviço específico
docker compose logs -f php
docker compose logs -f nginx

# Executar comandos dentro do container PHP
docker compose exec php bash
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/phpunit

# Inspecionar o banco via CLI (dentro do container)
docker compose exec mysql mysql -uhelpdesk -phelpdesk helpdesk

# Recriar apenas um serviço sem derrubar os outros
docker compose up -d --build php
```

---

## 10. Fases de Desenvolvimento <a name="fases"></a>

### 🏗️ Fase 1 — Fundação (3–5 dias)

**Objetivo:** Projeto rodando localmente com autenticação funcional.

- [ ] Criar projeto Symfony: `composer create-project symfony/skeleton helpdesk`
- [ ] Instalar pacotes essenciais:
  ```bash
  composer require symfony/orm-pack symfony/security-bundle \
    symfony/mailer symfony/twig-bundle symfony/form \
    symfony/webpack-encore-bundle symfony/maker-bundle --dev \
    phpunit/phpunit phpstan/phpstan \
    friendsofphp/php-cs-fixer --dev
  ```
- [ ] Configurar `.env` com `DATABASE_URL` e `MAILER_DSN`
- [ ] Criar entidade `User` com roles via `make:user`
- [ ] Implementar autenticação com `make:auth`
- [ ] Criar primeira migração e executar
- [ ] Criar `UserFixtures` com um usuário de cada role para testes

---

### 🎫 Fase 2 — Módulo de Chamados (4–6 dias)

**Objetivo:** CRUD completo de chamados com controle de acesso.

- [ ] Criar entidades: `Category`, `Ticket`, `Comment`
- [ ] Definir Enums `TicketStatus` e `TicketPriority`
- [ ] Gerar e executar migrações
- [ ] Criar formulários (`TicketType`, `CommentType`)
- [ ] Implementar `TicketController` com ações: new, show, list, edit, changeStatus
- [ ] Aplicar `access_control` e `IsGranted` nos controllers
- [ ] Criar `TicketVoter` para regras finas de autorização
- [ ] Criar templates Twig com Bootstrap para cada tela

---

### 📧 Fase 3 — Notificações (2–3 dias)

**Objetivo:** E-mails automáticos em mudanças de status.

- [ ] Criar `MailerService` com método `sendStatusChangedNotification()`
- [ ] Criar `TicketStatusListener` usando Doctrine Events
- [ ] Criar template de e-mail em HTML (`email/ticket_status_changed.html.twig`)
- [ ] Testar com Mailtrap (ambiente dev)
- [ ] Configurar `.env.test` com `MAILER_DSN=null://null` para testes

---

### 🛠️ Fase 4 — Painel Admin (2–3 dias)

**Objetivo:** Área administrativa completa.

- [ ] CRUD de usuários com atribuição de roles
- [ ] CRUD de categorias
- [ ] Dashboard com contagem de chamados por status
- [ ] Listagem de chamados em aberto há mais de N dias

---

### ✅ Fase 5 — Qualidade e CI/CD (2–3 dias)

**Objetivo:** Testes, análise estática e esteira automatizada.

- [ ] Escrever testes funcionais para fluxo de login
- [ ] Escrever testes funcionais para abertura de chamado
- [ ] Escrever testes unitários para `TicketVoter`
- [ ] Configurar PHP CS Fixer e garantir que o código está formatado
- [ ] Configurar PHPStan nível 6 e corrigir os erros reportados
- [ ] Criar `.github/workflows/ci.yml`
- [ ] Fazer push e validar que a esteira passa no GitHub

---

### 🚀 Fase 6 — Polimento e Deploy (2–4 dias)

**Objetivo:** Projeto apresentável no portfólio.

- [ ] Refinar UI (responsividade, cores, ícones com Font Awesome)
- [ ] Adicionar paginação nas listagens
- [ ] Criar `README.md` detalhado com instruções de instalação, prints e badges do CI
- [ ] Deploy em uma VPS (DigitalOcean/Hostinger) ou PaaS gratuito (Railway, Render)
- [ ] Adicionar badge `CI Passing` no README

---

## 10. Checklist Final <a name="checklist"></a>

### Pré-entrega para o portfólio

- [ ] README com: descrição, tecnologias, instruções de instalação, prints das telas e link do deploy
- [ ] Badge do GitHub Actions no README: `![CI](https://github.com/seu-usuario/helpdesk/actions/workflows/ci.yml/badge.svg)`
- [ ] `.env.example` versionado (sem credenciais reais)
- [ ] `fixtures/` com dados de demonstração (3 roles, 10 chamados de exemplo)
- [ ] Migrations todas numeradas e funcionando do zero (`migrate --no-interaction`)
- [ ] Testes passando na esteira CI
- [ ] Deploy público acessível por link

---

## 📦 Pacotes Composer Resumidos

```bash
# Produção
composer require \
  symfony/orm-pack \           # Doctrine ORM + DBAL
  symfony/security-bundle \    # Autenticação e RBAC
  symfony/mailer \             # Envio de e-mails
  symfony/twig-bundle \        # Camada de templates
  symfony/twig-extra-bundle \  # Filtros extras (intl, string…)
  symfony/form \               # Formulários
  symfony/validator \          # Validação de entidades
  symfony/webpack-encore-bundle # Assets (Bootstrap)

# Desenvolvimento / Testes
composer require --dev \
  symfony/maker-bundle \       # Scaffolding (make:entity, make:auth…)
  symfony/test-pack \          # PHPUnit + BrowserKit
  symfony/debug-bundle \       # Profiler e debug toolbar
  phpstan/phpstan \            # Análise estática
  phpstan/extension-installer \
  friendsofphp/php-cs-fixer \  # Formatação de código
  doctrine/doctrine-fixtures-bundle # Fixtures de teste
```

---

*Plano gerado em Junho de 2025 | Stack: PHP 8.2 · Symfony 7 · Doctrine ORM · Bootstrap 5*

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Youl-Coin-API** is a Symfony 6.4 + API Platform 3.4 REST API for managing a cryptocurrency wallet and transaction system with Discord OAuth authentication. It uses FrankenPHP (Go-based PHP server) and follows a Clean Architecture pattern.

**Stack**: PHP 8.2+, Symfony 6.4, API Platform 3.4, PostgreSQL 13, RabbitMQ 3, Docker Swarm

## Development Commands

### Docker Environment

The project runs via Docker Swarm using Traefik proxy. All commands are executed via Make rules inside containers.

**Stack Management**:
```bash
make docker.deploy    # Deploy the full Docker stack
make docker.stop      # Stop the stack
make docker.ps        # List running services
```

**Services**:
- `youl_coin_php` - FrankenPHP application server
- `youl_coin_message-worker` - Symfony Messenger consumers (2 replicas)
- `youl_coin_db` - PostgreSQL database
- `youl_coin_rabbitmq` - RabbitMQ message broker
- `youl_coin_adminer` - Adminer DB admin UI

**Access URLs** (local development):
- App: `https://yc.local.barlito.fr`
- RabbitMQ: `https://yc-rabbitmq.local.barlito.fr`
- Adminer: `https://yc-adminer.local.barlito.fr`

### Application Commands

**Install/Deploy**:
```bash
make deploy           # Full deploy: composer install + DB setup + migrations + fixtures
make composer.install # Install dependencies only
```

**Database**:
```bash
make doctrine.database.create      # Create database
make doctrine.migration.migrate    # Run migrations
make doctrine.fixtures.load        # Load fixtures
```

**Code Quality**:
```bash
make check_style     # Run all code style checks (phpcs + phpmd + cs-fixer dry-run)
make cs_fixer        # Fix code style issues automatically
make phpcs           # Run PHP CodeSniffer
make phpmd           # Run PHP Mess Detector
```

Code quality tools use configs from `vendor/barlito/utils/config/`:
- `.php-cs-fixer.dist.php` - PHP CS Fixer rules
- `phpcs.xml.dist` - PHP CodeSniffer rules
- `phpmd.xml` - PHP Mess Detector rules

**Testing**:
```bash
# PHPUnit (Unit + Functional tests)
make phpunit                          # Run all PHPUnit tests
make phpunit PHPUNIT_OPT="--filter=TestName"  # Run specific test
make phpunit PHPUNIT_OPT="--testsuite=Unit"   # Run only unit tests
make phpunit PHPUNIT_OPT="--testsuite=Functional"  # Run only functional tests

# Behat (BDD acceptance tests)
make behat                            # Run all Behat features
make behat BEHAT_OPT="features/Api"   # Run specific feature directory
make behat BEHAT_OPT="features/Api/transaction_post.feature"  # Run specific feature
```

**Running Single Tests**: Use the `PHPUNIT_OPT` or `BEHAT_OPT` variables to pass options.

## Architecture Overview

### Request Flow

**API Endpoints** (`/api/*`):
1. **API Platform** receives request (via JSON or form data)
2. **Entity hydration** & basic validation (Assert attributes)
3. **State Processor** (`TransactionStateProcessor`) processes the operation
4. **Handler** (`TransactionHandler`) executes business logic with locking
5. **Repository** persists to database
6. **Messenger** dispatches async notification message to RabbitMQ
7. **Worker** consumes message and sends Discord notification

**Admin Panel** (`/admin/*`):
- EasyAdmin CRUD interfaces for Wallets, ApiUsers, BankWallet management
- Discord OAuth authentication required (JWT stored in httpOnly cookies)

### Layered Architecture

```
Controller/StateProcessor → Handler → Builder/Validator → Repository → Entity
                                ↓
                           Messenger (async) → Notifier
```

**Key Layers**:
- **State Processors** (`src/State/`): API Platform entry point, delegates to handlers
- **Handlers** (`src/Service/Handler/`): Business logic, transaction management, validation
- **Builders** (`src/Service/Builder/`): Complex entity construction logic
- **Validators** (`src/Validator/`): Custom validation constraints (business rules)
- **Repositories** (`src/Repository/`): Standard Doctrine repositories (minimal custom queries)
- **Messenger** (`src/Service/Messenger/`): Async message handling (serializers, handlers, publishers)
- **Notifiers** (`src/Service/Notifier/`): External integrations (Discord webhooks)

### Core Entities

**Transaction** (`src/Entity/Transaction.php`):
- ID: UUID
- Transfers YoulCoin between wallets
- Validation: Ensures sufficient balance, valid wallet types, positive amounts
- Types: `CLASSIC` (standard transfer)
- Triggers async Discord notification via Messenger

**Wallet** (`src/Entity/Wallet.php`):
- ID: ULID
- Types: `PERSONAL` (linked to DiscordUser), `BANK` (system wallet, no user)
- Amount stored as string (minor units, e.g., cents) using brick/money
- OneToOne relationship with DiscordUser (nullable for BANK wallets)

**DiscordUser** (`src/Entity/DiscordUser.php`):
- ID: Discord's user ID (string)
- Discord OAuth authenticated user
- Has one personal Wallet
- Whitelisted via `config/parameters/allowed_discord_users.yaml`

**ApiUser** (`src/Entity/ApiUser.php`):
- ID: UUID
- API token authentication (Bearer token in Authorization header)
- Used for API access (non-Discord clients)

### Authentication System

**Two Separate Firewalls**:

1. **`/api/*` routes** (API Platform):
   - Provider: `api_user_provider` (ApiUser entity)
   - Method: Bearer token via `ApiTokenHandler`
   - **Security Issue**: API keys stored in plain text (should be hashed)

2. **`/admin/*` routes** (EasyAdmin):
   - Provider: `app_user_provider` (DiscordUser entity)
   - Method: Discord OAuth via `DiscordAuthenticator`
   - JWT stored in httpOnly, secure, sameSite cookies
   - JWT blocklist implemented for logout
   - Remember me: 1 week

**Config**: `config/packages/security.yaml`

### Messaging & Async Processing

**RabbitMQ Transports** (`config/packages/messenger.yaml`):

1. **`async_transaction`**:
   - Queue: `messages_transaction`
   - Serializer: `TransactionMessageSerializer`
   - Handles: `TransactionMessage` (DTO for transaction processing)
   - No retries (failed transactions should not be retried)

2. **`transaction_notification`**:
   - Exchange: `transaction_notification_exchange`
   - Serializer: `TransactionNotificationSerializer`
   - Handles: `Transaction` entity (for Discord notifications)

**Middlewares**:
- `doctrine_transaction`: Wraps handlers in DB transaction
- `LoggerMiddleware`: Custom audit logging for messenger

**Workers**: 2 replicas consume messages via Supervisor (see `.docker/supervisor.d/`)

### Concurrency & Locking

**Critical Section**: Transaction processing uses Symfony Lock component to prevent race conditions:

```php
// TransactionHandler.php
$lock = $this->factory->createLock(LockEnum::TRANSACTION_LOCK->value);
$lock->acquire(true);  // Blocks until lock acquired
try {
    // Refresh wallets from DB (get latest amounts)
    // Validate transaction
    // Update wallet amounts
    // Persist transaction
} finally {
    $lock->release();
}
```

Lock name: `TRANSACTION_LOCK` (defined in `LockEnum`)

### Money Handling

**Library**: `brick/money` for arbitrary precision decimal calculations

**Storage**: Amounts stored as strings in minor units (e.g., cents):
- Database: `string` type, e.g., `"150000"` = 1500.00 YoulCoin
- Calculations: Convert to `Money` object, perform math, convert back to string

**Currency**: Custom `YoulCoin` currency (see `MoneyUtil.php`)

**Important**: Never use floats for money calculations (precision loss). Always use `bcmath` functions or brick/money.

### Validation Strategy

**Multi-Level Validation**:

1. **API Platform**: Basic constraints via Symfony Assert attributes on entities
2. **Custom Validators** (`src/Validator/`):
   - `TransactionConstraintValidator`: Business rules (sufficient balance, valid wallet types)
   - `AmountValidator`: Amount must be positive
   - `WalletTypeValidator`: Ensures BANK wallet is target for specific operations
   - `DiscordUserWalletExistValidator`: Ensures Discord user has a wallet

**Validation Groups**: Transaction uses `GroupSequence` for conditional validation:
```php
#[Assert\GroupSequence(['Transaction', 'Negative', 'ValidAmount', 'Exist', 'Type'])]
```
Validates in order, stops at first failure.

**Exception Handling**: `AbstractHandler` validates and throws `ConstraintDefinitionException` on failure (should be `ValidationFailedException`).

### Testing Structure

**PHPUnit** (`phpunit.xml.dist`):
- **Unit tests** (`tests/Unit/`): Test single service in isolation
- **Functional tests** (`tests/Functional/`): Test service interactions, e.g., validators, authentication flow
- Coverage excludes: Entities, Enums, Repositories, Controllers, Kernel

**Behat** (`behat.yml.dist`):
- **`api` suite**: Tests API endpoints end-to-end (Transaction POST)
- **`app` suite**: Tests admin forms and workflows
- **`message` suite**: Tests Messenger message handling

**Contexts** (`tests/Behat/`):
- `ApiContext`: API requests/responses
- `EntityManagerContext`: Database setup/teardown
- `MessengerContext`: Message queue assertions
- `NotifierContext`: Mock Discord notifications

**Fixtures**: Uses Hautelook Alice Bundle for test data generation

## Known Issues & Important Notes

### Critical Issues to Fix

1. **API Keys in Plain Text** (`src/Security/ApiTokenHandler.php`):
   - Currently compares plain text tokens
   - Should hash tokens like passwords (use `password_hash()`)

2. **RoleEnum Duplication**:
   - Three separate enums for roles: `src/Enum/RoleEnum.php`, `src/Enum/Roles/RoleEnum.php`, `src/Enum/Roles/ApiUserRoleEnum.php`
   - Consolidate or clearly distinguish purposes

3. **Incorrect Exception Type** (`src/Service/Handler/Abstraction/AbstractHandler.php`):
   ```php
   throw new ConstraintDefinitionException($errorsString);
   ```
   Should use `ValidationFailedException` for validation errors

4. **Transaction Validation Bug** (`src/Validator/Entity/Transaction/TransactionConstraintValidator.php`):
   ```php
   return bcsub($value->getWalletFrom()->getAmount(), $value->getAmount()) > 0;
   ```
   Should be `>= 0` (allows withdrawing full balance)

5. **Nullable Type Mismatches**:
   - `DiscordUser::$wallet` is `Wallet` (not nullable) but may not exist at creation
   - `Transaction::$externalIdentifier` is `string` but column is nullable
   - Fix: Add `?` to type hints

### Development Packages in Production

These packages are incorrectly in `require` (should be `require-dev`):
```json
"friendsofphp/php-cs-fixer": "^3.70.2",
"pdepend/pdepend": "dev-master",
"phpmd/phpmd": "^2.15",
"squizlabs/php_codesniffer": "^3.11.3"
```

Also remove deprecated package:
```json
"composer/package-versions-deprecated": "1.11.99.2"
```

### Configuration Files

**Parameters** (`config/parameters/`):
- `discord.yaml`: Discord webhook bot settings (username, avatar, colors)
- `allowed_discord_users.yaml`: Whitelist of Discord user IDs
- `admin_project_urls.yaml`: External links in EasyAdmin dashboard

**Custom Dependency Injection** (`config/services.yaml`):
- Auto-wiring enabled for all services
- Custom bindings: `$messengerAuditLogger`, `$env`
- Explicit config for `DiscordNotifier` and `DashboardController`

### Entity ID Strategies

- **Transaction & ApiUser**: UUID v4
- **Wallet**: ULID (time-sortable)
- **DiscordUser**: Discord's snowflake ID (string)

### Code Style Enforcement

All PHP files must:
- Use `declare(strict_types=1);`
- Follow PSR-12 coding standards
- Pass phpcs, phpmd, and php-cs-fixer checks
- Use PHPDoc for exceptions and complex return types

### Messenger Best Practices

- Use DTOs (like `TransactionMessage`) for commands
- Entities as messages (like `Transaction` for notifications) is unusual but works here
- No retries on `async_transaction` to avoid duplicate financial operations
- Always use `doctrine_transaction` middleware for data consistency

### Adding New Transaction Types

1. Add enum value to `TransactionTypeEnum`
2. Update validation logic in `TransactionConstraintValidator` if business rules differ
3. Add builder method in `TransactionBuilder` if construction differs
4. Update Discord notifier templates if notification format differs

### Security Considerations

- API has no rate limiting (vulnerable to brute force)
- JWT blocklist is in-memory (will clear on restart, use Redis for production)
- Discord whitelist is in YAML config (consider moving to database)
- No audit trail for transactions (consider adding)
- No request size limits configured

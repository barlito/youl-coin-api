# YoulCoin API

[![CI](https://github.com/barlito/youl-coin-api/actions/workflows/entrypoint.yaml/badge.svg?branch=master)](https://github.com/barlito/youl-coin-api/actions/workflows/entrypoint.yaml)

Symfony API managing **YoulCoin**, the virtual currency of the Youls Discord community. It is the single source of truth for wallets and transactions: coins move between members through Discord bot commands or authenticated API calls, and every movement is validated, locked, persisted and announced back to Discord.

## How it works

```
Discord bot ──▶ RabbitMQ (transaction_exchange) ──▶ message-worker ─┐
Trusted app ──▶ POST /api/transactions (API token) ─────────────────┤
                                                                    ▼
                                                          TransactionHandler
                                              (lock → validate → move money → persist)
                                                                    │
                        ┌───────────────────────────────────────────┤
                        ▼                                           ▼
        RabbitMQ (transaction_notification_exchange)    Discord channel webhook
                 (consumed by the bot)                     (rich embed message)
```

Two ways in, one code path:

1. **AMQP** — the Discord bot (private repo `youl-coin-discord-bot`) publishes a transaction message to the `transaction_exchange` RabbitMQ exchange. Dedicated worker containers (Symfony Messenger consumers under supervisor, 2 replicas) pick it up through a custom serializer and validate it (amount format, both wallets must exist, valid transaction type).
2. **HTTP** — trusted services `POST /api/transactions` (API Platform), authenticated with a Bearer API token and guarded by `ROLE_TRANSACTION_CREATE`.

Both paths end in the same `TransactionHandler`: it takes a global lock (`symfony/lock`) to serialize concurrent transactions, re-validates, moves the amount between wallets with `brick/money` (amounts stored as minor units — no floats), and persists atomically. It then publishes the transaction to `transaction_notification_exchange` for the bot to consume, and posts a rich embed to a Discord channel via webhook (`symfony/discord-notifier`). Failed messages trigger a Discord error notification plus a critical log.

### Data model

| Entity | Role |
|--------|------|
| `Wallet` | Holds an amount (minor units, string). Type `user` (one per Discord user) or `bank` (unique, enforced by a partial unique index). |
| `Transaction` | Immutable movement between two wallets. Types: `classic`, `air_drop`, `regulation`, `season_reward`. |
| `DiscordUser` | Community member, linked 1-1 to a wallet, authenticates via Discord OAuth2. |
| `ApiUser` | Machine account with an API key, for server-to-server calls. |

### Authentication

| Surface | Auth |
|---------|------|
| `/api/*` | Stateless Bearer token (`ApiUser` API keys) |
| `/admin` | Discord OAuth2 login (`knpuniversity/oauth2-client-bundle`), `ROLE_ADMIN` required |
| Other Youls apps | Lexik JWT issued as a cookie on `.barlito.fr` (carries the `discordId` claim) — shared login across the ecosystem |

The back office is an EasyAdmin dashboard: wallet CRUD, bank wallet management and API user administration.

## Stack

| Layer | Tech |
|-------|------|
| Language / framework | PHP 8.4, Symfony 7.4, API Platform 3 |
| Runtime | FrankenPHP 1 (Caddy), Messenger workers under supervisor |
| Database | PostgreSQL 18, Doctrine ORM 3 + migrations |
| Messaging | RabbitMQ 4 + Symfony Messenger (AMQP) |
| Money | `brick/money` + bcmath |
| Admin | EasyAdmin 4 |
| Infra | Docker Swarm behind [traefik-base](https://github.com/barlito/traefik-base) |
| Tooling | Make rules from [php-make-rules](https://github.com/barlito/php-make-rules) (submodule) + [Castor](https://github.com/jolicode/castor) |

## Quick start

Prerequisites: Docker with Swarm mode enabled, the [traefik-base](https://github.com/barlito/traefik-base) stack running (external `traefik_traefik_proxy` network), Make and Castor.

```bash
git clone --recurse-submodules git@github.com:barlito/youl-coin-api.git
cd youl-coin-api

make deploy                    # build images, deploy the stack, composer install,
                               # run migrations, load fixtures
castor generate-jwt-key-pair   # generate the Lexik JWT keypair inside the container
```

| Service | Local URL |
|---------|-----------|
| API + admin | `yc.local.barlito.fr` |
| Adminer | `yc-adminer.local.barlito.fr` |
| RabbitMQ management | `yc-rabbitmq.local.barlito.fr` |

## Commands

```bash
make deploy            # Full local deploy (stack + composer + migrations + fixtures)
make docker.deploy     # Deploy the Swarm stack only
make deploy.prod       # Prod deploy: stack + DB backup + migrations + smoke test
make db.backup         # pg_dump into the backup volume
make smoke.test        # curl the prod homepage, fail on non-2xx
make phpunit           # Run PHPUnit
make behat             # Run Behat
make quality           # phpcs + phpmd + php-cs-fixer (dry run)
make phpstan           # Static analysis
make docker.bash       # Shell into the PHP container
```

## Tests

- **PHPUnit** — unit tests for isolated services, functional tests for service interactions, validators, security and database constraints.
- **Behat** — end-to-end scenarios against the full environment (PostgreSQL + RabbitMQ): API transactions and wallets, admin pages, Messenger consumption.
- Fixtures via `hautelook/alice-bundle`.

## CI / CD

| Workflow | Trigger | What it does |
|----------|---------|--------------|
| [`entrypoint.yaml`](.github/workflows/entrypoint.yaml) | push | Fans out to code quality + tests |
| [`code-quality.yaml`](.github/workflows/code-quality.yaml) | called | Deploys the CI stack, runs php-cs-fixer, phpcs, PHPStan |
| [`test.yaml`](.github/workflows/test.yaml) | called | Deploys the CI stack, generates JWT keys, runs PHPUnit + Behat |
| [`deploy.yaml`](.github/workflows/deploy.yaml) | manual | Deploys to the prod Swarm over SSH (rolling update or full re-deploy) |
| [`release.yaml`](.github/workflows/release.yaml) | release published | Builds and ships the release image |

## Related projects

- `youl-coin-discord-bot` (private) — the Discord bot members interact with; talks to this API over RabbitMQ.
- [youls-analytics](https://github.com/barlito/youls-analytics) — analytics for the Youls community.
- [youl-tcg](https://github.com/barlito/youl-tcg) — trading card game side project for the same community.
- [traefik-base](https://github.com/barlito/traefik-base) — ingress stack every service runs behind.

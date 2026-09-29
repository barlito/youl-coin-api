@api @transaction

Feature:
    The externalIdentifier is an idempotency key, scoped to the API client that sent it

    Background:
        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"

    Scenario:
    A retry returns the original transaction, even once the balance no longer covers it

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "900000000000",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "idem_drain",
          "type": "classic"
        }
        """
        Then the response status code should be 201

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "900000000000",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "idem_drain",
          "type": "classic"
        }
        """
        Then the response status code should be 201
        And JSON schema should validate Transaction class

        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 0 |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 1700000000000 |
        And I should find 1 "Transaction" entity found by "externalIdentifier=idem_drain"
        And the Discord notifier should have notified "1" notifications

    Scenario:
    Reusing a key for a different transaction is a conflict

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "idem_conflict",
          "type": "classic"
        }
        """
        Then the response status code should be 201

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "20",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "idem_conflict",
          "type": "classic"
        }
        """
        Then the response status code should be 409

        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 899999999990 |
        And I should find 1 "Transaction" entity found by "externalIdentifier=idem_conflict"

    Scenario:
    Two API clients may use the same key

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "idem_shared",
          "type": "classic"
        }
        """
        Then the response status code should be 201

        Given I set header "Authorization" with value "Bearer api_key_other"
        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "idem_shared",
          "type": "classic"
        }
        """
        Then the response status code should be 201

        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 899999999980 |
        And I should find 2 "Transaction" entity found by "externalIdentifier=idem_shared"

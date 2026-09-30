@api @transaction

Feature:
    An app can attach a short free-text description to a transaction, shown to players in their history

    Background:
        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"
        And I send the player token of "188967649332428800"

    Scenario:
    A description is stored trimmed, exposed back and shown in the player history with the app name

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "1000000000",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "with_description",
          "type": "classic",
          "description": "  Carte Pikachu <b>x1</b>  "
        }
        """
        Then the response status code should be 201
        And JSON schema should validate Transaction class
        And the JSON should contain:
        """
        {"description": "Carte Pikachu <b>x1</b>"}
        """
        And a "Transaction" entity found by "externalIdentifier=with_description" should match:
            | description | Carte Pikachu <b>x1</b> |

        When I send a GET request to "/api/transactions?externalIdentifier=with_description"
        Then the response status code should be 200
        And the JSON should contain:
        """
        {"hydra:member": [{"description": "Carte Pikachu <b>x1</b>"}]}
        """

        Given I set header "Authorization" with value "Bearer api_key_wallet_history"
        And I send the player token of "195659530363731968"
        When I send a GET request to "/api/user/195659530363731968/transactions"
        Then the response status code should be 200
        And the JSON should contain:
        """
        {"hydra:member": [{"description": "Carte Pikachu <b>x1</b>", "app": "Youl TCG", "direction": "in"}]}
        """

    Scenario:
    The description is optional and a blank one becomes null

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "blank_description",
          "type": "classic",
          "description": "   "
        }
        """
        Then the response status code should be 201
        And the JSON should not have the key "description"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "no_description",
          "type": "classic"
        }
        """
        Then the response status code should be 201
        And the JSON should not have the key "description"

    Scenario:
    A description longer than 140 characters is refused and nothing moves

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "too_long_description",
          "type": "classic",
          "description": "123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901"
        }
        """
        Then the response status code should be 422
        And a "Transaction" entity found by "externalIdentifier=too_long_description" should not exist
        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 900000000000 |

    Scenario:
    A replay with another description is the same transaction: the description is cosmetic

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "replay_description",
          "type": "classic",
          "description": "First"
        }
        """
        Then the response status code should be 201

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "replay_description",
          "type": "classic",
          "description": "Second"
        }
        """
        Then the response status code should be 201
        And the JSON should contain:
        """
        {"description": "First"}
        """
        And I should find 1 "Transaction" entity found by "externalIdentifier=replay_description"

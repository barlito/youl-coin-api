@api @transaction

Feature:
    WelcomeBonus is system-only: no API client can create it, whatever role it holds

    Background:
        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"
        And I send the player token of "188967649332428800"

    Scenario:
    WelcomeBonus is refused with 403, even with every role and a wallet-complete payload

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "100000000000",
          "walletFrom": "/api/wallets/01HAJGPGCP28GFA6QD08NMH764",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "welcome_bonus_complete",
          "type": "welcome_bonus"
        }
        """

        Then the response status code should be 403
        And a "Transaction" entity found by "externalIdentifier=welcome_bonus_complete" should not exist

    Scenario:
    WelcomeBonus is refused with 403 even with an incomplete payload (no walletFrom is not the normal shape, but it stays system-only)

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "100000000000",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "welcome_bonus_incomplete",
          "type": "welcome_bonus"
        }
        """

        Then the response status code should be 403
        And a "Transaction" entity found by "externalIdentifier=welcome_bonus_incomplete" should not exist

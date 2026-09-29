@api @transaction

Feature:
    Mint and Burn are admin-only: no API client can create them, whatever role it holds

    Background:
        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"
        And I send the player token of "188967649332428800"

    Scenario:
    Mint is refused with 403, even with every role and a wallet-complete payload

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletTo": "/api/wallets/01HAJGPGCP28GFA6QD08NMH764",
          "externalIdentifier": "mint_complete",
          "type": "mint"
        }
        """

        Then the response status code should be 403
        And a "Transaction" entity found by "externalIdentifier=mint_complete" should not exist

    Scenario:
    Burn is refused with 403, even with every role and a wallet-complete payload

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01HAJGPGCP28GFA6QD08NMH764",
          "externalIdentifier": "burn_complete",
          "type": "burn"
        }
        """

        Then the response status code should be 403
        And a "Transaction" entity found by "externalIdentifier=burn_complete" should not exist

    Scenario:
    Mint is refused with 403 even with an incomplete payload (no walletFrom is normal for a Mint, but it stays admin-only)

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "externalIdentifier": "mint_incomplete",
          "type": "mint"
        }
        """

        Then the response status code should be 403
        And a "Transaction" entity found by "externalIdentifier=mint_incomplete" should not exist

    Scenario:
    A payload missing walletFrom for a normal type still gets a 422, not a 403 (Mint/Burn stay the only reserved types)

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "classic_incomplete",
          "type": "classic"
        }
        """

        Then the response status code should be 422
        And a "Transaction" entity found by "externalIdentifier=classic_incomplete" should not exist

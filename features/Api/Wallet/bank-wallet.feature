@api @wallet

Feature:
    I want to test the bank Wallet GET endpoint

    Scenario Outline:
    Reading the bank wallet needs an API key with ROLE_WALLET_READ

        Given I set header "Authorization" with value "<authorization>"

        When I send a GET request to "/api/bank/wallet"

        Then the response status code should be <code>

        Examples:
            | authorization            | code |
            |                          | 401  |
            | Bearer bad_auth_token    | 401  |
            | Bearer api_key_bank_only | 403  |
            | Bearer api_key_reader    | 200  |

    Scenario:
    The bank wallet is returned with the same fields as a player wallet

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_reader"

        When I send a GET request to "/api/bank/wallet"

        Then the response status code should be 200

        And the JSON should contain:
        """
        {
            "@type": "Wallet",
            "id": "01HAJGPGCP28GFA6QD08NMH764",
            "amount": "1000000000000",
            "type": "bank",
            "name": "Bank Wallet"
        }
        """

        And the JSON should not have the key "discordUser"

    Scenario:
    A missing bank wallet answers 404

        Given I reload the fixtures
        And a "Wallet" entity found by "type=bank" should be deleted
        And I set header "Authorization" with value "Bearer api_key_reader"

        When I send a GET request to "/api/bank/wallet"

        Then the response status code should be 404

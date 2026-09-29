@api @transaction

Feature:
    Each transaction direction needs its own API role

    Scenario Outline:
    The wallets of the transaction decide which role is required

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer <apiKey>"
        And I send the player token of "188967649332428800"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/<walletFrom>",
          "walletTo": "/api/wallets/<walletTo>",
          "externalIdentifier": "roles_<apiKey>_<type>",
          "type": "<type>"
        }
        """

        Then the response status code should be <code>

        Examples:
            | apiKey            | walletFrom                 | walletTo                   | type     | code |
            | api_key_bank_only | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC | air_drop | 201  |
            | api_key_bank_only | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 | classic  | 201  |
            | api_key_bank_only | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | classic  | 403  |
            | api_key_reader    | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC | air_drop | 403  |
            | api_key_test      | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | classic  | 201  |

    Scenario:
    A refused transaction moves nothing

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_bank_only"
        And I send the player token of "188967649332428800"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "roles_refused",
          "type": "classic"
        }
        """

        Then the response status code should be 403
        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 900000000000 |
        And a "Transaction" entity found by "externalIdentifier=roles_refused" should not exist

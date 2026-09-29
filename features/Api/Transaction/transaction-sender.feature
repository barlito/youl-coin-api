@api @transaction

Feature:
    Coins only leave a player's wallet when the API client forwards that player's token (X-Player-Token)

    Background:
        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"

    Scenario Outline:
    The forwarded token must belong to the owner of walletFrom

        Given <token>

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/<walletTo>",
          "externalIdentifier": "sender_<walletTo>",
          "type": "classic"
        }
        """

        Then the response status code should be <code>

        Examples:
            | token                                                     | walletTo                   | code |
            | I send the player token of "188967649332428800"           | 01FPD1DNKVFS5GGBPVXBT3YQ01 | 201  |
            | I send the player token of "188967649332428800"           | 01HAJGPGCP28GFA6QD08NMH764 | 201  |
            | I send the player token of "195659530363731968"           | 01FPD1DNKVFS5GGBPVXBT3YQ01 | 403  |
            | I send the player token of "195659530363731968"           | 01HAJGPGCP28GFA6QD08NMH764 | 403  |
            | I send an expired player token of "188967649332428800"    | 01FPD1DNKVFS5GGBPVXBT3YQ01 | 403  |
            | I set header "X-Player-Token" with value "not-a-jwt"      | 01FPD1DNKVFS5GGBPVXBT3YQ01 | 403  |
            | I set header "X-Player-Token" with value ""               | 01FPD1DNKVFS5GGBPVXBT3YQ01 | 403  |

    Scenario:
    A token revoked at logout no longer allows spending

        Given I send the player token of "188967649332428800"
        And the player token has been revoked

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "sender_revoked",
          "type": "classic"
        }
        """

        Then the response status code should be 403
        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 900000000000 |

    Scenario:
    The bank pays a player without any player token

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01HAJGPGCP28GFA6QD08NMH764",
          "walletTo": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "externalIdentifier": "sender_bank",
          "type": "air_drop"
        }
        """

        Then the response status code should be 201

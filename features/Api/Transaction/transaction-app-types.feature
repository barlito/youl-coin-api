@api @transaction

Feature:
    App transaction types only move coins between a player and the bank, in their own direction

    Scenario Outline:
    The right direction is created, and a player debit needs the player token

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"
        And I send the player token of "188967649332428800"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/<walletFrom>",
          "walletTo": "/api/wallets/<walletTo>",
          "externalIdentifier": "app_type_<type>",
          "type": "<type>"
        }
        """

        Then the response status code should be 201
        And a "Transaction" entity found by "externalIdentifier=app_type_<type>" should match:
            | amount | 10 |

        Examples:
            | type           | walletFrom                 | walletTo                   |
            | purchase       | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 |
            | market_payment | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 |
            | reward         | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC |
            | market_payout  | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC |
            | market_refund  | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC |

    Scenario Outline:
    A forbidden direction is refused with a message naming the type

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"
        And I send the player token of "188967649332428800"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/<walletFrom>",
          "walletTo": "/api/wallets/<walletTo>",
          "externalIdentifier": "app_type_wrong_<type>",
          "type": "<type>"
        }
        """

        Then the response status code should be 422
        And the JSON should contain a ConstraintViolationList with "<message>"
        And a "Transaction" entity found by "externalIdentifier=app_type_wrong_<type>" should not exist

        Examples:
            | type           | walletFrom                 | walletTo                   | message                                                                 |
            | purchase       | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC | Purchase Transaction must go from a user Wallet to the Bank Wallet.     |
            | market_payment | 01HAJGPGCP28GFA6QD08NMH764 | 01FPD1DHMWPV4BHJQ82TSJEBJC | Market Payment Transaction must go from a user Wallet to the Bank Wallet. |
            | reward         | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 | Reward Transaction must go from the Bank Wallet to a user Wallet.       |
            | market_payout  | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 | Market Payout Transaction must go from the Bank Wallet to a user Wallet. |
            | market_refund  | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 | Market Refund Transaction must go from the Bank Wallet to a user Wallet. |

    Scenario Outline:
    Between two players an app type is refused with 422

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"
        And I send the player token of "188967649332428800"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "app_type_p2p_<type>",
          "type": "<type>"
        }
        """

        Then the response status code should be 422
        And a "Transaction" entity found by "externalIdentifier=app_type_p2p_<type>" should not exist

        Examples:
            | type           |
            | purchase       |
            | market_payment |
            | reward         |
            | market_payout  |
            | market_refund  |

    Scenario Outline:
    Debiting a player without their own token is refused

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01HAJGPGCP28GFA6QD08NMH764",
          "externalIdentifier": "app_type_no_token_<type>",
          "type": "<type>"
        }
        """

        Then the response status code should be 403
        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 900000000000 |

        Examples:
            | type           |
            | purchase       |
            | market_payment |

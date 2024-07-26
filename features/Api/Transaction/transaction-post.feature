@api @transaction

Feature:
    I want to test my Transaction POST endpoints

    Scenario:
    I try to POST a transaction with a bad authorization header

        Given I set header "Authorization" with value "Bearer bad_auth_token"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "1000000000",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "test_from_api",
          "type": "classic"
        }
        """

        Then the response status code should be 401

        And a "Transaction" entity found by "externalIdentifier=c2f5f2ef-f893-4470-a751-6c52cecbc261" should not exist

    Scenario Outline:
    I want to test POST endpoint with payload errors

        Given I set header "Authorization" with value "Bearer api_key_test"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "<amount>",
          "walletFrom": "/api/wallets/<walletFromIri>",
          "walletTo": "/api/wallets/<walletToIri>",
          "type": "<type>",
          "externalIdentifier": "c2f5f2ef-f893-4470-a751-6c52cecbc261"
        }
        """

        Then the response status code should be <code>

        And a "Transaction" entity found by "externalIdentifier=c2f5f2ef-f893-4470-a751-6c52cecbc261" should not exist

        And the JSON should contain a ConstraintViolationList with "<message>"

        Examples:
            | amount        | walletFromIri              | walletToIri                | type          | code | message                                                                           |
            |               | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | classic       | 422  | amount: This value should not be blank.                                           |
            | -10           | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | classic       | 422  | amount: The amount value is not a positive integer                                |
            | 9999900000000 | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | classic       | 422  | Not enough coins in from wallet.                                                  |
            | 10            | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DHMWPV4BHJQ82TSJEBJC | classic       | 422  | WalletFrom and WalletTo are the same.                                             |
            | 10            | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | wrong         | 400  | The data must belong to a backed enumeration of type App\Enum\TransactionTypeEnum |
            | 10            | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 | air_drop      | 422  | AirDrop Transaction must have the Bank Wallet as Wallet From.                     |
            | 10            | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01FPD1DNKVFS5GGBPVXBT3YQ01 | regulation    | 422  | Regulation Transaction must have the Bank Wallet as Wallet From or Wallet To.     |
            | 10            | 01FPD1DHMWPV4BHJQ82TSJEBJC | 01HAJGPGCP28GFA6QD08NMH764 | season_reward | 422  | Season Reward Transaction must have the Bank Wallet as Wallet From.               |


    Scenario:
    I want to test my POST endpoint with good values

        Given I reload the fixtures
        And I set header "Authorization" with value "Bearer api_key_test"

        Given a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | id     | 01FPD1DHMWPV4BHJQ82TSJEBJC |
            | amount | 900000000000               |
        Given a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | id     | 01FPD1DNKVFS5GGBPVXBT3YQ01 |
            | amount | 800000000000               |

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "1000000000",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01FPD1DNKVFS5GGBPVXBT3YQ01",
          "externalIdentifier": "test_from_api",
          "type": "classic"
        }
        """

        Then the response status code should be 201
        And JSON schema should validate Transaction class

        And a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | id     | 01FPD1DHMWPV4BHJQ82TSJEBJC |
            | amount | 899000000000               |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | id     | 01FPD1DNKVFS5GGBPVXBT3YQ01 |
            | amount | 801000000000               |
        And a "Transaction" entity found by "walletFrom=01FPD1DHMWPV4BHJQ82TSJEBJC&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&externalIdentifier=test_from_api" should match:
            | amount             | 1000000000                                      |
            | type               | !php/enum App\Enum\TransactionTypeEnum::CLASSIC |
            | externalIdentifier | test_from_api                                   |

        And the Discord notifier should have notified "1" notifications

        And "1" message has been sent on "transaction_notification" transport
        And the "1" message sent on "transaction_notification" transport should match:
        """
        {
          "amount": 1000000000,
          "walletFrom.discordUser.discordId": "188967649332428800",
          "walletTo.discordUser.discordId": "195659530363731968",
          "type": "!php/enum App\\Enum\\TransactionTypeEnum::CLASSIC",
          "externalIdentifier": "test_from_api"
        }
        """

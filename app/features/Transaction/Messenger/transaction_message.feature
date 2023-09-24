@messenger

Feature:
    I want to test when a TransactionMessage is received
    A Transaction is created and Wallets are updated
    Notifications are send correctly
    Errors are raised if the message is bad

    Scenario Outline:
    I send incorrect Messages
    Errors should be logged and notified on discord
    Transaction entity should not be created
    Wallets shouldn't be updated

        When I send and consume a TransactionMessage to the queue with body:
        """
        {
          "amount": "<amount>",
          "discordUserIdFrom": "<discordUserIdFrom>",
          "discordUserIdTo": "<discordUserIdTo>",
          "type": "<type>",
          "externalIdentifier": "e588239e-f47a-4864-9f23-d09838dc00a8"
        }
        """

        And a "Transaction" entity found by "externalIdentifier=e588239e-f47a-4864-9f23-d09838dc00a8" should not exist

        And the logger logged an error containing "<message>"

        And the Discord notifier should have notified "1" error

        Examples:
            | amount        | discordUserIdFrom  | discordUserIdTo    | type          | message                                                                       |
            |               | 188967649332428800 | 195659530363731968 | classic       | The amount value should not be blank.                                         |
            | -10           | 188967649332428800 | 195659530363731968 | classic       | The amount value is not a positive integer                                    |
            | 9999900000000 | 188967649332428800 | 195659530363731968 | classic       | Not enough coins in from wallet.                                              |
            | 10            |                    | 195659530363731968 | classic       | The discordUserIdFrom value should not be blank.                              |
            | 10            | 188967649332428800 |                    | classic       | The discordUserIdTo value should not be blank.                                |
            | 10            | 188967649332428800 | 188967649332428800 | classic       | WalletFrom and WalletTo are the same.                                         |
            | 10            | 188967649332428800 | 195659530363731968 | wrong         | The type value you selected is not a valid Transaction Type or is null.       |
            | 10            | wrong              | 195659530363731968 | classic       | The Wallet with the given Discord ID was not found.                           |
            | 10            | 188967649332428800 | wrong              | classic       | The Wallet with the given Discord ID was not found.                           |
            | 10            | 188967649332428800 | bank               | air_drop      | AirDrop Transaction must have the Bank Wallet as Wallet From.                 |
            | 10            | 188967649332428800 | 195659530363731968 | regulation    | Regulation Transaction must have the Bank Wallet as Wallet From or Wallet To. |
            | 10            | 188967649332428800 | bank               | season_reward | Season Reward Transaction must have the Bank Wallet as Wallet From.           |

    Scenario: I send a correct Message
    TransactionMessage should be processed
    A Transaction entity should be created in database
    Wallet should have been updated

        Given I reload the fixtures

        Given a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 900000000000 |
        Given a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 800000000000 |

        When I send and consume a TransactionMessage to the queue with body:
        """
        {
          "amount": 1000000000,
          "discordUserIdFrom": "188967649332428800",
          "discordUserIdTo": "195659530363731968",
          "type": "classic",
          "externalIdentifier": "1a5b2d53-b5b5-4880-9a96-591638359184"
        }
        """

        Then a "Wallet" entity found by "discordUser=188967649332428800" should match:
            | amount | 899000000000 |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 801000000000 |
        And a "Transaction" entity found by "walletFrom=01FPD1DHMWPV4BHJQ82TSJEBJC&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&externalIdentifier=1a5b2d53-b5b5-4880-9a96-591638359184" should match:
            | amount             | 1000000000                                      |
            | type               | !php/enum App\Enum\TransactionTypeEnum::CLASSIC |
            | externalIdentifier | 1a5b2d53-b5b5-4880-9a96-591638359184            |

        And the Discord notifier should have notified "1" notifications

        And "1" message has been sent on "transaction_notification" transport
        And the "1" message sent on "transaction_notification" transport should match:
        """
        {
          "amount": 1000000000,
          "walletFrom.discordUser.discordId": "188967649332428800",
          "walletTo.discordUser.discordId": "195659530363731968",
          "type": "!php/enum App\\Enum\\TransactionTypeEnum::CLASSIC",
          "externalIdentifier": "1a5b2d53-b5b5-4880-9a96-591638359184"
        }
        """

    Scenario: I send a correct Message with AIR_DROP type
    TransactionMessage should be processed
    A Transaction entity should be created in database
    Wallet should have been updated

        Given I reload the fixtures

        Given a "Wallet" entity found by "type=bank" should match:
            | amount | 1000000000000 |
        Given a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 800000000000 |

        When I send and consume a TransactionMessage to the queue with body:
        """
        {
          "amount": 1000000000,
          "discordUserIdFrom": "bank",
          "discordUserIdTo": "195659530363731968",
          "type": "air_drop",
          "externalIdentifier": "bank_air_drop"
        }
        """

        Then a "Wallet" entity found by "type=bank" should match:
            | amount | 999000000000 |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 801000000000 |
        And a "Transaction" entity found by "walletFrom=01HAJGPGCP28GFA6QD08NMH764&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&externalIdentifier=bank_air_drop" should match:
            | amount             | 1000000000                                       |
            | type               | !php/enum App\Enum\TransactionTypeEnum::AIR_DROP |
            | externalIdentifier | bank_air_drop                                    |

        And the Discord notifier should have notified "1" notifications

        And "1" message has been sent on "transaction_notification" transport
        And the "1" message sent on "transaction_notification" transport should match:
        """
        {
          "amount": 1000000000,
          "walletFrom.name": "Bank Wallet",
          "walletTo.discordUser.discordId": "195659530363731968",
          "type": "!php/enum App\\Enum\\TransactionTypeEnum::AIR_DROP",
          "externalIdentifier": "bank_air_drop"
        }
        """

    Scenario: I send a correct Message with REGULATION type from bank to User
    TransactionMessage should be processed
    A Transaction entity should be created in database
    Wallet should have been updated

        Given I reload the fixtures

        Given a "Wallet" entity found by "type=bank" should match:
            | amount | 1000000000000 |
        Given a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 800000000000 |

        When I send and consume a TransactionMessage to the queue with body:
        """
        {
          "amount": 1000000000,
          "discordUserIdFrom": "bank",
          "discordUserIdTo": "195659530363731968",
          "type": "regulation",
          "externalIdentifier": "regulation"
        }
        """

        Then a "Wallet" entity found by "type=bank" should match:
            | amount | 999000000000 |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 801000000000 |
        And a "Transaction" entity found by "walletFrom=01HAJGPGCP28GFA6QD08NMH764&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&externalIdentifier=regulation" should match:
            | amount             | 1000000000                                         |
            | type               | !php/enum App\Enum\TransactionTypeEnum::REGULATION |
            | externalIdentifier | regulation                                         |

        And the Discord notifier should have notified "1" notifications

        And "1" message has been sent on "transaction_notification" transport
        And the "1" message sent on "transaction_notification" transport should match:
        """
        {
          "amount": 1000000000,
          "walletFrom.name": "Bank Wallet",
          "walletTo.discordUser.discordId": "195659530363731968",
          "type": "!php/enum App\\Enum\\TransactionTypeEnum::REGULATION",
          "externalIdentifier": "regulation"
        }
        """

    Scenario: I send a correct Message with REGULATION type from User to Bank
    TransactionMessage should be processed
    A Transaction entity should be created in database
    Wallet should have been updated

        Given I reload the fixtures

        Given a "Wallet" entity found by "type=bank" should match:
            | amount | 1000000000000 |
        Given a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 800000000000 |

        When I send and consume a TransactionMessage to the queue with body:
        """
        {
          "amount": 1000000000,
          "discordUserIdFrom": "195659530363731968",
          "discordUserIdTo": "bank",
          "type": "regulation",
          "externalIdentifier": "regulationReverse"
        }
        """

        Then a "Wallet" entity found by "type=bank" should match:
            | amount | 1001000000000 |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 799000000000 |
        And a "Transaction" entity found by "walletFrom=01FPD1DNKVFS5GGBPVXBT3YQ01&walletTo=01HAJGPGCP28GFA6QD08NMH764&externalIdentifier=regulationReverse" should match:
            | amount             | 1000000000                                         |
            | type               | !php/enum App\Enum\TransactionTypeEnum::REGULATION |
            | externalIdentifier | regulationReverse                                  |

        And the Discord notifier should have notified "1" notifications

        And "1" message has been sent on "transaction_notification" transport
        And the "1" message sent on "transaction_notification" transport should match:
        """
        {
          "amount": 1000000000,
          "walletFrom.discordUser.discordId": "195659530363731968",
          "walletTo.name": "Bank Wallet",
          "type": "!php/enum App\\Enum\\TransactionTypeEnum::REGULATION",
          "externalIdentifier": "regulationReverse"
        }
        """

    Scenario: I send a correct Message with SEASON_REWARD
    TransactionMessage should be processed
    A Transaction entity should be created in database
    Wallet should have been updated

        Given I reload the fixtures

        Given a "Wallet" entity found by "type=bank" should match:
            | amount | 1000000000000 |
        Given a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 800000000000 |

        When I send and consume a TransactionMessage to the queue with body:
        """
        {
          "amount": 1000000000,
          "discordUserIdFrom": "bank",
          "discordUserIdTo": "195659530363731968",
          "type": "season_reward",
          "externalIdentifier": "season_reward"
        }
        """

        Then a "Wallet" entity found by "type=bank" should match:
            | amount | 999000000000 |
        And a "Wallet" entity found by "discordUser=195659530363731968" should match:
            | amount | 801000000000 |
        And a "Transaction" entity found by "walletFrom=01HAJGPGCP28GFA6QD08NMH764&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&externalIdentifier=season_reward" should match:
            | amount             | 1000000000                                            |
            | type               | !php/enum App\Enum\TransactionTypeEnum::SEASON_REWARD |
            | externalIdentifier | season_reward                                         |

        And the Discord notifier should have notified "1" notifications

        And "1" message has been sent on "transaction_notification" transport
        And the "1" message sent on "transaction_notification" transport should match:
        """
        {
          "amount": 1000000000,
          "walletFrom.name": "Bank Wallet",
          "walletTo.discordUser.discordId": "195659530363731968",
          "type": "!php/enum App\\Enum\\TransactionTypeEnum::SEASON_REWARD",
          "externalIdentifier": "season_reward"
        }
        """

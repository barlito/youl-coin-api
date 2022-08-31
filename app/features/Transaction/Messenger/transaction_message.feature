@messenger

Feature:
  I want to test when a TransactionMessage is received
  A Transaction is created and Wallets are updated
  Notifications are send correctly
  Errors are raised if the message is bad

#  todo add a Discord notifier mock to test discord notification but without sending them

  Scenario Outline:
  I send uncorrect Messages
  Errors should be logged and notified on discord
  Transaction entity should not be created
  Wallets shouldn't be updated

    When I send a TransactionMessage to the queue with body:
    """
    {
      "amount": "<amount>",
      "discordUserIdFrom": "<discordUserIdFrom>",
      "discordUserIdTo": "<discordUserIdTo>",
      "type": "<type>",
      "message": "e588239e-f47a-4864-9f23-d09838dc00a8"
    }
    """

    Then I run the messenger consumer command and consume "1" messages

    And a "Transaction" entity found by "message=e588239e-f47a-4864-9f23-d09838dc00a8" should not exist

    Then the logger logged an error containing "<message>"

    Examples:
      | amount | discordUserIdFrom  | discordUserIdTo    | type    | message                                            |
      |        | 188967649332428800 | 195659530363731968 | classic | The amount value should not be blank.              |
      | -10    | 188967649332428800 | 195659530363731968 | classic | The amount value is not a positive integer         |
      | 99999  | 188967649332428800 | 195659530363731968 | classic | Not enough coins in from wallet.                   |
      | 10     |                    | 195659530363731968 | classic | The walletFrom value should not be null.           |
      | 10     | 188967649332428800 |                    | classic | The walletTo value should not be null.             |
      | 10     | 188967649332428800 | 188967649332428800 | classic | WalletFrom and WalletTo are the same.              |
      | 10     | 188967649332428800 | 195659530363731968 | wrong   | The type value you selected is not a valid choice. |

  Scenario: I send a correct Message
  TransactionMessage should be processed
  A Transaction entity should be created in database
  Wallet should have been updated

    And a "Wallet" entity found by "discordUser=188967649332428800" should match:
      | amount | 9000 |
    And a "Wallet" entity found by "discordUser=195659530363731968" should match:
      | amount | 8000 |

    When I send a TransactionMessage to the queue with body:
    """
    {
      "amount": 10,
      "discordUserIdFrom": "188967649332428800",
      "discordUserIdTo": "195659530363731968",
      "type": "classic",
      "message": "1a5b2d53-b5b5-4880-9a96-591638359184"
    }
    """

    Then I run the messenger consumer command and consume "1" messages

    And a "Wallet" entity found by "discordUser=188967649332428800" should match:
      | amount | 8990 |
    And a "Wallet" entity found by "discordUser=195659530363731968" should match:
      | amount | 8010 |
    And a "Transaction" entity found by "amount=10&walletFrom=01FPD1DHMWPV4BHJQ82TSJEBJC&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&message=1a5b2d53-b5b5-4880-9a96-591638359184" should match:
      | amount  | 10                                   |
      | type    | classic                              |
      | message | 1a5b2d53-b5b5-4880-9a96-591638359184 |

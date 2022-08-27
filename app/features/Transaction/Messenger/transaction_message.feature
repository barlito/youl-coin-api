@messenger

Feature:
  I want to test when a TransactionMessage is received
  A Transaction is created and Wallets are updated
  Notifications are send correctly
  Errors are raised if the message is bad

#  todo test logs
#  todo test broken messages with an Outline scenario to test errors and logs
#  todo add a Discord notifier mock to test discord notification but without sending them

  Scenario: I send a correct Message
  TransactionMessage should be processed
  A Transaction entity should be created in database
  Wallet should have been updated

    Given a "Wallet" entity found by "id=01FPD1DHMWPV4BHJQ82TSJEBJC" should match:
      | amount | 9000 |

    Given a "Wallet" entity found by "id=01FPD1DNKVFS5GGBPVXBT3YQ01" should match:
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

    And a "Wallet" entity found by "id=01FPD1DHMWPV4BHJQ82TSJEBJC" should match:
      | amount | 8990 |
    And a "Wallet" entity found by "id=01FPD1DNKVFS5GGBPVXBT3YQ01" should match:
      | amount | 8010 |
    And a "Transaction" entity found by "amount=10&walletFrom=01FPD1DHMWPV4BHJQ82TSJEBJC&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01&message=1a5b2d53-b5b5-4880-9a96-591638359184" should match:
      | amount  | 10           |
      | type    | classic      |
      | message | 1a5b2d53-b5b5-4880-9a96-591638359184 |

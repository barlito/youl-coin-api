Feature:
  I want to test when a TransactionMessage is received
  A Transaction is created and Wallets are updated
  Notifications are send correctly
  Errors are raised if the message is bad

  Scenario: I send a correct Message
  TransactionMessage should be processed
  A Transaction entity should be created in database
  Wallet should have been updated

    Given a "Wallet" entity found by "id=01FPD1DHMWPV4BHJQ82TSJEBJC" should match:
      | amount | 9000 |

    Given a "Wallet" entity found by "id=01FPD1DNKVFS5GGBPVXBT3YQ01" should match:
      | amount | 8000 |

#    When I send a TransactionMessage to the queue with WalletFrom ID:"01FPD1DHMWPV4BHJQ82TSJEBJC" and WalletTo ID :"01FPD1DNKVFS5GGBPVXBT3YQ01"
    When I send a TransactionMessage to the queue with body:
    """
    {
      "amount": 10,
      "walletFrom": "01FPD1DHMWPV4BHJQ82TSJEBJC",
      "walletTo": "01FPD1DNKVFS5GGBPVXBT3YQ01",
      "type": "classic",
      "message": "test message"
    }
    """

    Then I start the messenger consumer and consume "1" messages

    And a "Wallet" entity found by "id=01FPD1DHMWPV4BHJQ82TSJEBJC" should match:
      | amount | 8990 |
    And a "Wallet" entity found by "id=01FPD1DNKVFS5GGBPVXBT3YQ01" should match:
      | amount | 8010 |
    And a "Transaction" entity found by "amount=10&walletFrom=01FPD1DHMWPV4BHJQ82TSJEBJC&walletTo=01FPD1DNKVFS5GGBPVXBT3YQ01" should match:
      | amount  | 10      |
      | type    | classic |
      | message | test message |
    And I stop the messenger consumer

@api @wallet

Feature:
    A player's wallet history: transactions where their wallet is source or destination, most recent first

    Background:
        Given I reload the fixtures

    Scenario:
    A player reads their own history with their player token

        Given I set header "Authorization" with value "Bearer api_key_wallet_history"
        And I send the player token of "232457563910832129"

        When I send a GET request to "/api/user/232457563910832129/transactions"

        Then the response status code should be 200
        And the JSON should contain:
        """
        {
          "hydra:totalItems": 3,
          "hydra:member": [
            {"amount": "333000000", "type": "classic", "direction": "out", "counterpartType": "user", "counterpartDiscordId": "186441663856508928"},
            {"amount": "222000000", "type": "classic", "direction": "in", "counterpartType": "user", "counterpartDiscordId": "186441663856508928"},
            {"amount": "111000000", "type": "classic", "direction": "out", "counterpartType": "user", "counterpartDiscordId": "186441663856508928"}
          ]
        }
        """
        And the JSON should not contain "externalIdentifier"
        And the JSON should not contain "issuer"

    Scenario:
    A trusted client reads any player's history without a player token

        Given I set header "Authorization" with value "Bearer api_key_wallet_history_any"

        When I send a GET request to "/api/user/232457563910832129/transactions"

        Then the response status code should be 200
        And the JSON should contain:
        """
        {"hydra:totalItems": 3}
        """

    Scenario:
    The player token of another player is refused

        Given I set header "Authorization" with value "Bearer api_key_wallet_history"
        And I send the player token of "195659530363731968"

        When I send a GET request to "/api/user/232457563910832129/transactions"

        Then the response status code should be 403

    Scenario:
    ROLE_WALLET_HISTORY_READ without a player token is refused

        Given I set header "Authorization" with value "Bearer api_key_wallet_history"

        When I send a GET request to "/api/user/232457563910832129/transactions"

        Then the response status code should be 403

    Scenario Outline:
    Access rules

        Given I set header "Authorization" with value "<authorization>"

        When I send a GET request to "/api/user/232457563910832129/transactions"

        Then the response status code should be <code>

        Examples:
            | authorization                    | code |
            |                                   | 401  |
            | Bearer api_key_reader             | 403  |
            | Bearer api_key_wallet_history_any | 200  |

    Scenario:
    An unknown player is a 404

        Given I set header "Authorization" with value "Bearer api_key_wallet_history_any"

        When I send a GET request to "/api/user/000000000000000000/transactions"

        Then the response status code should be 404

    Scenario:
    Pagination: 30 items per page, most recent first

        Given I set header "Authorization" with value "Bearer api_key_wallet_history_any"

        When I send a GET request to "/api/user/500000000000000001/transactions"

        Then the response status code should be 200
        And the JSON should contain:
        """
        {"hydra:totalItems": 33}
        """
        And the hydra member collection should contain 30 items
        And the hydra member "amount" values should be "33000000,32000000,31000000,30000000,29000000,28000000,27000000,26000000,25000000,24000000,23000000,22000000,21000000,20000000,19000000,18000000,17000000,16000000,15000000,14000000,13000000,12000000,11000000,10000000,9000000,8000000,7000000,6000000,5000000,4000000"

        When I send a GET request to "/api/user/500000000000000001/transactions?page=2"

        Then the response status code should be 200
        And the JSON should contain:
        """
        {"hydra:totalItems": 33}
        """
        And the hydra member collection should contain 3 items
        And the hydra member "amount" values should be "3000000,2000000,1000000"

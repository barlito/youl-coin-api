@api @transaction

Feature:
    An API client reads back the transactions it created, to check a transaction went through after a timeout

    Background:
        Given I reload the fixtures

    Scenario:
    Reading one of my transactions by id

        Given I set header "Authorization" with value "Bearer api_key_test"

        When I send a GET request to "/api/transactions/a1b2c3d4-0000-4000-8000-000000000001"

        Then the response status code should be 200
        And JSON schema should validate Transaction class
        And the JSON should contain:
        """
        {
          "id": "a1b2c3d4-0000-4000-8000-000000000001",
          "amount": "2500000000",
          "walletFrom": "/api/wallets/01FPD1DRHVBMZEM5EGS95F5N3E",
          "walletTo": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "externalIdentifier": "fixture_issued_by_test",
          "type": "classic"
        }
        """

    Scenario:
    Looking a transaction up by its externalIdentifier

        Given I set header "Authorization" with value "Bearer api_key_test"

        When I send a GET request to "/api/transactions?externalIdentifier=fixture_issued_by_test"

        Then the response status code should be 200
        And the JSON should contain:
        """
        {
          "hydra:totalItems": 1,
          "hydra:member": [{"id": "a1b2c3d4-0000-4000-8000-000000000001"}]
        }
        """

    Scenario:
    A transaction created through the API can be read back right away

        Given I set header "Authorization" with value "Bearer api_key_bank_only"

        When I send a POST request to "api/transactions" with body:
        """
        {
          "amount": "10",
          "walletFrom": "/api/wallets/01FPD1DHMWPV4BHJQ82TSJEBJC",
          "walletTo": "/api/wallets/01HAJGPGCP28GFA6QD08NMH764",
          "externalIdentifier": "read_after_post",
          "type": "classic"
        }
        """
        Then the response status code should be 201

        When I send a GET request to "/api/transactions?externalIdentifier=read_after_post"
        Then the response status code should be 200
        And the JSON should contain:
        """
        {
          "hydra:totalItems": 1,
          "hydra:member": [{"externalIdentifier": "read_after_post", "amount": "10"}]
        }
        """

    Scenario:
    The transactions of another API client stay invisible

        Given I set header "Authorization" with value "Bearer api_key_test"

        When I send a GET request to "/api/transactions/a1b2c3d4-0000-4000-8000-000000000002"
        Then the response status code should be 404

        When I send a GET request to "/api/transactions?externalIdentifier=fixture_issued_by_other"
        Then the response status code should be 200
        And the JSON should contain:
        """
        {"hydra:totalItems": 0}
        """

    Scenario Outline:
    Reading transactions needs ROLE_TRANSACTION_READ

        Given I set header "Authorization" with value "<authorization>"

        When I send a GET request to "<url>"

        Then the response status code should be <code>

        Examples:
            | authorization         | url                                                           | code |
            |                       | /api/transactions/a1b2c3d4-0000-4000-8000-000000000001        | 401  |
            | Bearer api_key_reader | /api/transactions?externalIdentifier=fixture_issued_by_test   | 403  |
            # The item is looked up among the caller's own transactions before the role check: a 404, nothing leaks
            | Bearer api_key_reader | /api/transactions/a1b2c3d4-0000-4000-8000-000000000001        | 404  |

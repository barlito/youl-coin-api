@api @wallet

Feature:
    I want to test my Wallet GET endpoints

    Scenario Outline:
    Reading a wallet needs an API key with ROLE_WALLET_READ

        Given I set header "Authorization" with value "<authorization>"

        When I send a GET request to "/api/user/232457563910832129/wallet"

        Then the response status code should be <code>

        Examples:
            | authorization            | code |
            |                          | 401  |
            | Bearer bad_auth_token    | 401  |
            | Bearer api_key_bank_only | 403  |
            | Bearer api_key_reader    | 200  |

    Scenario Outline:
    I want to test endpoint errors

        Given I set header "Authorization" with value "Bearer api_key_reader"

        When I send a GET request to "/api/user/<discord_user_id>/wallet"

        Then the response status code should be 404

        Examples:
            | discord_user_id |
            | null            |
            |                 |
            | 123             |
            | azeaze          |

    Scenario:
    I want to test fields returned by the endpoint

        Given I set header "Authorization" with value "Bearer api_key_reader"

        When I send a GET request to "/api/user/232457563910832129/wallet"

        Then the response status code should be 200

        And JSON schema should validate Wallet class

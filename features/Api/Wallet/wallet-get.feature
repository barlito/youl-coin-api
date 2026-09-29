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

        When I send a GET request to "/api/user/188967649332428800/wallet"

        Then the response status code should be 200

        And the JSON should contain:
        """
        {
            "@type": "Wallet",
            "discordId": "188967649332428800",
            "amount": "900000000000",
            "type": "user",
            "name": "Wallet Barlito"
        }
        """

        And the JSON should not have the key "discordUser"
        And the JSON should not have the key "roles"

        And JSON schema should validate Wallet class

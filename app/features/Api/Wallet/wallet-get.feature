@api @wallet

Feature:
    I want to test my Wallet GET endpoints

    Scenario Outline:
    I want to test endpoint errors

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

        When I send a GET request to "/api/user/232457563910832129/wallet"

        Then the response status code should be 200

        And JSON schema should validate Wallet class

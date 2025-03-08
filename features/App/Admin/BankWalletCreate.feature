@app @admin

Feature:
  I want to test the BankWallet creation from BackOffice view

  Scenario:
    I want to create a BankWallet
    No BankWallet exist in the database
    BankWallet should be created successfully

    Given a "Wallet" entity found by "type=bank" should be deleted
    And an admin user is logged in

    When I submit the form with "Create" on button to "admin?crudAction=new&crudControllerFqcn=App\Controller\Admin\WalletCrudController" with body:
    """
    {
      "ea[newForm][btn]": "saveAndReturn",
      "Wallet[amount]": 123123,
      "Wallet[discordUser]": "",
      "Wallet[type]": "bank",
      "Wallet[name]": "jkljkljkllkjl"
    }
    """

    Then the response status code should be 200


  Scenario:
  I want to create a BankWallet
  BankWallet already exist in the database
  BankWallet should not be created successfully
  I should get an error

    When I submit the form with "Create" on button to "admin?crudAction=new&crudControllerFqcn=App\Controller\Admin\WalletCrudController" with body:
    """
    {
      "ea[newForm][btn]": "saveAndReturn",
      "Wallet[amount]": 123123,
      "Wallet[discordUser]": "",
      "Wallet[type]": "bank",
      "Wallet[name]": "jkljkljkllkjl"
    }
    """

    Then the response status code should be 422

  Scenario:
  I want to create a User Wallet
  User has no Wallet yet on database
  User Wallet should be created successfully

    Given a "Wallet" entity found by "discordUser=189029821328785409" should be deleted

    When I submit the form with "Create" on button to "admin?crudAction=new&crudControllerFqcn=App\Controller\Admin\WalletCrudController" with body:
    """
    {
      "ea[newForm][btn]": "saveAndReturn",
      "Wallet[amount]": 123123,
      "Wallet[discordUser]": "189029821328785409",
      "Wallet[type]": "user",
      "Wallet[name]": "jkljkljkllkjl"
    }
    """

    Then the response status code should be 200

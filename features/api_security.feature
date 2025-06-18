Feature: Bezpieczeństwo API
    Aby chronić dane aplikacji
    Jako klient API
    Powinienem mieć dostęp tylko do zasobów, do których mam uprawnienia

    Scenario: Próba dostępu do chronionego endpointu bez tokenu
        When I send a "GET" request to "/api/events"
        Then the response code should be 401

    Scenario: Zwykły użytkownik próbuje wylistować wszystkich użytkowników (brak dostępu)
        Given I am authenticated as "user@example.com" with password "password123"
        When I send a "GET" request to "/api/users"
        Then the response code should be 403

    Scenario: Administrator listuje wszystkich użytkowników (jest dostęp)
        Given I am authenticated as "admin@example.com" with password "adminpassword123"
        When I send a "GET" request to "/api/users"
        Then the response code should be 200

    Scenario: Administrator tworzy nowe wydarzenie (jest dostęp)
        Given I am authenticated as "admin@example.com" with password "adminpassword123"
        And I set the request body to:
    """
    {
        "title": "Nowe wydarzenie z testu Behat!",
        "description": "Opis...",
        "startDate": "2026-05-20 10:00:00",
        "capacity": 50
    }
    """
        When I send a "POST" request to "/api/events"
        Then the response code should be 201

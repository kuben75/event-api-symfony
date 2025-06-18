Feature: Zarządzanie ustawieniami wydarzenia (Klucz-Wartość)
    Aby móc dodawać elastyczne dane do wydarzeń
    Jako zautoryzowany użytkownik API
    Chcę móc zarządzać ustawieniami klucz-wartość dla konkretnego wydarzenia

    Background:
        Given I am authenticated as "organizer@example.com" with password "organizerpass"
        And I send a "POST" request to "/api/events" with body:
    """
    {
        "title": "Wydarzenie do testowania ustawień",
        "startDate": "2027-01-01 12:00:00",
        "capacity": 10
    }
    """
        And I store the value of JSON node "data.id" as "eventId"

    Scenario: Pomyślne dodanie nowego ustawienia do wydarzenia
        Given I set the request body to:
    """
    {
        "settingKey": "dress_code",
        "settingValue": "Smart Casual"
    }
    """
        When I send a "POST" request to "/api/events/{eventId}/settings"
        Then the response code should be 201
        And the response should contain "Setting added successfully."

    Scenario: Wyświetlanie wszystkich ustawień dla danego wydarzenia
        Given I send a "POST" request to "/api/events/{eventId}/settings" with body:
    """
    {
        "settingKey": "wifi_password",
        "settingValue": "SecretPassword123"
    }
    """
        When I send a "GET" request to "/api/events/{eventId}/settings"
        Then the response code should be 200
        And the JSON node "data.wifi_password" should be equal to "SecretPassword123"
    Scenario: Organizator pomyślnie aktualizuje ustawienie
        Given I send a "POST" request to "/api/events/{eventId}/settings" with body:
    """
    {
        "settingKey": "update_test_key",
        "settingValue": "Original Value"
    }
    """
        And I store the value of JSON node "data.id" as "settingId"
        And I set the request body to:
    """
    {
        "settingValue": "Updated Value"
    }
    """
        When I send a "PUT" request to "/api/settings/{settingId}"
        Then the response code should be 200
        And the JSON node "data.settingValue" should be equal to "Updated Value"

    Scenario: Organizator usuwa ustawienie
        Given I send a "POST" request to "/api/events/{eventId}/settings" with body:
    """
    {
        "settingKey": "to_be_deleted",
        "settingValue": "Some Value"
    }
    """
        And I store the value of JSON node "data.id" as "settingIdToDelete"
        When I send a "DELETE" request to "/api/settings/{settingIdToDelete}"
        Then the response code should be 204

    Scenario: Zwykły użytkownik próbuje usunąć ustawienie (brak dostępu)
        Given I send a "POST" request to "/api/events/{eventId}/settings" with body:
    """
    {
        "settingKey": "delete_permission_test",
        "settingValue": "Some Value"
    }
    """
        And I store the value of JSON node "data.id" as "settingId"
        Given I am authenticated as "user@example.com" with password "password123"
        When I send a "DELETE" request to "/api/settings/{settingId}"
        Then the response code should be 403

    Scenario: Organizator próbuje usunąć ustawienie nie swojego wydarzenia (brak dostępu)
        Given I am authenticated as "admin@example.com" with password "adminpassword123"
        And I send a "POST" request to "/api/events" with body:
    """
    {
        "title": "Admin's Super Secret Event",
        "startDate": "2028-01-01 12:00:00",
        "capacity": 5
    }
    """
        And I store the value of JSON node "data.id" as "adminEventId"
        And I send a "POST" request to "/api/events/{adminEventId}/settings" with body:
    """
    {
        "settingKey": "admin_only_key",
        "settingValue": "admin_value"
    }
    """
        And I store the value of JSON node "data.id" as "adminSettingId"
        Given I am authenticated as "organizer@example.com" with password "organizerpass"
        When I send a "DELETE" request to "/api/settings/{adminSettingId}"
        Then the response code should be 403

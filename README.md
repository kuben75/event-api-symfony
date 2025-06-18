# Event Management API

This is a RESTful API backend for managing events, participants, and the registration system, created as a final project for a Symfony course.

## Key Technologies

* PHP 8.x
* Symfony 7.x
* Doctrine ORM
* PostgreSQL
* Docker / Docker Compose
* FrankenPHP / Caddy Server
* JWT 
* PHPUnit 
* Behat 

## Prerequisites

* Docker installed and running.
* Docker Compose installed.


## Setup and Running Instructions

1.  **Clone the repository:**
    ```bash
    git clone [https://github.com/kuben75/event-api-symfony.git]
    ```

2.  **Build and run the Docker containers:**
    (Run this command in a terminal inside the project directory)
    ```bash
    docker-compose up --pull always -d --wait
    ```

3.  **Install Composer dependencies:**
    (Run this command in a terminal inside the project directory)
    ```bash
    docker compose exec php composer install
    ```

4.  **Run database migrations:**
    (Run this command in a terminal inside the project directory)
    ```bash
    docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
    ```

5.  **Load fixture data:**
    (Run this command in a terminal inside the project directory)
    ```bash
    docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
    ```

6.  **Done!** The application is up and running.
    * The main API is available at: `https://localhost`
    * Adminer (for database Browse) is available at: `http://localhost:8080`

## Running Tests

To run the automated test suites, use the following commands from within the project directory.

#### Running PHPUnit Tests

This command will execute all unit and functional tests written with PHPUnit.

```bash
docker compose exec php ./vendor/bin/phpunit
```

#### Running Behat Tests

This command requires a fresh set of fixture data. It's recommended to run the `fixtures:load` command for the test environment first.

```bash
# Step 1: Reload test database fixtures
docker compose exec php php bin/console doctrine:fixtures:load --env=test --no-interaction

# Step 2: Run Behat
docker compose exec php ./vendor/bin/behat
```

## API Usage

This repository includes the `Event Management API.postman_collection.json` file. You can import this collection into Postman to test all key API endpoints.

### Test Users

You can use the pre-loaded users to test different permission levels:

* **Admin:** `admin@example.com` / `adminpassword123`
* **Organizer:** `organizer@example.com` / `organizerpass`
* **User:** `user@example.com` / `password123`

### Creating Your Own User

You can also create your own user account via the public registration endpoint:

* **Endpoint:** `POST /api/register`
* **Body (JSON):**
    ```json
    {
        "email": "your_new_email@example.com",
        "password": "your_strong_password"
    }
    ```
After registering, you can log in via `POST /api/login_check` with your new credentials to get a JWT token.

## Custom CLI Commands

This project includes custom command-line interface (CLI) commands for administrative tasks. You must run these commands from within the project directory.

#### Assigning a Role to a User

This command grants a role (e.g., `ROLE_ORGANIZER`) to an existing user.

**Usage:**
```bash
docker compose exec php php bin/console app:user:assign-role <user_email> <role>
```
#### Revoking a Role from a User

This command removes a specific role from a user.

**Usage:**
```bash
docker compose exec php php bin/console app:user:revoke-role <user_email> <role>
```

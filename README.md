## Pentavalue AI Reporting Test

This is a system to manage and analyze sales data in real-time. The task will involve leveraging AI systems like OpenAI's ChatGPT or Gemini for assistance and generating recommendations, while other parts of the system must be written manually. The project
must include a real-time reporting feature and integration with external APIs.

## Specifications

Below are the specifications of dependencies:

-   Laravel version 12
-   PHP version 8.2

## Project Details

I tried to keep things quite simple within the project. I basically worked with:

-   Routes:
    -   Web: I kept only 2 GET routes - `Base (/)` and `Recommendations (/recommendations)`
    -   API: As required by the project - `Orders [POST: /orders]` and `Analytics [GET: /analytics]`
    -   Channel: I also have 2 public channel routes - `Orders (/orders)` and `Recommendations (/recommendations)`
-   Views:
    -   Welcome blade: This is the view that serves the base web route. It shows the dashboard chart and other analytics components. These components are updated using the CDN version of Laravel Echo + PusherJS and a bit of other javascript
    -   Recommendations: This blade shows the AI recommendations and current weather forecast too (hardcoded to Lagos, NG). The UI calls the analytics using the weather data and top orders from the SQLite to get recommendations from ChatGPT AI.
-   Controller:
    -   I have only used a controller class `OrderController` to manage the basic functions required for simplicity. In this class:
        -   store(): This method is used to store order and is linked to the `Orders [POST: /orders]` API route. After an order is created:
            -   An event is sent to pusher.com (I have my configs in the env) with the help of `artisan queue:work`
            -   We pull the current weather status (config also available in env) with the help of OpenWeatherApi
            -   Using the weather data and the order data, we prompt ChatGPT (config also in env) asking for recommendations
            -   Finally, we send the result from the recommendations prompt so we can update the UI
        -   analytics(): This method is used to pull analytical data from the SQLite database, adds the weather data pull using HttpGuzzle and finally recommendations from ChatGPT prompt using HttpGuzzle as well. This method is equally linked to the `Analytics [GET: /analytics]` API route as required by the task.
        -   generateRecommendations(): Used for prompting ChatGPT using HttpGuzzle
        -   getWeatherData(): Used for fetching weather info using HttpGuzzle
        -   getDefaultWeatherData(): Holds default/fallback data for weather data incase we are unable to reach OpenWeatherAPI
-   Request: `CreateOrderRequest` is the only request class used to validate the `Orders [POST: /orders]` API route POST request.
-   Repositories:
    -   OrderRepository: This was the only repository used since we have to deal with only 1 table. Of course, we had an interface for it as its blueprint for consistency. Within the OrderRepository:
        -   create(): This is used to write an order data to the order table using the `DB raw` class (avoiding the use of ORM)
        -   getTotalRevenue(): To make it easier like an ORM, this is a helper for pulling total revenue for statistics
        -   getTopProducts(): This is also a helper method within our repository to get top (10) products for display
        -   getRevenueLastMinute(): Also a helper method to fetch the revenue within the last minute
        -   getOrdersLastMinute(): A helper to fetch orders within the last minute
-   Events:
    -   OrderCreated: The event class for broadcasting new orders to pusher
    -   RecommendationsGenerated: The event class for broadcasting recommendations by ChatGPT

## Setting up the project locally

Before you start, ensure you have the following installed:

-   Docker (up-to-date will be just fine)
-   Web browser
-   Shell terminal environment

## Getting Started

1. **Clone the repository:**

    ```bash
    git clone https://github.com/degod/pentavalue-test.git
    ```

2. **Navigate to the project directory:**

    ```bash
    cd pentavalue-test/
    ```

3. **Install Composer dependencies:**

    ```bash
    docker-compose up --build -d
    ```

4. **Start the application with Laravel Sail:**

    ```bash
    docker exec -it pentavalue-app cp .env.example .env
    ```

5. **Start the application with Laravel Sail:**

    ```bash
    composer install
    ```

6. **Start the application with Laravel Sail:**

    ```bash
    docker exec -it pentavalue-app && php artisan key:generate
    ```

7. **Logging in to container shell:**

    ```bash
    docker exec -it pentavalue-app bash
    ```

8. **Running queue worker in container:**

    ```bash
    php artisan queue:work
    ```

9. **Exiting container shell:**
   First hit `control + C` on your keyboard to stop the worker in the terminal. Then...

    ```bash
    exit
    ```

10. **Accessing the application:**

-   The application should now be running on your local environment.
-   Navigate to `http://localhost:8088` in your browser to access the application and click the `See More` button for recommendations.
-   To go to recommendations directly, visit `http://localhost:8088/recommendations` for result.

11. **Stopping the application:**

    ```bash
    docker-compose down
    ```

## Contributing

If you encounter bugs or wish to contribute, please follow these steps:

-   Fork the repository and clone it locally.
-   Create a new branch (`git checkout -b feature/fix-issue`).
-   Make your changes and commit them (`git commit -am 'Fix issue'`).
-   Push to the branch (`git push origin feature/fix-issue`).
-   Create a new Pull Request against the `main` branch, tagging `@degod`.

## Contact

For inquiries or assistance, you can reach out to Godwin Uche:

-   `Email:` degodtest@gmail.com
-   `Phone:` +2348024245093

## Running and Setup

1. Run the project via docker compose
```bash
docker compose up
```

2. Install dependencies using Composer:

```bash
docker exec coaster_system_app composer install
```

3. Run monitor script
```bash
docker exec coaster_system_app php spark monitor:coasters
```

4. Check example api calls in `example/api-calls.http` file.
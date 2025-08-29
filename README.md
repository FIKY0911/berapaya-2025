## SETUP

***

- tambahkan di .env

```
PREDICT_KEY = berapaya
PREDICT_URL = http://berapaya:8000
API_KEY = test
```

- tambahkan di docker-compose.yml di line 53

```
networks:
      - default
      - shared_net
      
networks:
  default:
    driver: bridge
  shared_net:
    external: true
```

- tambahkan di docker-compose.yml di line 16, 32

```
networks:
      - default
      - shared_net
```

- shared network

```
docker network create shared_net
```

- running api predict

```
cd predict
docker compose up -d --build
```

- running api laravel

```
docker compose up -d --build
```

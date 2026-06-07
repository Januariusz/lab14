# Lab 14 – LEMP Stack z Docker Compose
Jakub Janusz 99553

---

## Struktura projektu

```
lemp-stack/
├── Dockerfile                   # obraz PHP z rozszerzeniem pdo_mysql
├── docker-compose.yaml          # definicja wszystkich usług
├── nginx/
│   └── conf.d/
│       └── default.conf         # konfiguracja Nginx + PHP-FPM
└── www/
    └── index.php                # strona startowa
```

---

## Plik docker-compose.yaml

```yaml
services:

  mysql:
    image: mysql:8.0
    container_name: mysql
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: testdb
      MYSQL_USER: lemp_user
      MYSQL_PASSWORD: lemp_password
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - backend

  php:
    build: .
    container_name: php
    volumes:
      - ./www:/var/www/html
    networks:
      - backend
    depends_on:
      - mysql

  nginx:
    image: nginx:1.25
    container_name: nginx
    ports:
      - "4001:80"
    volumes:
      - ./www:/var/www/html
      - ./nginx/conf.d:/etc/nginx/conf.d
    networks:
      - frontend
      - backend
    depends_on:
      - php

  phpmyadmin:
    image: phpmyadmin:5.2
    container_name: phpmyadmin
    ports:
      - "6001:80"
    environment:
      PMA_HOST: mysql
      MYSQL_ROOT_PASSWORD: rootpassword
    networks:
      - frontend
      - backend
    depends_on:
      - mysql

volumes:
  mysql_data:

networks:
  frontend:
  backend:
```

---
---

## Plik Dockerfile

```
FROM php:8.2-fpm
RUN docker-php-ext-install pdo_mysql
```

---

## Topologia sieci

| Kontener     | Obraz          | Sieci              | Port zewnętrzny |
|--------------|----------------|--------------------|-----------------|
| `mysql`      | mysql:8.0      | backend            | –               |
| `php`        | php:8.2-fpm    | backend            | –               |
| `nginx`      | nginx:1.25     | frontend + backend | **4001**        |
| `phpmyadmin` | phpmyadmin:5.2 | frontend + backend | **6001**        |

**Uzasadnienie:**

- **Sieć `backend`** – sieć wewnętrzna, izolowana od świata zewnętrznego. Należą do niej: `mysql`, `php`, `nginx`, `phpmyadmin`. Dzięki temu MySQL nie jest bezpośrednio eksponowany na zewnątrz.
- **Sieć `frontend`** – sieć "zewnętrzna", przez którą usługi są dostępne przez porty hosta. Należą do niej: `nginx` (port 4001), `phpmyadmin` (port 6001).
- **phpMyAdmin** jest dołączony do **obu sieci**: do `backend`, bo musi się łączyć z kontenerem `mysql` przez wewnętrzną sieć, i do `frontend`, bo użytkownik musi się do niego dostać przez przeglądarkę (port 6001).

---

## Polecenia – uruchomienie i weryfikacja

### 1. Uruchomienie stosu w trybie detach

```bash
docker compose up -d
```

**Wynik:**
```
[+] Building 3.8s (8/8) FINISHED                                                                                    
 => [internal] load local bake definitions                                                                     0.0s
 => => reading from stdin 510B                                                                                 0.0s
 => [internal] load build definition from Dockerfile                                                           0.0s
 => => transferring dockerfile: 91B                                                                            0.0s
 => [internal] load metadata for docker.io/library/php:8.2-fpm                                                 0.0s
 => [internal] load .dockerignore                                                                              0.0s
 => => transferring context: 2B                                                                                0.0s
 => [1/2] FROM docker.io/library/php:8.2-fpm@sha256:ea44c48c4612a224d0a5dbe95bb924d9017447d851f0cc38cfee7d571  0.3s
 => => resolve docker.io/library/php:8.2-fpm@sha256:ea44c48c4612a224d0a5dbe95bb924d9017447d851f0cc38cfee7d571  0.0s
 => [2/2] RUN docker-php-ext-install pdo_mysql                                                                 3.0s
 => exporting to image                                                                                         0.2s 
 => => exporting layers                                                                                        0.1s 
 => => exporting manifest sha256:d0d5d038da4219fce54a8c824567f86e21ec6655df007bb60610b9154c460968              0.0s 
 => => exporting config sha256:c80b763456cd1e784afbd177de02d6b955e075a1a6374050627163c80950a3e5                0.0s 
 => => exporting attestation manifest sha256:e8d892f5563c4d0a9fc1f33e47403ed2f2b9e23812b8010c2d78f3de2e2c61d9  0.0s 
 => => exporting manifest list sha256:4bcecf4b8d8ad8854bae05f73f42e06660d6a98a2779c1794b8f306024224f8d         0.0s 
 => => naming to docker.io/library/lemp-stack-php:latest                                                       0.0s
 => => unpacking to docker.io/library/lemp-stack-php:latest                                                    0.0s
 => resolving provenance for metadata file                                                                     0.0s
[+] up 7/7
 ✔ Image lemp-stack-php        Built                                                                            3.8s
 ✔ Network lemp-stack_frontend Created                                                                          0.1s
 ✔ Network lemp-stack_backend  Created                                                                          0.1s
 ✔ Container mysql             Started                                                                          0.3s
 ✔ Container php               Started                                                                          0.4s
 ✔ Container phpmyadmin        Started                                                                          0.4s
 ✔ Container nginx             Started     
```

---

### 2. Weryfikacja działających kontenerów

```bash
docker compose ps
```

**Wynik:**
```
NAME         IMAGE            COMMAND                  SERVICE      CREATED          STATUS          PORTS
mysql        mysql:8.0        "docker-entrypoint.s…"   mysql        34 seconds ago   Up 33 seconds   3306/tcp, 33060/tcp
nginx        nginx:1.25       "/docker-entrypoint.…"   nginx        34 seconds ago   Up 33 seconds   0.0.0.0:4001->80/tcp, [::]:4001->80/tcp
php          lemp-stack-php   "docker-php-entrypoi…"   php          34 seconds ago   Up 33 seconds   9000/tcp
phpmyadmin   phpmyadmin:5.2   "/docker-entrypoint.…"   phpmyadmin   34 seconds ago   Up 33 seconds   0.0.0.0:6001->80/tcp, [::]:6001->80/tcp
```

---

### 3. Sprawdzenie sieci – backend

```bash
docker network inspect lemp-stack_backend | jq '.[].Containers | to_entries[] | {name: .value.Name, ip: .value.IPv4Address}'
```

**Wynik:**
```json
{
  "name": "php",
  "ip": "172.19.0.3/16"
}
{
  "name": "phpmyadmin",
  "ip": "172.19.0.4/16"
}
{
  "name": "nginx",
  "ip": "172.19.0.5/16"
}
{
  "name": "mysql",
  "ip": "172.19.0.2/16"
}
```

---

### 4. Sprawdzenie sieci – frontend

```bash
docker network inspect lemp-stack_frontend | jq '.[].Containers | to_entries[] | {name: .value.Name, ip: .value.IPv4Address}'
```

**Wynik:**
```json
{
  "name": "phpmyadmin",
  "ip": "172.18.0.2/16"
}
{
  "name": "nginx",
  "ip": "172.18.0.3/16"
}
```

---

### 5. Dowód działania LEMP – strona startowa PHP

```bash
curl -s http://localhost:4001 | grep -o '<title>.*</title>'
```

**Wynik:**
```
<title>LEMP Stack</title>
```

Lub: otwórz w przeglądarce **http://localhost:4001** (widoczne na `1.png`).

---

### 6. Dowód działania bazy danych – inicjalizacja testowej bazy

```bash
docker exec -it mysql mysql -u root -prootpassword -e "SHOW DATABASES;"
```

**Wynik:**
```
mysql: [Warning] Using a password on the command line interface can be insecure.
+--------------------+
| Database           |
+--------------------+
| information_schema |
| mysql              |
| performance_schema |
| sys                |
| testdb             |
+--------------------+
```

```bash
docker exec -it mysql mysql -u lemp_user -plemp_password testdb -e "SELECT * FROM users;"
```

**Wynik (po odwiedzeniu strony):**
```
+----+--------------+---------------------+
| id | name         | created_at          |
+----+--------------+---------------------+
|  1 | Jan Kowalski | 2026-06-07 09:35:06 |
|  2 | Anna Nowak   | 2026-06-07 09:35:06 |
+----+--------------+---------------------+
```

---

### 7. Ręczne założenie testowej bazy w MySQL

```bash
docker exec -it mysql mysql -u root -prootpassword -e \
  "CREATE DATABASE IF NOT EXISTS test_manual; SHOW DATABASES;"
```

**Wynik:**
```
+--------------------+
| Database           |
+--------------------+
| information_schema |
| mysql              |
| performance_schema |
| sys                |
| test_manual        |
| testdb             |
+--------------------+
```

---

### 8. Logowanie do phpMyAdmin

Otwórz w przeglądarce: **http://localhost:6001**

| Pole       | Wartość      |
|------------|--------------|
| Serwer     | mysql        |
| Użytkownik | root         |
| Hasło      | rootpassword |

Widoczne na `2.png` i `3.png`.

---

### 9. Przeglądanie logów usługi

```bash
docker compose logs
```

**Wynik:**
```
php  | [07-Jun-2026 09:34:37] NOTICE: fpm is running, pid 1
php  | [07-Jun-2026 09:34:37] NOTICE: ready to handle connections
php  | 172.19.0.5 -  07/Jun/2026:09:35:06 +0000 "GET /index.php" 200
mysql  | 2026-06-07 09:34:37+00:00 [Note] [Entrypoint]: Entrypoint script for MySQL Server 8.0.46-1.el9 started.
mysql  | 2026-06-07 09:34:37+00:00 [Note] [Entrypoint]: Switching to dedicated user 'mysql'
mysql  | 2026-06-07 09:34:37+00:00 [Note] [Entrypoint]: Entrypoint script for MySQL Server 8.0.46-1.el9 started.
mysql  | '/var/lib/mysql/mysql.sock' -> '/var/run/mysqld/mysqld.sock'
mysql  | 2026-06-07T09:34:38.178432Z 0 [Warning] [MY-011068] [Server] The syntax '--skip-host-cache' is deprecated and will be removed in a future release. Please use SET GLOBAL host_cache_size=0 instead.
mysql  | 2026-06-07T09:34:38.179257Z 0 [System] [MY-010116] [Server] /usr/sbin/mysqld (mysqld 8.0.46) starting as process 1
mysql  | 2026-06-07T09:34:38.182218Z 1 [System] [MY-013576] [InnoDB] InnoDB initialization has started.
mysql  | 2026-06-07T09:34:38.325393Z 1 [System] [MY-013577] [InnoDB] InnoDB initialization has ended.
mysql  | 2026-06-07T09:34:38.463755Z 0 [Warning] [MY-010068] [Server] CA certificate ca.pem is self signed.
mysql  | 2026-06-07T09:34:38.463771Z 0 [System] [MY-013602] [Server] Channel mysql_main configured to support TLS. Encrypted connections are now supported for this channel.
mysql  | 2026-06-07T09:34:38.466989Z 0 [Warning] [MY-011810] [Server] Insecure configuration for --pid-file: Location '/var/run/mysqld' in the path is accessible to all OS users. Consider choosing a different directory.
mysql  | 2026-06-07T09:34:38.476338Z 0 [System] [MY-010931] [Server] /usr/sbin/mysqld: ready for connections. Version: '8.0.46'  socket: '/var/run/mysqld/mysqld.sock'  port: 3306  MySQL Community Server - GPL.
mysql  | 2026-06-07T09:34:38.476364Z 0 [System] [MY-011323] [Server] X Plugin ready for connections. Bind-address: '::' port: 33060, socket: /var/run/mysqld/mysqlx.sock
```

```bash
docker compose logs nginx
```

**Wynik:**
```
nginx  | /docker-entrypoint.sh: /docker-entrypoint.d/ is not empty, will attempt to perform configuration
nginx  | /docker-entrypoint.sh: Looking for shell scripts in /docker-entrypoint.d/
nginx  | /docker-entrypoint.sh: Launching /docker-entrypoint.d/10-listen-on-ipv6-by-default.sh
nginx  | 10-listen-on-ipv6-by-default.sh: info: Getting the checksum of /etc/nginx/conf.d/default.conf
nginx  | 10-listen-on-ipv6-by-default.sh: info: /etc/nginx/conf.d/default.conf differs from the packaged version
nginx  | /docker-entrypoint.sh: Sourcing /docker-entrypoint.d/15-local-resolvers.envsh
nginx  | /docker-entrypoint.sh: Launching /docker-entrypoint.d/20-envsubst-on-templates.sh
nginx  | /docker-entrypoint.sh: Launching /docker-entrypoint.d/30-tune-worker-processes.sh
nginx  | /docker-entrypoint.sh: Configuration complete; ready for start up
nginx  | 2026/06/07 09:34:38 [notice] 1#1: using the "epoll" event method
nginx  | 2026/06/07 09:34:38 [notice] 1#1: nginx/1.25.5
nginx  | 2026/06/07 09:34:38 [notice] 1#1: built by gcc 12.2.0 (Debian 12.2.0-14) 
nginx  | 2026/06/07 09:34:38 [notice] 1#1: OS: Linux 7.0.11-arch1-1
nginx  | 2026/06/07 09:34:38 [notice] 1#1: getrlimit(RLIMIT_NOFILE): 1024:524288
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker processes
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 28
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 29
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 30
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 31
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 32
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 33
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 34
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 35
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 36
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 37
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 38
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 39
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 40
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 41
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 42
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 43
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 44
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 45
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 46
nginx  | 2026/06/07 09:34:38 [notice] 1#1: start worker process 47
nginx  | 172.19.0.1 - - [07/Jun/2026:09:35:06 +0000] "GET / HTTP/1.1" 200 1081 "-" "Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0" "-"
```

```bash
docker compose logs mysql
```

**Wynik:**
```
mysql  | 2026-06-07 09:34:37+00:00 [Note] [Entrypoint]: Entrypoint script for MySQL Server 8.0.46-1.el9 started.
mysql  | 2026-06-07 09:34:37+00:00 [Note] [Entrypoint]: Switching to dedicated user 'mysql'
mysql  | 2026-06-07 09:34:37+00:00 [Note] [Entrypoint]: Entrypoint script for MySQL Server 8.0.46-1.el9 started.
mysql  | '/var/lib/mysql/mysql.sock' -> '/var/run/mysqld/mysqld.sock'
mysql  | 2026-06-07T09:34:38.178432Z 0 [Warning] [MY-011068] [Server] The syntax '--skip-host-cache' is deprecated and will be removed in a future release. Please use SET GLOBAL host_cache_size=0 instead.
mysql  | 2026-06-07T09:34:38.179257Z 0 [System] [MY-010116] [Server] /usr/sbin/mysqld (mysqld 8.0.46) starting as process 1
mysql  | 2026-06-07T09:34:38.182218Z 1 [System] [MY-013576] [InnoDB] InnoDB initialization has started.
mysql  | 2026-06-07T09:34:38.325393Z 1 [System] [MY-013577] [InnoDB] InnoDB initialization has ended.
mysql  | 2026-06-07T09:34:38.463755Z 0 [Warning] [MY-010068] [Server] CA certificate ca.pem is self signed.
mysql  | 2026-06-07T09:34:38.463771Z 0 [System] [MY-013602] [Server] Channel mysql_main configured to support TLS. Encrypted connections are now supported for this channel.
mysql  | 2026-06-07T09:34:38.466989Z 0 [Warning] [MY-011810] [Server] Insecure configuration for --pid-file: Location '/var/run/mysqld' in the path is accessible to all OS users. Consider choosing a different directory.
mysql  | 2026-06-07T09:34:38.476338Z 0 [System] [MY-010931] [Server] /usr/sbin/mysqld: ready for connections. Version: '8.0.46'  socket: '/var/run/mysqld/mysqld.sock'  port: 3306  MySQL Community Server - GPL.
mysql  | 2026-06-07T09:34:38.476364Z 0 [System] [MY-011323] [Server] X Plugin ready for connections. Bind-address: '::' port: 33060, socket: /var/run/mysqld/mysqlx.sock
```

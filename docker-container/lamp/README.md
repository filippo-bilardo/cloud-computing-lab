# LAMP Stack — Docker Container

Stack LAMP containerizzato con **Apache + PHP 8.2**, **MariaDB 10.11** e supporto per reverse proxy tramite Nginx Proxy Manager.

---

## Architettura

```
┌─────────────────────────────────────────────────┐
│              lamp_network (bridge)              │
│  (rete esterna: nginx_proxy_network)            │
│                                                 │
│  ┌──────────────────┐   ┌──────────────────┐    │
│  │  lamp_webserver  │   │  lamp_mariadb    │    │
│  │  PHP 8.2.5       │──>│  MariaDB 10.11   │    │
│  │  Apache 2.4      │   │                  │    │
│  │  porta 80, 8888  │   │  porta 3306      │    │
│  └──────────────────┘   └──────────────────┘    │
└─────────────────────────────────────────────────┘
```

### Servizi

| Servizio | Immagine | Container | Ruolo |
|---|---|---|---|
| `webserver` | `php:8.2.5-apache-bullseye` | `lamp_webserver` | Web server Apache + PHP |
| `mariadb` | `mariadb:10.11` | `lamp_mariadb` | Database MySQL-compatibile |

### Volumi e mount

| Sorgente (host) | Destinazione (container) | Descrizione |
|---|---|---|
| `./volumes/www/` | `/var/www/html/` | Radice web (DocumentRoot) |
| `./volumes/config/apache/apache2.conf` | `/etc/apache2/apache2.conf` | Configurazione Apache principale |
| `./volumes/config/apache/sites-available/` | `/etc/apache2/sites-available/` | Virtual host disponibili |
| `./volumes/config/apache/sites-enabled/` | `/etc/apache2/sites-enabled/` | Virtual host attivi |
| `mysql_data` (named volume) | `/var/lib/mysql` | Dati MariaDB persistenti |

### Rete

I container condividono la rete esterna `nginx_proxy_network` (creata da Nginx Proxy Manager), che consente il routing del traffico HTTP/HTTPS verso il webserver senza esporre direttamente le porte sull'host.

---

## Prerequisiti

- Docker Engine ≥ 24
- Docker Compose v2
- Rete esterna `nginx_proxy_network` già esistente (creata da Nginx Proxy Manager)

Verifica la rete:
```bash
docker network ls | grep nginx_proxy_network
```

Se non esiste, creala manualmente:
```bash
docker network create nginx_proxy_network
```

---

## Configurazione

Le variabili d'ambiente sono gestite tramite il file `.env`. Il file `.env.example` contiene il template con i valori predefiniti.

```bash
# Copiare il template (solo al primo utilizzo)
cp .env.example .env
```

Modificare `.env` con le proprie credenziali:

```ini
# Database
MYSQL_ROOT_PASSWORD=lamp        # Password root MariaDB
MYSQL_USER=lamp                 # Utente applicazione
MYSQL_PASSWORD=lamp             # Password utente applicazione

# Admin webserver (usato dall'applicazione PHP)
WEBSERVER_ADMIN_USERNAME=admin
WEBSERVER_ADMIN_PASSWORD=admin

# Connessione DB lato PHP
DB_USERNAME=lamp
DB_PASSWORD=lamp
DB_HOST=mariadb                 # Nome del servizio nella rete Docker
```

> **Nota di sicurezza**: Non committare mai il file `.env` con credenziali reali. Il file è già incluso nel `.gitignore`.

---

## Creazione e avvio

### Primo avvio

```bash
# Posizionarsi nella directory del progetto
cd docker-container/lamp

# Copiare la configurazione (se non già fatto)
cp .env.example .env

# Avviare i container in background
docker compose up -d
```

Docker Compose eseguirà automaticamente le seguenti operazioni:
1. Pull delle immagini `mariadb:10.11` e `php:8.2.5-apache-bullseye`
2. Creazione dei container `lamp_mariadb` e `lamp_webserver`
3. Avvio di MariaDB (il webserver attende che MariaDB superi l'healthcheck prima di partire)
4. Montaggio dei volumi e delle directory locali

### Avvii successivi

```bash
docker compose up -d
```

---

## Gestione

### Stato dei container

```bash
# Stato e healthcheck
docker compose ps

# Log in tempo reale di tutti i servizi
docker compose logs -f

# Log del solo webserver
docker compose logs -f webserver

# Log del solo database
docker compose logs -f mariadb
```

### Fermare e riavviare

```bash
# Fermare i container (i dati vengono preservati)
docker compose stop

# Fermare e rimuovere i container (i dati nel volume mysql_data vengono preservati)
docker compose down

# Riavviare un singolo servizio
docker compose restart webserver
docker compose restart mariadb
```

### Accesso ai container

```bash
# Shell nel webserver
docker exec -it lamp_webserver bash

# Shell nel database (client MySQL)
docker exec -it lamp_mariadb mysql -u lamp -p

# Client MySQL come root
docker exec -it lamp_mariadb mysql -u root -p
```

### Operazioni sul database

```bash
# Backup del database
docker exec lamp_mariadb mysqldump -u root -p<ROOT_PASSWORD> --all-databases > backup.sql

# Ripristino del database
docker exec -i lamp_mariadb mysql -u root -p<ROOT_PASSWORD> < backup.sql
```

---

## Struttura delle directory

```
lamp/
├── .env                        # Variabili d'ambiente (non versionato)
├── .env.example                # Template delle variabili
├── .gitignore
├── docker-compose.yml
└── volumes/
    ├── config/
    │   └── apache/
    │       ├── apache2.conf            # Configurazione principale Apache
    │       ├── sites-available/
    │       │   └── 000-default.conf    # VirtualHost di default (porta 80)
    │       └── sites-enabled/         # Symlink ai siti attivi
    └── www/
        └── index.php                  # Pagina di test (verifica PHP e DB)
```

### Aggiungere file PHP/HTML

Copiare i file nella directory `volumes/www/`. Sono immediatamente disponibili senza riavviare il container:

```bash
cp mio-script.php volumes/www/
```

### Aggiungere un Virtual Host

1. Creare il file di configurazione in `volumes/config/apache/sites-available/miosito.conf`
2. Creare il symlink in `sites-enabled/`:
   ```bash
   docker exec lamp_webserver a2ensite miosito.conf
   docker exec lamp_webserver service apache2 reload
   ```

---

## Verifica del funzionamento

La pagina `volumes/www/index.php` esegue automaticamente:
- Visualizzazione della versione PHP attiva
- Test di connessione al database MariaDB

Accedere tramite il browser all'URL configurato nel reverse proxy, oppure verificare la porta esposta:

```bash
# Individuare la porta host assegnata automaticamente
docker compose ps

# Test rapido da riga di comando
curl http://localhost:<PORTA>/
```

---

## Risoluzione dei problemi

| Sintomo | Causa probabile | Soluzione |
|---|---|---|
| `webserver` non parte | MariaDB non ancora healthy | Attendere o controllare `docker compose logs mariadb` |
| Errore connessione DB in PHP | Credenziali `.env` errate | Verificare `.env` e riavviare con `docker compose up -d` |
| Rete non trovata | `nginx_proxy_network` mancante | `docker network create nginx_proxy_network` |
| Modifiche PHP non visibili | File non nel volume corretto | Verificare che i file siano in `volumes/www/` |

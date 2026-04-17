# LAMP Stack — Docker Container + Kanban Task Manager

Stack LAMP containerizzato con **Apache + PHP 8.2**, **MariaDB 10.11** e applicazione **Kanban Board** per la gestione dei task stile Trello.

---

## Applicazione: Kanban Task Manager

L'applicazione nella cartella `volumes/www/` è una **Kanban Board** moderna con tema dark/indigo che permette di gestire task in tre colonne con persistenza su MariaDB.

```
volumes/www/
├── index.php   ← Frontend Kanban (HTML + CSS + JS + Sortable.js)
├── api.php     ← REST API JSON (create/read/update/delete/move)
└── db.php      ← Connessione PDO + auto-init DB + seed iniziale
```

### Funzionalità

| Feature | Descrizione |
|---------|-------------|
| **3 colonne Kanban** | 📋 Da Fare / 🔄 In Corso / ✅ Completato |
| **Drag & drop** | Sposta task tra colonne trascinando (Sortable.js) |
| **CRUD completo** | Aggiungi, modifica, elimina task tramite modal |
| **Priorità** | 🔴 Alta / 🟠 Media / 🟢 Bassa con badge colorati |
| **Tag** | Etichette personalizzate separati da virgola |
| **Scadenza** | Campo data con evidenziazione rosso se scaduta |
| **Toast** | Notifiche animate per ogni operazione |
| **Auto-init DB** | `db.php` crea DB, tabella e task iniziali al primo avvio |
| **REST API** | `api.php` espone endpoint JSON testabili da browser/curl |

### API Endpoints

| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/api.php?action=ping` | GET | Health check: versione PHP + nome DB |
| `/api.php?action=list` | GET | Lista tutti i task (JSON) |
| `/api.php?action=create` | POST | Crea nuovo task (body JSON) |
| `/api.php?action=update` | POST | Modifica task esistente (body JSON con `id`) |
| `/api.php?action=move` | POST | Sposta task in colonna diversa (`id` + `status`) |
| `/api.php?action=delete` | POST | Elimina task (`id`) |

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
│  │  Apache 2.4      │   │  DB: taskmanager │    │
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
| `./volumes/www/` | `/var/www/html/` | Radice web — modifica i file PHP qui |
| `./volumes/config/apache/apache2.conf` | `/etc/apache2/apache2.conf` | Configurazione Apache principale |
| `./volumes/config/apache/sites-available/` | `/etc/apache2/sites-available/` | Virtual host disponibili |
| `./volumes/config/apache/sites-enabled/` | `/etc/apache2/sites-enabled/` | Virtual host attivi |
| `mysql_data` (named volume) | `/var/lib/mysql` | Dati MariaDB persistenti |

---

## Prerequisiti

- Docker Engine ≥ 24
- Docker Compose v2
- Rete esterna `nginx_proxy_network` già esistente (creata da Nginx Proxy Manager)

```bash
# Verifica rete — se non esiste, creala
docker network ls | grep nginx_proxy_network || docker network create nginx_proxy_network
```

---

## Configurazione

```bash
cp .env.example .env   # solo al primo utilizzo
```

Variabili principali in `.env`:

```ini
MYSQL_ROOT_PASSWORD=lamp
MYSQL_USER=lamp
MYSQL_PASSWORD=lamp
MYSQL_DATABASE=taskmanager   # nome del database creato automaticamente
DB_HOST=mariadb              # nome del servizio nella rete Docker
```

> **Sicurezza**: Non committare mai `.env` con credenziali reali. Il file è già nel `.gitignore`.

---

## Creazione e avvio

```bash
cd docker-container/lamp
cp .env.example .env          # configura le credenziali

docker compose up -d          # avvia entrambi i container in background
docker compose ps             # verifica che entrambi siano "Up (healthy)"
```

Al primo avvio `db.php` crea automaticamente:
1. Il database `taskmanager`
2. La tabella `tasks`
3. I task iniziali dell'esercitazione (seed data)

### Verifica rapida

```bash
# Health check dell'API
curl http://localhost:8888/api.php?action=ping
# → {"status":"ok","db":"taskmanager","php":"8.2.x"}

# Dashboard nel browser
open http://localhost:8888
```

---

## Gestione

```bash
# Logs in tempo reale
docker compose logs -f

# Fermare senza perdere dati
docker compose stop

# Fermare e rimuovere container (i dati nel volume mysql_data restano)
docker compose down

# Shell nel webserver
docker exec -it lamp_webserver bash

# Client MariaDB
docker exec -it lamp_mariadb mysql -u lamp -p taskmanager
```

---

## Aggiungere file PHP

I file in `volumes/www/` sono immediatamente disponibili senza riavviare:

```bash
cp mio-script.php volumes/www/
# Accessibile su http://localhost:8888/mio-script.php
```

---

## Risoluzione dei problemi

| Sintomo | Causa probabile | Soluzione |
|---------|-----------------|-----------|
| `webserver` non parte | MariaDB healthcheck non superato | Attendi ~40s o `docker compose logs mariadb` |
| Errore connessione DB in PHP | Credenziali `.env` errate | Verifica `.env` e `docker compose up -d` |
| Rete non trovata | `nginx_proxy_network` mancante | `docker network create nginx_proxy_network` |
| Task non compaiono | DB non ancora inizializzato | Attendi qualche secondo e ricarica la pagina |
| `port is already allocated` | Porta 8888 occupata | Modifica la porta in `docker-compose.yml` |

---

---

# 🔬 Esercizio A: Setup Repository e GitHub Codespace (adattato allo stack LAMP)

> Esercitazione originale:
> [Cloud-Computing/ES01-Docker_Nodejs_Java/esercizio_a.md](https://github.com/filippo-bilardo/Cloud-Computing/blob/main/03-Architetture_Cloud_e_Container/ES01-Docker_Nodejs_Java/esercizio_a.md)
> — adattata al container **LAMP (PHP + MariaDB)** del repository `cloud-computing-lab`.

## Obiettivo

Avviare e testare uno stack LAMP containerizzato con Docker Compose,
esplorare il codice PHP e l'API REST, e pubblicare il lavoro su GitHub
rendendolo accessibile tramite GitHub Codespaces.

## Competenze

✅ Avviare uno stack multi-container con Docker Compose  
✅ Testare una REST API PHP con curl e browser  
✅ Modificare file PHP in un volume Docker  
✅ Fare commit e push su GitHub  
✅ Aprire il progetto in GitHub Codespaces  

---

## Parte 1: Setup Repository GitHub

### Step 1.1: Crea il repository

1. Vai su [github.com](https://github.com/) e accedi
2. Click su **New repository** (verde)
3. Compila:
   - Repository name: `cloud-computing-lab`
   - Description: `"Node.js + Java + PHP Docker lab"`
   - Public ✅
   - Initialize with README ✅
4. Click **Create repository**

### Step 1.2: Clone in locale (opzionale)

```bash
git clone https://github.com/TUO_USERNAME/cloud-computing-lab.git
cd cloud-computing-lab
```

> ⚠️ Puoi anche usare l'editor web GitHub (tasto `.` sul repository)

---

## Parte 2: Configurare il Dev Container

### Step 2.1: Struttura cartelle

Il repository deve avere questa struttura:

```
cloud-computing-lab/
├── .devcontainer/
│   └── devcontainer.json
└── docker-container/
    └── lamp/
        ├── Dockerfile
        ├── docker-compose.yml
        ├── .env.example
        └── volumes/
            └── www/
                ├── index.php   ← Kanban Board
                ├── api.php     ← REST API
                └── db.php      ← Connessione DB
```

### Step 2.2: Crea `.devcontainer/devcontainer.json`

```jsonc
{
  "name": "Node.js Lab",
  "image": "mcr.microsoft.com/devcontainers/base:debian",

  "features": {
    "ghcr.io/devcontainers/features/node:1": { "version": "24" },
    "ghcr.io/devcontainers/features/docker-in-docker:2": { "moby": false }
  },

  "postCreateCommand": "docker --version && node --version",

  "customizations": {
    "vscode": {
      "extensions": [
        "dbaeumer.vscode-eslint",
        "ms-azuretools.vscode-docker"
      ]
    }
  },

  "forwardPorts": [80, 3000, 8080, 8888]
}
```

> 📝 `"moby": false` è necessario su Debian trixie (l'immagine base attuale).  
> `forwardPorts` include 8888 per il webserver LAMP.

Commit: `"Add devcontainer config"`

---

## Parte 3: Avviare lo stack LAMP

### Step 3.1: Configura le variabili d'ambiente

```bash
cd docker-container/lamp
cp .env.example .env
# Modifica .env se vuoi cambiare password e database
```

### Step 3.2: Avvia i container

```bash
docker compose up -d
```

Docker Compose eseguirà:
1. Pull delle immagini `php:8.2.5-apache-bullseye` e `mariadb:10.11`
2. Avvio di MariaDB (attende l'healthcheck prima del webserver)
3. Mount di `volumes/www/` come DocumentRoot di Apache

### Step 3.3: Verifica lo stato

```bash
docker compose ps
```

Output atteso (entrambi i container devono essere `Up` o `Up (healthy)`):

```
NAME              IMAGE                        STATUS
lamp_webserver    php:8.2.5-apache-bullseye    Up (healthy)
lamp_mariadb      mariadb:10.11                Up (healthy)
```

---

## Parte 4: Esplorare il codice PHP

### Step 4.1: `volumes/www/db.php`

Questo file:
- Si connette a MariaDB tramite PDO usando le variabili d'ambiente
- Crea il database `taskmanager` se non esiste
- Crea la tabella `tasks` se non esiste
- Inserisce i task iniziali dell'esercitazione al primo avvio

**Concetti chiave:**
- `getenv('DB_HOST')` — legge variabili d'ambiente iniettate da Docker Compose
- `PDO` — PHP Data Objects, interfaccia sicura per database
- `prepare()` + `execute()` — query parametrizzate (protezione da SQL injection)

### Step 4.2: `volumes/www/api.php`

Questo file espone una **REST API** JSON. Ogni richiesta specifica l'azione
tramite il parametro `?action=`:

```bash
# Health check
curl http://localhost:8888/api.php?action=ping

# Lista tutti i task
curl http://localhost:8888/api.php?action=list

# Crea un nuovo task
curl -X POST http://localhost:8888/api.php?action=create \
  -H "Content-Type: application/json" \
  -d '{"title":"Nuovo task","status":"todo","priority":"alta","tags":"test"}'

# Sposta task in 'done'
curl -X POST http://localhost:8888/api.php?action=move \
  -H "Content-Type: application/json" \
  -d '{"id":1,"status":"done"}'

# Elimina task
curl -X POST http://localhost:8888/api.php?action=delete \
  -H "Content-Type: application/json" \
  -d '{"id":1}'
```

### Step 4.3: `volumes/www/index.php`

Il frontend è un'applicazione a pagina singola (SPA) che:
- Carica i task dal backend via `fetch('/api.php?action=list')`
- Rende le tre colonne Kanban con JavaScript
- Usa [Sortable.js](https://sortablejs.github.io/Sortable/) per il drag & drop
- Chiama l'API per ogni operazione CRUD

---

## Parte 5: Aprire in GitHub Codespaces

### Step 5.1: Crea il Codespace

1. Vai sul repository GitHub
2. Click su **Code** (verde) → tab **Codespaces**
3. Click **Create codespace on main**
4. Attendi 1–2 minuti → VS Code si apre nel browser

### Step 5.2: Verifica installazioni

Nel terminale del Codespace:

```bash
docker --version   # Docker 24.x
node --version     # v24.x
```

### Step 5.3: Avvia lo stack nel Codespace

```bash
cd docker-container/lamp
cp .env.example .env
docker compose up -d
docker compose ps
```

VS Code mostra la notifica **"Port 8888 is available"** → click per aprire nel browser.

---

## Parte 6: Testare l'applicazione

### Test via browser

| URL | Descrizione |
|-----|-------------|
| `http://localhost:8888/` | Dashboard Kanban Board |
| `http://localhost:8888/api.php?action=ping` | Health check |
| `http://localhost:8888/api.php?action=list` | JSON con tutti i task |

### Test via curl (terminale)

```bash
# Verifica salute dell'app
curl -s http://localhost:8888/api.php?action=ping | python3 -m json.tool

# Risposta attesa:
# {
#     "status": "ok",
#     "db": "taskmanager",
#     "php": "8.2.x"
# }

# Lista task
curl -s http://localhost:8888/api.php?action=list | python3 -m json.tool
```

### Test operazioni CRUD (interfaccia grafica)

1. **Aggiungi task**: click su **＋ Nuovo Task** in alto a destra
2. **Modifica task**: hover sulla card → click ✏️
3. **Sposta task**: trascina la card in un'altra colonna
4. **Elimina task**: hover sulla card → click 🗑️ → conferma

---

## Parte 7: Docker Build manuale (avanzato)

```bash
cd docker-container/lamp

# Build dell'immagine PHP personalizzata (con estensione pdo_mysql)
docker build -t lamp-php:1.0 .

# Verifica l'immagine
docker images lamp-php

# Esamina i layer
docker history lamp-php:1.0

# Avvio standalone (senza DB — solo per testare l'immagine)
docker run -d -p 9090:80 --name lamp-test lamp-php:1.0
curl http://localhost:9090/
docker stop lamp-test && docker rm lamp-test
```

---

## ✅ Checklist di completamento

- [ ] Repository GitHub creato con struttura corretta
- [ ] Dev Container configurato (`.devcontainer/devcontainer.json`)
- [ ] File `.env` configurato da `.env.example`
- [ ] Stack LAMP avviato (`docker compose up -d`)
- [ ] Entrambi i container in stato `Up (healthy)` (`docker compose ps`)
- [ ] Dashboard Kanban visibile nel browser su porta 8888
- [ ] API `/api.php?action=ping` restituisce `{"status":"ok"}`
- [ ] Operazioni CRUD testate (aggiungi, modifica, sposta, elimina task)
- [ ] Codespace aperto e stack testato nel browser
- [ ] Commit e push effettuati

---

## 📸 Screenshot da consegnare

1. Repository GitHub — struttura `docker-container/lamp/`
2. Terminale: output di `docker compose ps` (entrambi i container Up)
3. Browser: Kanban Board con i task in tutte e tre le colonne
4. Browser: risposta JSON di `/api.php?action=ping`
5. Browser o terminale: aggiunta di un nuovo task personalizzato

---

## ⚠️ Troubleshooting

### Problema: "Cannot connect to the Docker daemon"

**Causa**: Docker daemon non in esecuzione nel Codespace  
**Soluzione**: Assicurati che `devcontainer.json` includa il feature `docker-in-docker:2` con `"moby": false` (necessario su Debian trixie).

### Problema: `webserver` in stato `Exit` o `Restarting`

```bash
docker compose logs webserver
```

Se l'errore è legato a MariaDB non ancora pronto, attendi ~40 secondi e riprova:
```bash
docker compose up -d
```

### Problema: Task non compaiono nella dashboard

Il DB viene inizializzato al primo accesso a `index.php`.  
Se la connessione fallisce, controlla le variabili in `.env`:

```bash
docker exec lamp_webserver env | grep DB_
```

### Problema: Port forwarding non funziona

```bash
# Tab 'Ports' in VS Code → 'Add Port' → 8888
# Oppure verifica che forwardPorts includa 8888 in devcontainer.json
```

---

## 🎯 Prossimi passi

- Completa **Esercizio B** per creare un API Gateway che orchestra Node.js, Java e PHP!
- Prova ad aggiungere un campo `categoria` ai task e a filtrare per categoria
- Implementa l'autenticazione base (HTTP Basic Auth con Apache)

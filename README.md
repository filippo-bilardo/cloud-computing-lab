# Cloud Computing Lab — Docker Container Playground

Repository per lo studio pratico di **Docker**, **Docker Compose** e applicazioni containerizzate,
progettato per funzionare sia in locale che tramite **GitHub Codespaces** con Dev Container.

---

## Struttura del repository

```
cloud-computing-lab/
├── .devcontainer/
│   └── devcontainer.json          ← Ambiente Codespace (Node 24 + Docker-in-Docker)
├── nodejs-app/                    ← App Node.js standalone (avvio diretto con npm)
│   ├── server.js
│   └── package.json
└── docker-container/              ← Container Docker pronti all'uso
    ├── nodejs/                    ← Node.js + Express (Dockerfile + docker run)
    ├── java-spring/               ← Spring Boot (Dockerfile multi-stage, porta 8080)
    └── lamp/                      ← PHP 8.2 + Apache + MariaDB (docker-compose, porta 8888)
        └── volumes/www/
            ├── index.php          ← Kanban Board (frontend)
            ├── api.php            ← REST API JSON
            └── db.php             ← Connessione PDO + auto-init DB
```

---

## Container disponibili

| Container | Stack | Porta | Avvio |
|-----------|-------|-------|-------|
| `nodejs/` | Node.js 20 + Express | 3000 | `docker run` |
| `java-spring/` | Java 21 + Spring Boot | 8080 | `docker run` |
| `lamp/` | PHP 8.2 + Apache + MariaDB 10.11 | 8888 | `docker compose` |

---

---

# 🔬 Esercizio A: Setup Repository e GitHub Codespace

> Adattamento dell'esercitazione originale
> [ES01-Docker_Nodejs_Java/esercizio_a.md](https://github.com/filippo-bilardo/Cloud-Computing/blob/main/03-Architetture_Cloud_e_Container/ES01-Docker_Nodejs_Java/esercizio_a.md)
> al repository **`cloud-computing-lab`** con container Node.js, Java Spring Boot e LAMP.

## Obiettivo

Avviare container Docker con applicazioni Node.js, Java e PHP/MariaDB all'interno di un
ambiente di sviluppo containerizzato (Dev Container), accessibile tramite GitHub Codespaces.

## Competenze

✅ Clonare e navigare un repository GitHub  
✅ Configurare e usare un Dev Container con Docker-in-Docker  
✅ Avviare container con `docker run` e `docker compose`  
✅ Testare API REST con il browser e `curl`  
✅ Fare commit e push su GitHub  
✅ Aprire il progetto in GitHub Codespaces  

---

## Parte 0: Creare un Dev Container da zero

Prima di fare il fork del repository, capiamo **cos'è un Dev Container e come si costruisce**.

### Cos'è un Dev Container?

Un **Dev Container** è un container Docker usato come ambiente di sviluppo.
Invece di installare Node, Java, Docker ecc. sul tuo PC, VS Code (o Codespaces) avvia
un container con tutto il necessario già dentro, e ti ci connette automaticamente.

```
Il tuo PC / Codespaces
│
└── VS Code
      │
      └── si connette a ──► Container (Debian + Node + Docker)
                                │
                                └── qui esegui tutto il codice
```

La configurazione è in una sola cartella:

```
progetto/
└── .devcontainer/
    └── devcontainer.json   ← tutto qui
```

### Step 0.1: Crea la struttura di base

Partendo da una cartella vuota:

```bash
mkdir il-mio-lab
cd il-mio-lab
git init
mkdir .devcontainer
```

### Step 0.2: Crea `devcontainer.json` — versione minimale

```bash
cat > .devcontainer/devcontainer.json << 'EOF'
{
  "name": "Il mio Lab",
  "image": "mcr.microsoft.com/devcontainers/base:debian"
}
EOF
```

Questo è il minimo indispensabile: un nome e un'immagine base.
Apri la cartella in VS Code → compare il popup **"Reopen in Container"** → click per entrare.

### Step 0.3: Aggiungi le **features** (Node + Docker)

Le *features* sono pacchetti preconfigurati che si installano sull'immagine base.
Non devi scrivere un Dockerfile: bastano poche righe:

```jsonc
{
  "name": "Il mio Lab",
  "image": "mcr.microsoft.com/devcontainers/base:debian",

  "features": {
    // Installa Node.js versione 24
    "ghcr.io/devcontainers/features/node:1": { "version": "24" },

    // Installa Docker-in-Docker (per usare docker dentro il container)
    "ghcr.io/devcontainers/features/docker-in-docker:2": { "moby": false }
  }
}
```

> 💡 `"moby": false` dice di installare il client Docker ufficiale invece di Moby
> (necessario su immagini Debian recenti come `trixie`).

### Step 0.4: Aggiungi `postCreateCommand` e `forwardPorts`

```jsonc
{
  "name": "Il mio Lab",
  "image": "mcr.microsoft.com/devcontainers/base:debian",

  "features": {
    "ghcr.io/devcontainers/features/node:1": { "version": "24" },
    "ghcr.io/devcontainers/features/docker-in-docker:2": { "moby": false }
  },

  // Comando eseguito UNA VOLTA dopo la creazione del container
  "postCreateCommand": "docker --version && node --version",

  // Porte da esporre automaticamente verso il browser
  "forwardPorts": [3000, 8080, 8888]
}
```

| Chiave | Cosa fa |
|--------|---------|
| `image` | Immagine Docker da usare come base dell'ambiente |
| `features` | Pacchetti aggiuntivi da installare (node, docker, git, ecc.) |
| `postCreateCommand` | Script eseguito dopo il build, una volta sola |
| `forwardPorts` | Porte del container esposte come se fossero sul tuo `localhost` |

### Step 0.5: Commit

```bash
git add .devcontainer/devcontainer.json
git commit -m "feat: add devcontainer configuration"
```

Fatto. Quando apri questo repository in Codespaces (o in VS Code con l'estensione
*Dev Containers*), ottieni un ambiente già pronto con Node 24 e Docker disponibili.

---

## Parte 1: Fork e Clone del Repository

### Step 1.1: Fork del repository

1. Apri [github.com/filippo-bilardo/cloud-computing-lab](https://github.com/filippo-bilardo/cloud-computing-lab)
2. Click su **Fork** (in alto a destra)
3. Seleziona il tuo account → **Create fork**

### Step 1.2: Clone in locale (opzionale)

```bash
git clone https://github.com/TUO_USERNAME/cloud-computing-lab.git
cd cloud-computing-lab
```

> ⚠️ Puoi anche lavorare direttamente nel browser usando GitHub Codespaces (→ Parte 5).

---

## Parte 2: Dev Container

### Step 2.1: Struttura del Dev Container

Il file `.devcontainer/devcontainer.json` configura l'ambiente di sviluppo:

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

  "forwardPorts": [3000, 8080, 8888]
}
```

> 📝 La feature `docker-in-docker:2` con `"moby": false` consente di eseguire Docker
> all'interno del Codespace (necessario su Debian trixie, l'immagine base attuale).  
> `forwardPorts` espone automaticamente le porte 3000, 8080 e 8888.

### Step 2.2: Aggiorna `devcontainer.json`

Il file nel repository ha `"forwardPorts": [3000]`. Aggiungilo anche per 8080 e 8888:

```bash
# Modifica .devcontainer/devcontainer.json
# Cambia: "forwardPorts": [3000]
# In:     "forwardPorts": [3000, 8080, 8888]
```

Commit: `"Update devcontainer: add ports 8080 and 8888"`

---

## Parte 3: Container Node.js

### Step 3.1: Esplora il codice

```bash
cat docker-container/nodejs/server.js
cat docker-container/nodejs/Dockerfile
```

Il container espone un'API Express su porta 3000 con endpoint `/` e `/health`.

### Step 3.2: Build dell'immagine

```bash
cd docker-container/nodejs
docker build -t nodejs-api:1.0 .
```

### Step 3.3: Avvio del container

```bash
docker run -d -p 3000:3000 --name nodejs-api nodejs-api:1.0
```

### Step 3.4: Test

```bash
curl http://localhost:3000/
# Output: {"message":"Hello from Node.js!","service":"nodejs-api", ...}

curl http://localhost:3000/health
# Output: {"status":"ok"}
```

VS Code mostra la notifica **"Port 3000 is available"** → click per aprire nel browser.

### Step 3.5: Cleanup

```bash
docker stop nodejs-api && docker rm nodejs-api
```

---

## Parte 4: Container Java Spring Boot

### Step 4.1: Esplora il codice

```bash
cat docker-container/java-spring/Dockerfile
# → Dockerfile multi-stage: Maven build + JRE runtime (immagine finale ~70 MB)
```

Il Dockerfile usa un build a due stadi:
1. **Build stage** (`maven:3.9`) — compila il JAR con `mvn package`
2. **Runtime stage** (`eclipse-temurin:21-jre-alpine`) — esegue solo il JAR

### Step 4.2: Build dell'immagine

```bash
cd docker-container/java-spring
docker build -t spring-dashboard:1.0 .
# Richiede ~2-3 minuti al primo build (scarica dipendenze Maven)
```

### Step 4.3: Avvio del container

```bash
docker run -d -p 8080:8080 --name spring-dashboard spring-dashboard:1.0
```

### Step 4.4: Verifica startup

```bash
# Attendi ~15 secondi, poi:
docker logs spring-dashboard | tail -5
# Cerca: "Started Application in X.XXX seconds"
```

### Step 4.5: Test

```bash
curl http://localhost:8080/
# Output: {"message":"Hello from Java!","service":"spring-dashboard", ...}

curl http://localhost:8080/health
# Output: {"status":"ok"}
```

### Step 4.6: Cleanup

```bash
docker stop spring-dashboard && docker rm spring-dashboard
```

---

## Parte 5: Stack LAMP (PHP + MariaDB)

Lo stack LAMP usa **Docker Compose** per orchestrare due container: webserver PHP e database MariaDB.
L'applicazione è una **Kanban Board** moderna con persistenza su MariaDB.

### Step 5.1: Configurazione

```bash
cd docker-container/lamp
cp .env.example .env
# Modifica .env se vuoi cambiare le credenziali (opzionale)
```

### Step 5.2: Avvio dello stack

```bash
docker compose up -d
```

Docker Compose:
1. Crea la rete `lamp_network`
2. Avvia `lamp_mariadb` (MariaDB 10.11) e attende l'healthcheck
3. Avvia `lamp_webserver` (PHP 8.2 + Apache)
4. Al primo accesso, `db.php` crea il DB `taskmanager`, la tabella e i task iniziali

### Step 5.3: Verifica stato

```bash
docker compose ps
# Entrambi i container devono essere "Up (healthy)"
```

### Step 5.4: Test

```bash
# Health check API
curl http://localhost:8888/api.php?action=ping
# Output: {"status":"ok","db":"taskmanager","php":"8.2.x"}

# Lista task
curl http://localhost:8888/api.php?action=list
```

Apri `http://localhost:8888` nel browser per vedere la **Kanban Board** con i task dell'esercitazione.

### Step 5.5: Operazioni CRUD

Testa la Kanban Board nell'interfaccia grafica:

| Azione | Come farlo |
|--------|-----------|
| Aggiungi task | Click su **＋ Nuovo Task** (in alto a destra) |
| Modifica task | Hover sulla card → click ✏️ |
| Sposta task | Trascina la card in un'altra colonna |
| Elimina task | Hover sulla card → click 🗑️ → conferma |

### Step 5.6: Cleanup

```bash
docker compose down
# I dati rimangono nel volume mysql_data
# Per eliminare anche i dati: docker compose down -v
```

---

## Parte 6: Aprire in GitHub Codespaces

### Step 6.1: Crea il Codespace

1. Vai sul tuo fork GitHub
2. Click su **Code** (verde) → tab **Codespaces**
3. Click **Create codespace on main**
4. Attendi 1–2 minuti → VS Code si apre nel browser

### Step 6.2: Verifica installazioni

```bash
node --version    # v24.x
docker --version  # Docker 27.x (Docker-in-Docker)
```

### Step 6.3: Crea la rete Docker condivisa

```bash
docker network create nginx_proxy_network
# Necessaria per lo stack LAMP
```

### Step 6.4: Testa tutti i container nel Codespace

```bash
# Node.js
cd docker-container/nodejs
docker build -t nodejs-api:1.0 . && docker run -d -p 3000:3000 --name nodejs-api nodejs-api:1.0
curl http://localhost:3000/

# Java Spring
cd ../java-spring
docker build -t spring-dashboard:1.0 . && docker run -d -p 8080:8080 --name spring-dashboard spring-dashboard:1.0
# attendi ~15s
curl http://localhost:8080/

# LAMP
cd ../lamp
cp .env.example .env
docker compose up -d
curl http://localhost:8888/api.php?action=ping
```

VS Code mostra le notifiche per ogni porta aperta → click per aprire nel browser integrato.

---

## Parte 7: App Node.js standalone (senza Docker)

Il repository include anche `nodejs-app/` — una versione dell'app Node.js che gira
direttamente nell'ambiente Codespace, **senza Docker**.

```bash
cd nodejs-app
npm install
npm start
# Output: ✅ Node.js app running on port 3000
```

Utile per confrontare:
- **Con Docker** (`docker-container/nodejs/`): app isolata, riproducibile ovunque
- **Senza Docker** (`nodejs-app/`): avvio diretto, dipende dall'ambiente host

---

## ✅ Verifica completamento

- [ ] Repository forkato (o creato da zero)
- [ ] `devcontainer.json` aggiornato con porte 3000, 8080, 8888
- [ ] Container Node.js avviato e testato (`curl http://localhost:3000/`)
- [ ] Container Java Spring avviato e testato (`curl http://localhost:8080/`)
- [ ] Stack LAMP avviato (`docker compose up -d`) e testato (ping API)
- [ ] Kanban Board aperta nel browser su porta 8888
- [ ] Operazioni CRUD eseguite sulla Kanban Board
- [ ] Codespace aperto e stack testato
- [ ] Commit e push effettuati

---

## 📸 Screenshot da consegnare

1. Terminale: output di `docker ps` con tutti i container in esecuzione
2. Browser: Kanban Board (`http://localhost:8888`) con i task nelle tre colonne
3. Browser o terminale: risposta JSON di `/api.php?action=ping`
4. Browser o terminale: risposta JSON di Node.js API (`http://localhost:3000/`)
5. Browser o terminale: risposta JSON di Spring Boot API (`http://localhost:8080/`)
6. Codespace aperto (screenshot di VS Code nel browser con il terminale visibile)

---

## ⚠️ Troubleshooting

### Problema: "Cannot connect to the Docker daemon"

**Causa**: Docker daemon non ancora pronto nel Codespace (prime fasi di setup).  
**Soluzione**: Attendi 30 secondi e riprova. Verifica che `devcontainer.json` abbia la feature `docker-in-docker:2` con `"moby": false`.

### Problema: `lamp_webserver` in stato `Exit` o `Restarting`

```bash
docker compose logs webserver
```

Se l'errore riguarda MariaDB non ancora pronto, attendi ~40 secondi e:
```bash
docker compose up -d
```

### Problema: Rete `nginx_proxy_network` non trovata

```bash
docker network create nginx_proxy_network
docker compose up -d
```

### Problema: Port forwarding non funziona

1. Tab **Ports** in VS Code → **Add Port** → inserisci `3000`, `8080` o `8888`
2. Click sull'icona 🌐 per aprire nel browser
3. Verifica che `forwardPorts` in `devcontainer.json` includa le porte necessarie

### Problema: Spring Boot impiega troppo a partire

Il primo `docker build` di Java scarica ~200 MB di dipendenze Maven.  
Le build successive usano la cache Docker e impiegano < 30 secondi.

```bash
docker logs spring-dashboard -f
# Attendi la riga: "Started Application in X.XXX seconds"
```

---

## 🎯 Prossimi passi

- Completa **Esercizio B** per creare un API Gateway che orchestra Node.js, Java e PHP!
- Modifica `docker-container/nodejs/server.js` e fai rebuild per vedere le variazioni
- Aggiungi un endpoint `/info` alla Spring Boot app che restituisce la versione Java
- Aggiungi un campo `categoria` alla Kanban Board e filtra per categoria

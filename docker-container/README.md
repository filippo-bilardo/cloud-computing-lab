# Docker Container Examples – Cloud Computing Lab

Raccolta di esempi pratici per imparare a creare, avviare e gestire container Docker. Ogni sottocartella contiene un'applicazione di esempio pronta per essere containerizzata.

---

## Struttura del progetto

```
docker-container/
├── nodejs/        # Dashboard Node.js con statistiche del container (questo esempio)
├── java-spring/   # Applicazione Java Spring Boot
├── lamp/          # Stack LAMP (Linux, Apache, MySQL, PHP)
└── owncloud/      # Istanza ownCloud
```

---

## Concetti fondamentali di Docker

### Cos'è un container?
Un **container** è un'unità software leggera e isolata che racchiude un'applicazione insieme a tutte le sue dipendenze (librerie, runtime, file di configurazione). A differenza di una macchina virtuale, un container condivide il kernel del sistema operativo host, risultando molto più leggero e veloce da avviare.

```
┌─────────────────────────────────────┐
│           Applicazione              │
│         Dipendenze / Librerie       │
│            Runtime (Node, JVM…)     │
├─────────────────────────────────────┤
│          Container Engine (Docker)  │
├─────────────────────────────────────┤
│         Sistema Operativo Host      │
│              Hardware               │
└─────────────────────────────────────┘
```

### Dockerfile
Il **Dockerfile** è un file di testo con le istruzioni per costruire un'**immagine** Docker. Le istruzioni più comuni sono:

| Istruzione | Significato |
|------------|-------------|
| `FROM`     | Immagine base di partenza |
| `WORKDIR`  | Directory di lavoro all'interno del container |
| `COPY`     | Copia file dall'host nel container |
| `RUN`      | Esegue un comando durante la build |
| `EXPOSE`   | Dichiara la porta su cui l'app è in ascolto |
| `CMD`      | Comando eseguito all'avvio del container |

### Immagine vs Container
- **Immagine**: snapshot immutabile (come un template o una classe)
- **Container**: istanza in esecuzione di un'immagine (come un oggetto istanziato)

---

## Esempio: Node.js Container Dashboard

L'applicazione nella cartella `nodejs/` è una **dashboard web** che mostra in tempo reale le statistiche del container in esecuzione: CPU, memoria, rete, processi e informazioni sull'ambiente.

### Dockerfile spiegato

```dockerfile
FROM node:20-alpine        # Usa Node.js 20 su Alpine Linux (immagine minimale ~50MB)
WORKDIR /app               # Imposta /app come directory di lavoro
COPY package*.json ./      # Copia i file di dipendenze (ottimizza la cache di Docker)
RUN npm install            # Installa le dipendenze Node.js
COPY . .                   # Copia il resto del codice sorgente
EXPOSE 3000                # Documenta che l'app usa la porta 3000
CMD ["npm", "start"]       # Comando di avvio del container
```

> **Perché copiare `package*.json` prima del resto del codice?**  
> Docker costruisce l'immagine a strati (*layers*). Copiando prima solo i file delle dipendenze, se il codice cambia ma le dipendenze no, Docker riusa il layer già costruito con `npm install`, velocizzando notevolmente la build.

### Endpoint API

| Endpoint       | Descrizione |
|----------------|-------------|
| `GET /`        | Dashboard HTML con grafici real-time |
| `GET /api/info`    | Informazioni statiche su container, OS, CPU, memoria |
| `GET /api/metrics` | Metriche real-time: carico CPU, RAM, rete, disco |

---

## Guida pratica: Build, Run, Manage

### 1. Costruire l'immagine

```bash
cd docker-container/nodejs

# Build dell'immagine con tag
docker build -t container-dashboard:1.0 .

# Verifica che l'immagine sia stata creata
docker images
```

### 2. Avviare il container

```bash
# Avvio interattivo (il log viene stampato nel terminale, Ctrl+C per fermare)
docker run -p 3000:3000 container-dashboard:1.0

# Avvio in background (detached mode)
docker run -d -p 3000:3000 --name my-dashboard container-dashboard:1.0

# Con variabile d'ambiente personalizzata
docker run -d -p 8080:3000 --name my-dashboard \
  -e NODE_ENV=production \
  container-dashboard:1.0
```

Apri il browser su **http://localhost:3000** per vedere la dashboard.

### 3. Gestire il container

```bash
# Elenco dei container in esecuzione
docker ps

# Elenco di tutti i container (inclusi quelli fermi)
docker ps -a

# Fermare il container
docker stop my-dashboard

# Riavviare il container
docker restart my-dashboard

# Rimuovere il container (deve essere fermo)
docker rm my-dashboard

# Fermare e rimuovere in un solo comando
docker rm -f my-dashboard
```

### 4. Ispezionare il container

```bash
# Log dell'applicazione
docker logs my-dashboard

# Log in tempo reale (segue l'output)
docker logs -f my-dashboard

# Statistiche di CPU e memoria in tempo reale
docker stats my-dashboard

# Aprire una shell bash all'interno del container
docker exec -it my-dashboard sh

# Ispezionare la configurazione completa del container
docker inspect my-dashboard
```

### 5. Pulizia delle risorse

```bash
# Rimuovere l'immagine
docker rmi container-dashboard:1.0

# Rimuovere tutti i container fermi, immagini non usate, volumi orfani
docker system prune -a
```

---

## Ciclo di vita di un container

```
  docker build
       │
       ▼
  [IMMAGINE] ──── docker run ────► [RUNNING]
                                      │    ▲
                              docker stop  docker start
                                      │    │
                                      ▼    │
                                   [STOPPED]
                                      │
                                 docker rm
                                      │
                                      ▼
                                  [RIMOSSO]
```

---

## Troubleshooting

| Problema | Causa probabile | Soluzione |
|----------|-----------------|-----------|
| `port is already allocated` | La porta 3000 è già usata | Usa `-p 3001:3000` per mappare su un'altra porta host |
| `Cannot find module` | `npm install` non eseguito | Ricostruire l'immagine con `docker build` |
| Container si ferma subito | Errore nell'app | Controllare i log con `docker logs <nome>` |
| Build lenta | Layer cache invalidata | Verificare l'ordine delle istruzioni nel Dockerfile |

---

## Riferimenti

- [Documentazione ufficiale Docker](https://docs.docker.com/)
- [Docker Hub – node](https://hub.docker.com/_/node)
- [Best practices per Dockerfile](https://docs.docker.com/develop/develop-images/dockerfile_best-practices/)

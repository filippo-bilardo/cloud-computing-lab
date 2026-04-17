# Container Node.js – Dashboard di monitoraggio

Applicazione web **Node.js + Express** che mostra in tempo reale le statistiche del container in esecuzione: carico CPU, memoria, rete, processi e informazioni sull'ambiente Docker.

---

## Struttura del progetto

```
nodejs/
├── Dockerfile          # Istruzioni per costruire l'immagine Docker
├── package.json        # Dipendenze Node.js (express, systeminformation)
├── server.js           # Server Express con API REST per le statistiche
├── public/
│   └── index.html      # Dashboard HTML con grafici Chart.js
├── .gitignore
└── README.md
```

---

## Come funziona

Il server `server.js` espone due endpoint REST che la dashboard interroga periodicamente:

| Endpoint | Aggiornamento | Descrizione |
|---|---|---|
| `GET /api/info` | Una volta all'avvio | Info statiche: hostname, OS, CPU, Node.js version, variabili d'ambiente |
| `GET /api/metrics` | Ogni 2 secondi | Metriche live: % CPU, RAM usata, traffico di rete, I/O disco, processi |

La dashboard HTML (servita come file statico) usa **Chart.js** per disegnare grafici a linee temporali e indicatori circolari aggiornati in tempo reale tramite `setInterval`.

### Librerie utilizzate

| Libreria | Ruolo |
|---|---|
| [`express`](https://expressjs.com/) | Web server HTTP, routing, serve dei file statici |
| [`systeminformation`](https://systeminformation.io/) | Lettura delle metriche di sistema (CPU, RAM, rete, disco) |
| [`os`](https://nodejs.org/api/os.html) | Modulo Node.js built-in per hostname e info piattaforma |

---

## Il Dockerfile spiegato

```dockerfile
FROM node:20-alpine
```
**Immagine base**: Node.js 20 su Alpine Linux. Alpine è una distribuzione minimale (~5 MB) pensata per i container: occupa meno spazio e ha una superficie di attacco ridotta.

```dockerfile
WORKDIR /app
```
**Directory di lavoro**: tutti i comandi successivi vengono eseguiti in `/app`. Se la cartella non esiste, viene creata automaticamente.

```dockerfile
COPY package*.json ./
RUN npm install
```
**Ottimizzazione della cache**: Docker costruisce l'immagine a *layer*. Copiando prima solo il manifesto delle dipendenze, il layer con `npm install` viene rieseguito solo se `package.json` cambia. Se si modificasse solo il codice sorgente, questo layer verrebbe riusato dalla cache, velocizzando la build.

```dockerfile
COPY . .
```
Copia il resto dei sorgenti (inclusa la cartella `public/`) nel container.

```dockerfile
EXPOSE 3000
```
**Documentazione della porta**: indica che l'applicazione ascolta sulla porta 3000. Non apre automaticamente la porta sull'host — questo avviene con `-p` al momento del `docker run`.

```dockerfile
CMD ["npm", "start"]
```
**Comando di default**: eseguito quando il container viene avviato. Utilizza la forma *exec* (array JSON) che lancia direttamente il processo senza una shell intermedia, garantendo che i segnali di arresto (SIGTERM) vengano ricevuti correttamente dall'applicazione.

---

## Guida pratica

### 1. Build dell'immagine

```bash
# Posizionarsi nella cartella del progetto
cd docker-container/nodejs

# Costruire l'immagine con un tag (nome:versione)
docker build -t container-dashboard:1.0 .

# Verificare che l'immagine sia presente
docker images | grep container-dashboard
```

L'opzione `-t` assegna un **tag** all'immagine nel formato `nome:versione`. Il `.` finale indica che il contesto di build è la directory corrente.

### 2. Avviare il container

```bash
# Avvio in foreground (log visibili, Ctrl+C per fermare)
docker run -p 3000:3000 container-dashboard:1.0

# Avvio in background (detached) con nome personalizzato
docker run -d -p 3000:3000 --name dashboard container-dashboard:1.0

# Con porta host diversa (es. 8080 → 3000 interno)
docker run -d -p 8080:3000 --name dashboard container-dashboard:1.0

# Con variabili d'ambiente
docker run -d -p 3000:3000 --name dashboard \
  -e NODE_ENV=production \
  container-dashboard:1.0
```

Aprire il browser su **http://localhost:3000**.

> **Nota sulla mappatura delle porte** (`-p HOST:CONTAINER`):  
> `-p 3000:3000` significa "la porta 3000 dell'host viene inoltrata alla porta 3000 del container". Il valore a sinistra è sempre la porta dell'host.

### 3. Gestire il container

```bash
# Elenco container in esecuzione
docker ps

# Elenco di tutti i container (inclusi quelli fermi)
docker ps -a

# Fermare il container (invia SIGTERM, poi SIGKILL dopo 10s)
docker stop dashboard

# Riavviare
docker restart dashboard

# Rimuovere un container fermo
docker rm dashboard

# Forza stop + rimozione in un comando
docker rm -f dashboard
```

### 4. Ispezionare il container

```bash
# Visualizzare i log dell'applicazione
docker logs dashboard

# Seguire i log in tempo reale
docker logs -f dashboard

# Statistiche live di CPU, RAM, rete (simile a top)
docker stats dashboard

# Aprire una shell all'interno del container
docker exec -it dashboard sh

# Ispezionare la configurazione completa (JSON)
docker inspect dashboard

# Vedere le variabili d'ambiente impostate nel container
docker exec dashboard env
```

### 5. Pulizia

```bash
# Rimuovere l'immagine (solo se non ci sono container che la usano)
docker rmi container-dashboard:1.0

# Rimuovere tutto ciò che non è in uso (container fermi, immagini, reti)
docker system prune

# Rimuovere anche le immagini non taggate
docker system prune -a
```

---

## Ciclo di vita di un container

```
  docker build
       │
       ▼
  [IMMAGINE]
       │
  docker run ──────────────────────► [RUNNING]
                                         │      ▲
                                   docker stop  docker start
                                         │      │
                                         ▼      │
                                      [STOPPED]
                                         │
                                    docker rm
                                         │
                                         ▼
                                     [RIMOSSO]
```

---

## Variabili d'ambiente supportate

| Variabile | Default | Descrizione |
|---|---|---|
| `PORT` | `3000` | Porta su cui il server è in ascolto |
| `NODE_ENV` | `development` | Ambiente di esecuzione |

---

## Troubleshooting

| Problema | Causa probabile | Soluzione |
|---|---|---|
| `port is already allocated` | Porta 3000 già occupata sull'host | Usa `-p 3001:3000` |
| Container si ferma subito | Errore nell'app | Controlla `docker logs dashboard` |
| `Cannot find module` | `npm install` non eseguito | Ricostruisci con `docker build` |
| Build lenta | Cache invalidata per ordine sbagliato nel Dockerfile | Mantieni `COPY package*.json` prima di `COPY . .` |
| Metriche di rete a 0 | Permessi limitati in alcuni ambienti | Normale in alcuni container engine; le altre metriche funzionano |

---

## Riferimenti

- [Documentazione Docker](https://docs.docker.com/)
- [Best practices Dockerfile](https://docs.docker.com/develop/develop-images/dockerfile_best-practices/)
- [Node.js Docker best practices](https://github.com/nodejs/docker-node/blob/main/docs/BestPractices.md)
- [systeminformation](https://systeminformation.io/)
- [Chart.js](https://www.chartjs.org/)

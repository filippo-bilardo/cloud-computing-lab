# Container Java Spring Boot – Dashboard

Dashboard web a tema **terminale verde** che mostra in tempo reale le metriche JVM e di sistema di un container Spring Boot.

---

## Struttura del progetto

```
java-spring/
├── Dockerfile                          # Multi-stage build: Maven → JRE Alpine
├── pom.xml                             # Dipendenze Maven (Spring Boot 3.2, OSHI)
└── src/
    └── main/
        ├── java/com/lab/dashboard/
        │   ├── DashboardApplication.java        # Entry point Spring Boot
        │   └── controller/
        │       └── ApiController.java           # Endpoint REST /api/info e /api/metrics
        └── resources/
            ├── application.properties           # Porta 8080, Actuator, log
            └── static/
                └── index.html                   # Frontend dashboard (ApexCharts)
```

---

## Il Dockerfile – Multi-Stage Build

```dockerfile
# ── Stage 1: BUILD ──────────────────────────────────────────────
# Usa Maven + JDK 21 per compilare il progetto e produrre il JAR
FROM maven:3.9-eclipse-temurin-21 AS builder
WORKDIR /app
COPY pom.xml .
RUN mvn dependency:go-offline     # scarica le dipendenze una volta sola (sfrutta la cache)
COPY src ./src
RUN mvn package -DskipTests       # compila e pacchettizza → target/dashboard-0.0.1-SNAPSHOT.jar

# ── Stage 2: RUNTIME ────────────────────────────────────────────
# Solo JRE Alpine: immagine finale ~180 MB invece di ~600 MB con JDK+Maven
FROM eclipse-temurin:21-jre-alpine
WORKDIR /app
COPY --from=builder /app/target/*.jar app.jar
EXPOSE 8080
CMD ["java", "-jar", "app.jar"]
```

**Vantaggi del multi-stage build:**

| Aspetto | Senza multi-stage | Con multi-stage |
|---------|-------------------|-----------------|
| Dimensione immagine finale | ~600 MB (JDK + Maven) | ~180 MB (solo JRE) |
| Codice sorgente nell'immagine | Sì | No |
| Superficie d'attacco | Alta | Ridotta |
| Cache layer Maven | No | Sì (layer `dependency:go-offline`) |

---

## Dipendenze principali (`pom.xml`)

| Dipendenza | Versione | Scopo |
|------------|----------|-------|
| `spring-boot-starter-web` | 3.2.5 | Server HTTP embedded (Tomcat), REST API |
| `spring-boot-starter-actuator` | 3.2.5 | Endpoint `/actuator/health` e `/actuator/metrics` |
| `oshi-core` | 6.6.1 | Metriche hardware/OS: CPU, RAM, rete, disco, processi |

---

## API REST

| Endpoint | Metodo | Frequenza | Descrizione |
|----------|--------|-----------|-------------|
| `/` | GET | una volta | Dashboard HTML (servita da `static/`) |
| `/api/info` | GET | una volta | Hostname, versione Java/JVM, CPU, OS, GC collectors, heap max |
| `/api/metrics` | GET | ogni 3 s | CPU load, avg 1/5/15m, JVM heap/non-heap %, RAM, thread, GC, rete, disco |
| `/actuator/health` | GET | — | Health check Spring Actuator |

### Esempio risposta `/api/metrics` (estratto)

```json
{
  "timestamp": 1713362400000,
  "uptime": 42.5,
  "cpu": { "load": 12.34, "loadAvg1": 0.45, "loadAvg5": 0.38, "loadAvg15": 0.31 },
  "jvmMemory": { "heapUsed": 52428800, "heapMax": 268435456, "heapPercent": 19.53, "nonHeapUsed": 78643200 },
  "threads": { "count": 21, "daemon": 15, "peak": 24, "totalStarted": 30 },
  "gc": { "collections": 4, "timeMs": 62 },
  "memory": { "total": 8589934592, "used": 3221225472, "usedPercent": 37.5 },
  "network": { "name": "eth0", "rxBytes": 1048576, "txBytes": 524288 },
  "disk": { "name": "/dev/sda", "readBytes": 2097152, "writeBytes": 1048576 }
}
```

---

## Avvio rapido

### 1. Costruire l'immagine

```bash
cd docker-container/java-spring

# Build (prima esecuzione: ~3-5 min per scaricare le dipendenze Maven)
docker build -t spring-dashboard:1.0 .

# Verifica immagine creata (~180 MB)
docker images spring-dashboard
```

### 2. Avviare il container

```bash
# Avvio in foreground (log visibili nel terminale, Ctrl+C per fermare)
docker run -p 8080:8080 spring-dashboard:1.0

# Avvio in background (detached)
docker run -d -p 8080:8080 --name spring-dashboard spring-dashboard:1.0

# Con variabili d'ambiente personalizzate
docker run -d -p 8080:8080 --name spring-dashboard \
  -e SPRING_PROFILES_ACTIVE=production \
  -e SERVER_PORT=8080 \
  spring-dashboard:1.0
```

Apri il browser su **http://localhost:8080** per vedere la dashboard.

### 3. Gestire il container

```bash
# Elenco container in esecuzione
docker ps

# Elenco tutti i container (inclusi fermi)
docker ps -a

# Fermare
docker stop spring-dashboard

# Riavviare
docker restart spring-dashboard

# Rimuovere (deve essere fermo)
docker rm spring-dashboard

# Fermare e rimuovere in un solo comando
docker rm -f spring-dashboard
```

### 4. Ispezionare logs e stato

```bash
# Log dell'applicazione (mostra l'avvio di Spring Boot)
docker logs spring-dashboard

# Log in tempo reale
docker logs -f spring-dashboard

# Ultime 50 righe di log
docker logs --tail 50 spring-dashboard

# Statistiche CPU/RAM in tempo reale
docker stats spring-dashboard

# Aprire una shell nel container
docker exec -it spring-dashboard sh

# Ispezionare la configurazione completa
docker inspect spring-dashboard
```

### 5. Verificare che l'applicazione funzioni

```bash
# Health check tramite Spring Actuator
curl http://localhost:8080/actuator/health

# Risposta attesa:
# {"status":"UP","components":{"diskSpace":{"status":"UP"},"ping":{"status":"UP"}}}

# Info statiche
curl http://localhost:8080/api/info | python3 -m json.tool

# Metriche live
curl http://localhost:8080/api/metrics | python3 -m json.tool
```

### 6. Pulizia delle risorse

```bash
# Rimuovere l'immagine
docker rmi spring-dashboard:1.0

# Rimuovere tutti i container fermi + immagini non usate
docker system prune -a
```

---

## Ciclo di vita del container

```
  docker build
       │
       ▼
  [IMMAGINE] ──── docker run ────► [RUNNING] ◄─── docker restart
                                      │    ▲               │
                              docker stop  docker start    │
                                      │    │               │
                                      ▼    │               │
                                   [STOPPED] ──────────────┘
                                      │
                                 docker rm
                                      │
                                      ▼
                                  [RIMOSSO]
```

---

## Differenze rispetto alla dashboard Node.js

| | Node.js (`nodejs/`) | Spring Boot (`java-spring/`) |
|---|---|---|
| **Porta** | 3000 | 8080 |
| **Tema grafico** | Dark blue/purple | Terminal verde (stile hacker) |
| **Libreria grafici** | Chart.js 4 | ApexCharts 3 |
| **Metriche JVM** | No | Heap %, GC, Thread peak/daemon |
| **Health endpoint** | No | `/actuator/health` |
| **Dimensione immagine** | ~170 MB (Alpine) | ~180 MB (JRE Alpine) |
| **Aggiornamento metriche** | ogni 2 s | ogni 3 s |

---

## Troubleshooting

| Problema | Causa probabile | Soluzione |
|----------|-----------------|-----------|
| Build lenta alla prima esecuzione | Maven scarica le dipendenze | Normale, usa la cache Docker ai run successivi |
| `port is already allocated` | La porta 8080 è occupata | Usa `-p 9090:8080` per mappare su altra porta host |
| Container si ferma subito | Errore nella JVM | `docker logs spring-dashboard` per vedere lo stack trace |
| `OutOfMemoryError` | Heap JVM troppo piccolo | Aggiungi `-e JAVA_OPTS="-Xmx512m"` al `docker run` |
| Dashboard non si aggiorna | Perdita connessione HTTP | Il punto verde in header diventa rosso; ricaricare la pagina |

---

## Riferimenti

- [Spring Boot](https://spring.io/projects/spring-boot)
- [OSHI – OS & Hardware Info for Java](https://github.com/oshi/oshi)
- [eclipse-temurin Docker Hub](https://hub.docker.com/_/eclipse-temurin)
- [Spring Boot Actuator](https://docs.spring.io/spring-boot/docs/current/reference/html/actuator.html)
- [ApexCharts](https://apexcharts.com/)
# Cloud Computing Lab guidato — Container nodejs con GitHub Codespaces

Repository per lo studio pratico di **Docker**, **Docker Compose** e applicazioni containerizzate.
In questo laboratorio vedremo come creare un container Docker per una semplice app Node.js, come avviarlo, testarlo e gestirne il ciclo di vita. Useremo GitHub Codespaces per lavorare in un ambiente di sviluppo già configurato con Docker.

## Obiettivo

Capire cos'è un **Dev Container**, come si configura da zero e come si usa in GitHub Codespaces.

---

## Competenze

✅ Creare un repository Git e configurare un Dev Container  
✅ Usare le *features* di Dev Container (Node, Docker-in-Docker)  
✅ Aprire il progetto in VS Code con "Reopen in Container"  
✅ Fare commit e push su GitHub  

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
```
---

## Parte 1: Creare un Dev Container da zero

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

### Step 1.1: Crea Repository su GitHub

1. Vai su [github.com](https://github.com) e accedi
2. Click su **New repository** (pulsante verde)
3. Compila i campi:
   - **Repository name:** `cloud-computing-lab`
   - **Description:** `Node.js Docker lab`
   - **Public** ✅
   - **Initialize with README** ✅
4. Click **Create repository**

### Step 1.2: Crea la struttura di base

Partendo da una cartella vuota:

```bash
mkdir il-mio-lab
cd il-mio-lab
git init
mkdir .devcontainer
```

### Step 1.3: Crea `devcontainer.json` — versione minimale

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

### Step 1.4: Aggiungi le **features** (Node + Docker)

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

### Step 1.5: Aggiungi `postCreateCommand` e `forwardPorts`

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

### Step 1.6: Commit

```bash
git add .devcontainer/devcontainer.json
git commit -m "feat: add devcontainer configuration"
```

Fatto. Quando apri questo repository in Codespaces (o in VS Code con l'estensione
*Dev Containers*), ottieni un ambiente già pronto con Node 24 e Docker disponibili.

### Step 1.7: Crea il Codespace

Ora vai su GitHub, apri il tuo repository e premi il pulsante **Code** (verde) →
tab **Codespaces** → **Create codespace on main**.

Attendi qualche minuto mentre GitHub costruisce il container. Al termine si aprirà
VS Code nel browser con l'ambiente già configurato.

**Complimenti! Hai completato la Parte 1 dell'esercitazione.**
Hai ora un Codespace funzionante con Node.js disponibile e pronto all'uso.

### ❓ Domande di Riflessione 1 — Dev Container

Prima di proseguire, rispondi nel tuo documento di consegna:

**R1.1** Qual è la differenza principale tra sviluppare *direttamente sul proprio PC* e
sviluppare dentro un **Dev Container**? Elenca almeno 3 vantaggi del Dev Container per
un team di sviluppo.

**R1.2** Nel `devcontainer.json` usi `"image": "mcr.microsoft.com/devcontainers/base:debian"`.
Cosa cambierebbe usando `"image": "node:24"` direttamente senza la *feature* Node?
Quale approccio è più flessibile e perché?

**R1.3** La *feature* `docker-in-docker` installa Docker *dentro* un container Docker già
esistente (il Dev Container). Cos'è Docker-in-Docker? Perché è necessario in questo lab?
Quali rischi comporta in ambienti di produzione?

**R1.4** Il `postCreateCommand` viene eseguito una sola volta subito dopo la creazione del
container. Se lo cambiassi in `npm install`, quando verrebbe eseguito? Prova a pensare a uno
scenario in cui questo comando è fondamentale per far funzionare il progetto senza interventi manuali.

**R1.5** Guarda il diagramma testuale nella sezione "Cos'è un Dev Container?". Descrivi
con parole tue cosa succede al codice che scrivi nel container se cancelli il Codespace
senza aver fatto `git push`. Come puoi evitare di perdere lavoro?

---

## Parte 2: Utilizzare un Dev Container esistente (esercitaione alternativa alla Parte 1)

> **Nota:** Non è necessario essere il proprietario di un repository per avviare un Codespace.
> GitHub permette di creare un Codespace da **qualsiasi repository pubblico**, anche se appartiene
> ad un altro utente. Il tuo Codespace è un ambiente personale e isolato: le modifiche che fai
> non influenzano il repository originale.
>
> Il vantaggio di lavorare su un **proprio repository** (o su un fork) è che puoi fare il
> **push delle modifiche** direttamente dal Codespace al repository remoto, salvando così il
> tuo lavoro in modo permanente. Su un repository altrui, invece, non hai i permessi di scrittura
> e le modifiche restano solo nel Codespace temporaneo.
>
> Per questo motivo, se vuoi conservare il tuo lavoro, è consigliabile fare un **fork** del
> repository prima di aprire il Codespace.

Apri il file `.devcontainer/devcontainer.json` del repository `cloud-computing-lab` e confrontalo con quello creato da zero:

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

> 📝 La sezione `customizations.vscode.extensions` installa automaticamente le estensioni
> VS Code nel container. `forwardPorts` espone le porte 3000, 8080 e 8888.

### Step 2.1: Apri il Codespace

1. Vai su [github.com/filippo-bilardo/cloud-computing-lab](https://github.com/filippo-bilardo/cloud-computing-lab)
2. Click su **Code** (verde) → tab **Codespaces** → **Create codespace on main**
3. Attendi 1–2 minuti → VS Code si apre nel browser

### Step 2.2: Verifica l'ambiente

```bash
node --version    # v24.x
docker --version  # Docker 27.x
```

**Complimenti! Hai completato la Parte 2 dell'esercitazione.**
Anche in questo caso hai a disposizione un Codespace funzionante con Node.js pronto all'uso,
questa volta utilizzando un Dev Container già configurato da un repository esistente.

### ❓ Domande di Riflessione 2 — Dev Container esistente e Codespaces

**R2.1** Confronta i due `devcontainer.json` (quello creato da zero nella Parte 1 e quello
del repository `cloud-computing-lab`). Elenca le differenze. Cosa aggiunge la sezione
`customizations.vscode.extensions`? Perché installare estensioni VS Code nel container
è utile per un team?

**R2.2** GitHub permette di aprire un Codespace su *qualsiasi* repository pubblico.
Quali implicazioni di sicurezza ha questo per il proprietario del repository?
Cosa non puoi fare se apri il Codespace su un repository altrui (senza fork)?

**R2.3** `"forwardPorts": [3000, 8080, 8888]` espone automaticamente queste porte dal
container verso il browser. Cosa succederebbe se non configurassi questa lista e avviassi
un server sulla porta 3000? Ci sarebbe un modo alternativo per raggiungerlo?

**R2.4** Perché è consigliabile fare un **fork** del repository prima di aprire il
Codespace, invece di aprirlo direttamente? Descrivi lo scenario in cui il fork è necessario
per conservare il lavoro svolto.

---

## Parte 3: Creare e avviare la `nodejs-app`

Una volta aperto il Codespace (o il Dev Container), crea una semplice app Node.js
che gira **direttamente nell'ambiente**.

### Step 3.1: Crea la cartella e inizializza il progetto

```bash
mkdir nodejs-app
cd nodejs-app
npm init -y
```

`npm init -y` genera un `package.json` con i valori predefiniti.

### Step 3.2: Installa Express

```bash
npm install express
```

Questo aggiunge Express come dipendenza in `package.json` e crea la cartella `node_modules/`.

### Step 3.3: Crea `server.js`

```bash
cat > server.js << 'EOF'
const express = require('express');
const app = express();

app.get('/', (req, res) => {
  res.json({
    message: 'Hello from Node.js!',
    service: 'nodejs-api'
  });
});

app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`✅ Node.js app running on port ${PORT}`);
});
EOF
```
personalizza il messaggio JSON inserendo il tuo nome.

### Step 3.4: Aggiungi lo script `start` a `package.json`

Apri `package.json` e assicurati che la sezione `scripts` contenga:

```json
"scripts": {
  "start": "node server.js"
}
```

### Step 3.5: Avvia l'app

```bash
npm start
# Output: ✅ Node.js app running on port 3000
```

### Step 3.6: Testa l'app

Apri un **secondo terminale** (senza fermare il server) e lancia:

```bash
curl http://localhost:3000/
# Output: {"message":"Hello from Node.js!","service":"nodejs-api"}

curl http://localhost:3000/health
# Output: {"status":"ok"}
```

In Codespaces appare la notifica **"Port 3000 is available"** → click per aprire nel browser.

### Step 3.7: Aggiungi `.gitignore`

```bash
echo "node_modules/" > .gitignore
```

Esclude la cartella `node_modules/` dal repository (le dipendenze si riscaricanno con `npm install`).

### Step 3.8: Commit

```bash
cd ..   # torna alla root del progetto
git add nodejs-app/
git commit -m "feat: add nodejs-app standalone"
```

> 💡 **Differenza rispetto al container Docker**: questa app usa Node.js installato
> nel Dev Container tramite la *feature*. Nell'[Esercizio B](esercizio_b.md)
> la stessa app girerà dentro un container Docker isolato.

### ❓ Domande di Riflessione 3 — App Node.js e architettura

**R3.1** L'app espone due endpoint: `/` e `/health`. Perché è buona pratica avere un
endpoint dedicato al controllo di salute del servizio? Come viene usato in ambienti
Docker/Kubernetes per decidere se un container è "pronto" a ricevere traffico?

**R3.2** Il file `node_modules/` è escluso dal `.gitignore`. Perché non si include nel
repository? Come si ricreano le dipendenze su un altro sistema dopo aver clonato il progetto?
Cosa succederebbe se *non* aggiungessi `node_modules/` al `.gitignore`?

**R3.3** Il server usa `process.env.PORT || 3000`. Spiega cosa significa questo costrutto.
Perché è preferibile configurare la porta tramite variabile d'ambiente invece di un valore
fisso nel codice? Fai un esempio pratico in cui la porta fissa causerebbe problemi.

**R3.4** Questa app Node.js gira **direttamente nel Dev Container**. Nell'esercizio B la
stessa app girerà dentro un **container Docker separato**, avviato dal Dev Container.
Disegna uno schema che mostra la differenza architetturale tra i due approcci. Quale è
più isolato? Quale è più semplice da sviluppare?

**R3.5** `npm init -y` genera un `package.json` con valori predefiniti. Apri il file
`package.json` generato e spiega cosa indicano i campi `"name"`, `"version"`,
`"main"` e `"scripts"`. A cosa servono le sezioni `"dependencies"` e `"devDependencies"`?

---

## ✅ Verifica completamento Esercizio A

**Parte 1 — Creare un Dev Container da zero**
- [ ] Cartella `.devcontainer/` creata con `devcontainer.json` minimale
- [ ] Features Node e Docker-in-Docker aggiunte
- [ ] `postCreateCommand` e `forwardPorts` configurati
- [ ] Commit effettuato con messaggio descrittivo
- [ ] Codespace aperto e `node --version` / `docker --version` verificati
- [ ] Risposte a **R1.1 – R1.5** nel documento di consegna

**Parte 2 — Utilizzare un Dev Container esistente**
- [ ] File `.devcontainer/devcontainer.json` del repository `cloud-computing-lab` aperto e analizzato
- [ ] Codespace avviato dal repository esistente
- [ ] `node --version` e `docker --version` verificati nel Codespace
- [ ] Risposte a **R2.1 – R2.4** nel documento di consegna

**Parte 3 — Creare e avviare la `nodejs-app`**
- [ ] Cartella `nodejs-app/` creata e progetto inizializzato con `npm init`
- [ ] File `server.js` creato con server HTTP
- [ ] Script `start` aggiunto in `package.json`
- [ ] App avviata con `npm start` e risposta verificata con `curl http://localhost:3000/`
- [ ] Risposte a **R3.1 – R3.5** nel documento di consegna

---

## 📸 Screenshot da consegnare (Esercizio A)

1. File `devcontainer.json` completo (Step 1.4)
2. Terminale Codespace: output di `node --version` e `docker --version`
3. Applicazione Node.js in esecuzione (output di `npm start`)
4. Risposta JSON di `curl http://localhost:3000/`

---

## 📋 Modalità di Consegna

Crea un documento (Google Doc o PDF) con:

1. **Copertina**: Nome, Cognome, Classe, Data, Titolo ("Esercizio A — Dev Container e Node.js")
2. **Indice** numerato con le sezioni dell'esercitazione
3. **Screenshot** per ogni punto indicato sopra (minimo 4)
4. **Risposte alle domande di riflessione**: tutte le serie R1, R2, R3 (14 domande totali)
5. **Comandi eseguiti**: copia-incolla dei comandi con il relativo output
6. **Conclusioni personali**: riflessione finale (minimo 100 parole)

### Criteri di Valutazione

| Criterio | Peso | Descrizione |
|----------|------|-------------|
| Completezza | 30% | Tutti gli screenshot e le risposte presenti |
| Correttezza tecnica | 25% | Comandi corretti, risposte accurate |
| Comprensione concetti | 25% | Dimostra di aver capito il "perché" |
| Riflessione critica | 20% | Confronti, scenari, ragionamento personale |

---

## 🎯 Prossimi passi

- Completa **[Esercizio B](esercizio_b.md)** — Fork del repository e gestione container Docker (Node.js, Java Spring Boot, LAMP)

- [https://www.cloud.it/container/#soluzioni](https://www.cloud.it/container/#soluzioni) — Approfondimenti su container Docker, Kubernetes e soluzioni cloud native
<?php
declare(strict_types=1);

/**
 * Restituisce una connessione PDO al database taskmanager.
 * Al primo utilizzo crea automaticamente il database, la tabella
 * e inserisce i task iniziali dell'esercitazione.
 */
function getDB(): PDO
{
    $host = getenv('DB_HOST')     ?: 'mariadb';
    $user = getenv('DB_USERNAME') ?: 'lamp';
    $pass = getenv('DB_PASSWORD') ?: 'lamp';
    $db   = 'taskmanager';

    $pdo = new PDO(
        "mysql:host={$host};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}`
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db}`");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(255) NOT NULL,
            description TEXT,
            status      ENUM('todo','inprogress','done') DEFAULT 'todo',
            priority    ENUM('alta','media','bassa')     DEFAULT 'media',
            due_date    DATE NULL,
            tags        VARCHAR(255) DEFAULT '',
            position    INT          DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $count = (int) $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    if ($count === 0) {
        seedInitialTasks($pdo);
    }

    return $pdo;
}

function seedInitialTasks(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO tasks (title, description, status, priority, due_date, tags, position)
         VALUES (:title, :desc, :status, :priority, :due, :tags, :pos)"
    );

    $tasks = [
        // ── Completato ────────────────────────────────────────────────────────────
        [
            'title'    => 'Creare il repository GitHub cloud-computing-lab',
            'desc'     => "1. Vai su github.com e accedi\n2. Click su 'New repository' (verde)\n3. Nome: cloud-computing-lab\n4. Description: \"Node.js + Java + PHP Docker lab\"\n5. Public ✅, Initialize with README ✅\n6. Click 'Create repository'",
            'status'   => 'done',
            'priority' => 'alta',
            'due'      => null,
            'tags'     => 'git,github',
            'pos'      => 1,
        ],
        [
            'title'    => 'Configurare il Dev Container (devcontainer.json)',
            'desc'     => "Creare .devcontainer/devcontainer.json con:\n{\n  \"name\": \"Node.js Lab\",\n  \"image\": \"mcr.microsoft.com/devcontainers/base:debian\",\n  \"features\": {\n    \"ghcr.io/devcontainers/features/node:1\": {\"version\":\"24\"},\n    \"ghcr.io/devcontainers/features/docker-in-docker:2\": {\"moby\":false}\n  },\n  \"forwardPorts\": [80, 3000, 8080, 8888]\n}",
            'status'   => 'done',
            'priority' => 'alta',
            'due'      => null,
            'tags'     => 'devcontainer,docker',
            'pos'      => 2,
        ],
        [
            'title'    => 'Aggiungere Dockerfile e docker-compose.yml',
            'desc'     => "File già presenti in docker-container/lamp/:\n- Dockerfile: immagine PHP 8.2 + Apache con estensione pdo_mysql\n- docker-compose.yml: servizi webserver + mariadb in rete bridge dedicata",
            'status'   => 'done',
            'priority' => 'media',
            'due'      => null,
            'tags'     => 'docker,lamp,php',
            'pos'      => 3,
        ],
        // ── In Corso ──────────────────────────────────────────────────────────────
        [
            'title'    => 'Avviare lo stack LAMP con docker-compose',
            'desc'     => "cd docker-container/lamp\ndocker-compose up -d\n\nVerifica che entrambi i container siano 'Up':\ndocker-compose ps\n\nGuarda i log in tempo reale:\ndocker-compose logs -f webserver",
            'status'   => 'inprogress',
            'priority' => 'alta',
            'due'      => null,
            'tags'     => 'docker,lamp',
            'pos'      => 1,
        ],
        [
            'title'    => 'Verificare la connessione PHP → MariaDB',
            'desc'     => "Apri nel browser:\nhttp://localhost:8888/api.php?action=ping\n\nRisposta attesa:\n{\"status\":\"ok\",\"db\":\"taskmanager\",\"php\":\"8.2.x\"}\n\nSe errore: controlla le variabili d'ambiente\nDB_HOST, DB_USERNAME, DB_PASSWORD nel docker-compose.yml",
            'status'   => 'inprogress',
            'priority' => 'alta',
            'due'      => null,
            'tags'     => 'php,mariadb,debug',
            'pos'      => 2,
        ],
        [
            'title'    => 'Navigare nel browser a http://localhost:8888',
            'desc'     => "In GitHub Codespaces:\nLa porta 8888 viene forwardata automaticamente.\n\nClick sulla notifica in basso a destra:\n'Port 8888 is available'\n\nOppure: tab 'Ports' → 8888 → icona 🌐",
            'status'   => 'inprogress',
            'priority' => 'media',
            'due'      => null,
            'tags'     => 'browser,codespaces',
            'pos'      => 3,
        ],
        // ── Da Fare ───────────────────────────────────────────────────────────────
        [
            'title'    => 'Configurare il file .env con le credenziali',
            'desc'     => "Copia il template:\ncp .env.example .env\n\nModifica le variabili:\n  MYSQL_ROOT_PASSWORD=sicura123\n  MYSQL_USER=lamp\n  MYSQL_PASSWORD=lamp123\n  MYSQL_DATABASE=taskmanager\n\n⚠️ Non committare mai il file .env su GitHub!",
            'status'   => 'todo',
            'priority' => 'alta',
            'due'      => null,
            'tags'     => 'config,security',
            'pos'      => 1,
        ],
        [
            'title'    => 'Testare le operazioni CRUD del Kanban',
            'desc'     => "Provare tutte le operazioni:\n✅ Aggiungi un nuovo task (pulsante + in alto)\n✅ Modifica titolo, descrizione e priorità\n✅ Trascina un task tra le colonne\n✅ Cambia priorità (Alta/Media/Bassa)\n✅ Elimina un task completato",
            'status'   => 'todo',
            'priority' => 'media',
            'due'      => null,
            'tags'     => 'test,crud,php',
            'pos'      => 2,
        ],
        [
            'title'    => 'Eseguire docker build manuale dell\'immagine PHP',
            'desc'     => "cd docker-container/lamp\ndocker build -t lamp-php:1.0 .\n\nVerifica l'immagine creata:\ndocker images lamp-php\n\nEsamina i layer:\ndocker history lamp-php:1.0\n\nAvvia in standalone:\ndocker run -p 9090:80 lamp-php:1.0",
            'status'   => 'todo',
            'priority' => 'media',
            'due'      => null,
            'tags'     => 'docker,build',
            'pos'      => 3,
        ],
        [
            'title'    => 'Fare commit e push delle modifiche su GitHub',
            'desc'     => "git add .\ngit commit -m \"feat: aggiungi LAMP Kanban task manager con MariaDB\"\ngit push origin main\n\nVerifica su github.com che la struttura\ndocker-container/lamp/ sia aggiornata",
            'status'   => 'todo',
            'priority' => 'media',
            'due'      => null,
            'tags'     => 'git,github',
            'pos'      => 4,
        ],
        [
            'title'    => 'Aprire il progetto in GitHub Codespaces',
            'desc'     => "1. Vai sul repository GitHub\n2. Click su 'Code' (verde)\n3. Tab 'Codespaces'\n4. Click 'Create codespace on main'\n5. Attendi 1-2 minuti → VS Code nel browser!\n\nVerifica nel terminale:\ndocker --version\nphp --version",
            'status'   => 'todo',
            'priority' => 'bassa',
            'due'      => null,
            'tags'     => 'codespaces,github',
            'pos'      => 5,
        ],
        [
            'title'    => 'Consegnare gli screenshot dell\'esercitazione',
            'desc'     => "Screenshot da consegnare:\n1. Repository GitHub (file structure)\n2. Codespace aperto (VS Code nel browser)\n3. Terminal con php --version e docker --version\n4. Browser con la dashboard Kanban\n5. Browser con /api.php?action=ping (JSON response)\n6. docker-compose ps (entrambi i container Up)",
            'status'   => 'todo',
            'priority' => 'bassa',
            'due'      => null,
            'tags'     => 'consegna,screenshot',
            'pos'      => 6,
        ],
    ];

    foreach ($tasks as $t) {
        $stmt->execute([
            ':title'    => $t['title'],
            ':desc'     => $t['desc'],
            ':status'   => $t['status'],
            ':priority' => $t['priority'],
            ':due'      => $t['due'],
            ':tags'     => $t['tags'],
            ':pos'      => $t['pos'],
        ]);
    }
}

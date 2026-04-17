<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kanban Board – Cloud Computing Lab</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<style>
/* ── Reset & Base ─────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:         #0f172a;
  --surface:    #1e293b;
  --surface2:   #273348;
  --border:     #334155;
  --text:       #e2e8f0;
  --text-muted: #94a3b8;
  --accent:     #6366f1;
  --accent2:    #818cf8;
  --col-todo:       #3b82f6;
  --col-inprogress: #f59e0b;
  --col-done:       #10b981;
  --pri-alta:  #ef4444;
  --pri-media: #f97316;
  --pri-bassa: #22c55e;
  --radius: 12px;
  --shadow: 0 4px 24px rgba(0,0,0,.45);
}
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
/* ── Header ──────────────────────────────────────────────────────────────── */
header {
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e293b 100%);
  border-bottom: 1px solid var(--border);
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  flex-shrink: 0;
  box-shadow: 0 2px 20px rgba(99,102,241,.2);
}
.logo { display: flex; align-items: center; gap: .6rem; font-size: 1.15rem; font-weight: 700; letter-spacing: -.02em; }
.logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #6366f1, #a855f7); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.header-meta { display: flex; align-items: center; gap: 1rem; }
.badge-php { background: rgba(99,102,241,.25); color: var(--accent2); border: 1px solid rgba(99,102,241,.35); border-radius: 20px; padding: .25rem .75rem; font-size: .72rem; font-weight: 600; letter-spacing: .03em; }
.btn-add-header { background: linear-gradient(135deg, #6366f1, #a855f7); border: none; color: #fff; padding: .5rem 1.1rem; border-radius: 8px; font-size: .85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: .4rem; transition: opacity .15s, transform .1s; }
.btn-add-header:hover { opacity: .88; transform: translateY(-1px); }
/* ── Board ───────────────────────────────────────────────────────────────── */
main { flex: 1; display: flex; gap: 1.25rem; padding: 1.5rem; overflow-x: auto; align-items: flex-start; }
.column { flex: 0 0 320px; background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); display: flex; flex-direction: column; max-height: calc(100vh - 100px); }
.column-header { padding: 1rem 1.1rem .8rem; border-radius: var(--radius) var(--radius) 0 0; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.column[data-status="todo"]       .column-header { background: linear-gradient(135deg,rgba(59,130,246,.18),rgba(59,130,246,.06)); border-bottom: 2px solid var(--col-todo); }
.column[data-status="inprogress"] .column-header { background: linear-gradient(135deg,rgba(245,158,11,.18),rgba(245,158,11,.06)); border-bottom: 2px solid var(--col-inprogress); }
.column[data-status="done"]       .column-header { background: linear-gradient(135deg,rgba(16,185,129,.18),rgba(16,185,129,.06)); border-bottom: 2px solid var(--col-done); }
.col-title { display: flex; align-items: center; gap: .5rem; font-size: .9rem; font-weight: 700; letter-spacing: .01em; }
.col-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.column[data-status="todo"]       .col-dot { background: var(--col-todo); box-shadow: 0 0 6px var(--col-todo); }
.column[data-status="inprogress"] .col-dot { background: var(--col-inprogress); box-shadow: 0 0 6px var(--col-inprogress); }
.column[data-status="done"]       .col-dot { background: var(--col-done); box-shadow: 0 0 6px var(--col-done); }
.col-count { background: var(--surface2); border: 1px solid var(--border); border-radius: 20px; padding: .15rem .6rem; font-size: .72rem; font-weight: 700; color: var(--text-muted); }
.col-cards { padding: .75rem; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: .65rem; min-height: 80px; }
/* ── Card ────────────────────────────────────────────────────────────────── */
.card { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: .9rem 1rem; cursor: grab; transition: transform .15s, box-shadow .15s, border-color .15s; position: relative; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.5); border-color: rgba(99,102,241,.45); }
.card.sortable-ghost { opacity: .35; border: 2px dashed var(--accent); }
.card.sortable-chosen { cursor: grabbing; }
.card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; margin-bottom: .55rem; }
.card-title { font-size: .88rem; font-weight: 600; line-height: 1.35; color: var(--text); flex: 1; }
.card-actions { display: flex; gap: .2rem; flex-shrink: 0; opacity: 0; transition: opacity .15s; }
.card:hover .card-actions { opacity: 1; }
.btn-icon { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: .2rem .3rem; border-radius: 5px; font-size: .8rem; transition: color .15s, background .15s; }
.btn-icon:hover { color: var(--text); background: rgba(255,255,255,.08); }
.btn-icon.del:hover { color: #f87171; }
.card-desc { font-size: .78rem; color: var(--text-muted); line-height: 1.45; margin-bottom: .6rem; white-space: pre-line; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.card-meta { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
.badge-pri { font-size: .68rem; font-weight: 700; letter-spacing: .04em; padding: .15rem .55rem; border-radius: 20px; text-transform: uppercase; }
.pri-alta  { background: rgba(239,68,68,.18);  color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }
.pri-media { background: rgba(249,115,22,.18); color: #fdba74; border: 1px solid rgba(249,115,22,.3); }
.pri-bassa { background: rgba(34,197,94,.18);  color: #86efac; border: 1px solid rgba(34,197,94,.3); }
.badge-tag { font-size: .67rem; background: rgba(99,102,241,.15); color: var(--accent2); border: 1px solid rgba(99,102,241,.25); border-radius: 20px; padding: .12rem .5rem; }
.badge-due { font-size: .68rem; color: var(--text-muted); display: flex; align-items: center; gap: .2rem; margin-left: auto; }
.badge-due.overdue { color: #f87171; }
/* ── Modal ───────────────────────────────────────────────────────────────── */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; pointer-events: none; transition: opacity .2s; }
.modal-backdrop.open { opacity: 1; pointer-events: all; }
.modal { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: min(540px, 95vw); box-shadow: var(--shadow); transform: translateY(16px) scale(.97); transition: transform .2s; }
.modal-backdrop.open .modal { transform: none; }
.modal-header { padding: 1.25rem 1.5rem 1rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.modal-title { font-size: 1rem; font-weight: 700; }
.btn-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: .2rem; border-radius: 6px; transition: color .15s, background .15s; }
.btn-close:hover { color: var(--text); background: rgba(255,255,255,.08); }
.modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: .35rem; }
.form-group label { font-size: .78rem; font-weight: 600; color: var(--text-muted); letter-spacing: .04em; text-transform: uppercase; }
.form-group input, .form-group select, .form-group textarea { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: .6rem .8rem; color: var(--text); font-size: .875rem; font-family: inherit; transition: border-color .15s; outline: none; resize: vertical; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--accent); }
.form-group textarea { min-height: 100px; }
.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: .75rem; }
.btn { padding: .6rem 1.2rem; border-radius: 8px; font-size: .875rem; font-weight: 600; cursor: pointer; border: none; transition: opacity .15s, transform .1s; }
.btn:hover { opacity: .88; transform: translateY(-1px); }
.btn-secondary { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
.btn-primary { background: linear-gradient(135deg, #6366f1, #a855f7); color: #fff; }
.btn-danger  { background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; }
/* ── Toast ───────────────────────────────────────────────────────────────── */
#toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 2000; display: flex; flex-direction: column-reverse; gap: .5rem; }
.toast { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: .75rem 1.1rem; font-size: .83rem; font-weight: 500; color: var(--text); box-shadow: var(--shadow); display: flex; align-items: center; gap: .5rem; animation: slideIn .25s ease; max-width: 300px; }
.toast.success { border-left: 3px solid var(--col-done); }
.toast.error   { border-left: 3px solid var(--pri-alta); }
@keyframes slideIn { from { transform: translateX(40px); opacity:0; } to { transform: none; opacity:1; } }
/* ── Confirm dialog ──────────────────────────────────────────────────────── */
#confirm-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.7); display: none; align-items: center; justify-content: center; z-index: 1100; }
#confirm-backdrop.open { display: flex; }
#confirm-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; width: min(380px, 90vw); box-shadow: var(--shadow); }
#confirm-box p { margin-bottom: 1.2rem; font-size: .9rem; line-height: 1.5; }
#confirm-box .actions { display: flex; justify-content: flex-end; gap: .75rem; }
/* ── Loading ─────────────────────────────────────────────────────────────── */
.spinner { display: flex; align-items: center; justify-content: center; padding: 3rem; color: var(--text-muted); gap: .6rem; font-size: .85rem; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin-circle { width: 20px; height: 20px; border: 2px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin .7s linear infinite; }
/* ── Footer ──────────────────────────────────────────────────────────────── */
footer { border-top: 1px solid var(--border); padding: .6rem 1.5rem; font-size: .72rem; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
footer a { color: var(--accent2); text-decoration: none; }
footer a:hover { text-decoration: underline; }
</style>
</head>
<body>

<header>
  <div class="logo">
    <div class="logo-icon">📋</div>
    <span>Kanban Board</span>
  </div>
  <div class="header-meta">
    <span class="badge-php" id="php-ver">PHP + MariaDB</span>
    <button class="btn-add-header" onclick="openModal()">＋ Nuovo Task</button>
  </div>
</header>

<main id="board">
  <div class="spinner"><div class="spin-circle"></div> Caricamento...</div>
</main>

<footer>
  <span>Cloud Computing Lab · LAMP Stack (PHP 8.2 + MariaDB 10.11 + Apache)</span>
  <span>
    <a href="api.php?action=ping" target="_blank">API Health</a> ·
    <a href="api.php?action=list" target="_blank">JSON Data</a>
  </span>
</footer>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="task-modal">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <span class="modal-title" id="modal-title">Nuovo Task</span>
      <button class="btn-close" onclick="closeModal()" aria-label="Chiudi">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label for="f-title">Titolo *</label>
        <input type="text" id="f-title" placeholder="Cosa devi fare?" required>
      </div>
      <div class="form-group">
        <label for="f-desc">Descrizione</label>
        <textarea id="f-desc" placeholder="Dettagli, comandi, istruzioni…"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="f-status">Colonna</label>
          <select id="f-status">
            <option value="todo">📋 Da Fare</option>
            <option value="inprogress">🔄 In Corso</option>
            <option value="done">✅ Completato</option>
          </select>
        </div>
        <div class="form-group">
          <label for="f-priority">Priorità</label>
          <select id="f-priority">
            <option value="alta">🔴 Alta</option>
            <option value="media" selected>🟠 Media</option>
            <option value="bassa">🟢 Bassa</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="f-due">Scadenza</label>
          <input type="date" id="f-due">
        </div>
        <div class="form-group">
          <label for="f-tags">Tag (separati da virgola)</label>
          <input type="text" id="f-tags" placeholder="docker,php,git">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Annulla</button>
      <button class="btn btn-primary" id="modal-save-btn" onclick="saveTask()">Salva Task</button>
    </div>
  </div>
</div>

<!-- Confirm Delete -->
<div id="confirm-backdrop">
  <div id="confirm-box">
    <p id="confirm-msg">Eliminare questo task? L'operazione non è reversibile.</p>
    <div class="actions">
      <button class="btn btn-secondary" onclick="closeConfirm()">Annulla</button>
      <button class="btn btn-danger" id="confirm-ok">Elimina</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script>
'use strict';
let tasks = [], editId = null, sortables = [];
const COLUMNS = [
  { status: 'todo',       label: '📋 Da Fare'    },
  { status: 'inprogress', label: '🔄 In Corso'   },
  { status: 'done',       label: '✅ Completato' },
];

(async function init() {
  await loadTasks();
  try {
    const p = await apiFetch('ping');
    document.getElementById('php-ver').textContent = `PHP ${p.php} · MariaDB · ${p.db}`;
  } catch (_) {}
})();

async function apiFetch(action, body = null) {
  const opts = { headers: { 'Content-Type': 'application/json' } };
  if (body) { opts.method = 'POST'; opts.body = JSON.stringify(body); }
  const res = await fetch(`api.php?action=${action}`, opts);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
  return data;
}

async function loadTasks() {
  try {
    const data = await apiFetch('list');
    tasks = data.tasks;
    renderBoard();
  } catch (e) {
    document.getElementById('board').innerHTML =
      `<div class="spinner" style="color:#f87171">⚠️ Errore: ${escHtml(e.message)}<br>
       <small>Verifica che docker-compose sia avviato e MariaDB sia pronto</small></div>`;
  }
}

function renderBoard() {
  sortables.forEach(s => s.destroy()); sortables = [];
  const board = document.getElementById('board');
  board.innerHTML = '';
  COLUMNS.forEach(col => {
    const colTasks = tasks.filter(t => t.status === col.status)
      .sort((a, b) => (a.position - b.position) || (a.id - b.id));
    const colEl = document.createElement('div');
    colEl.className = 'column'; colEl.dataset.status = col.status;
    colEl.innerHTML = `
      <div class="column-header">
        <div class="col-title"><span class="col-dot"></span><span>${col.label}</span></div>
        <span class="col-count" id="count-${col.status}">${colTasks.length}</span>
      </div>
      <div class="col-cards" id="cards-${col.status}">
        ${colTasks.length === 0
          ? `<div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);font-size:.8rem">
               <div style="font-size:1.5rem;margin-bottom:.5rem;opacity:.3">📋</div>Trascina qui un task</div>`
          : colTasks.map(renderCard).join('')}
      </div>`;
    board.appendChild(colEl);
    sortables.push(Sortable.create(colEl.querySelector('.col-cards'), {
      group: 'kanban', animation: 180,
      ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
      onAdd(evt) { moveTask(parseInt(evt.item.dataset.id), col.status); },
    }));
  });
}

function renderCard(t) {
  const priLabel = { alta:'🔴 Alta', media:'🟠 Media', bassa:'🟢 Bassa' }[t.priority] || '';
  const tags = (t.tags||'').split(',').map(s=>s.trim()).filter(Boolean);
  const today = new Date().toISOString().split('T')[0];
  const overdue = t.due_date && t.due_date < today && t.status !== 'done';
  const dueFmt = t.due_date
    ? new Date(t.due_date).toLocaleDateString('it-IT',{day:'2-digit',month:'short'}) : '';
  const safeTitle = escHtml(t.title).replace(/'/g, "&#039;");
  return `
    <div class="card" data-id="${t.id}">
      <div class="card-top">
        <div class="card-title">${escHtml(t.title)}</div>
        <div class="card-actions">
          <button class="btn-icon" title="Modifica" onclick="editTask(${t.id})">✏️</button>
          <button class="btn-icon del" title="Elimina" onclick="confirmDelete(${t.id},'${safeTitle}')">🗑️</button>
        </div>
      </div>
      ${t.description ? `<div class="card-desc">${escHtml(t.description)}</div>` : ''}
      <div class="card-meta">
        <span class="badge-pri pri-${t.priority}">${priLabel}</span>
        ${tags.map(tag=>`<span class="badge-tag">${escHtml(tag)}</span>`).join('')}
        ${dueFmt?`<span class="badge-due ${overdue?'overdue':''}">📅 ${dueFmt}</span>`:''}
      </div>
    </div>`;
}

function openModal(status = 'todo') {
  editId = null;
  document.getElementById('modal-title').textContent    = 'Nuovo Task';
  document.getElementById('modal-save-btn').textContent = 'Salva Task';
  document.getElementById('f-title').value    = '';
  document.getElementById('f-desc').value     = '';
  document.getElementById('f-status').value   = status;
  document.getElementById('f-priority').value = 'media';
  document.getElementById('f-due').value      = '';
  document.getElementById('f-tags').value     = '';
  document.getElementById('task-modal').classList.add('open');
  setTimeout(() => document.getElementById('f-title').focus(), 50);
}

function editTask(id) {
  const t = tasks.find(x => x.id == id); if (!t) return;
  editId = id;
  document.getElementById('modal-title').textContent    = 'Modifica Task';
  document.getElementById('modal-save-btn').textContent = 'Aggiorna Task';
  document.getElementById('f-title').value    = t.title;
  document.getElementById('f-desc').value     = t.description || '';
  document.getElementById('f-status').value   = t.status;
  document.getElementById('f-priority').value = t.priority;
  document.getElementById('f-due').value      = t.due_date || '';
  document.getElementById('f-tags').value     = t.tags || '';
  document.getElementById('task-modal').classList.add('open');
  setTimeout(() => document.getElementById('f-title').focus(), 50);
}

function closeModal() { document.getElementById('task-modal').classList.remove('open'); }

async function saveTask() {
  const title = document.getElementById('f-title').value.trim();
  if (!title) { showToast('Il titolo è obbligatorio','error'); document.getElementById('f-title').focus(); return; }
  const payload = {
    title, description: document.getElementById('f-desc').value,
    status: document.getElementById('f-status').value,
    priority: document.getElementById('f-priority').value,
    due_date: document.getElementById('f-due').value,
    tags: document.getElementById('f-tags').value,
  };
  if (editId) payload.id = editId;
  try {
    const data = await apiFetch(editId ? 'update' : 'create', payload);
    if (editId) { tasks = tasks.map(t => t.id == editId ? data.task : t); showToast('Task aggiornato ✓','success'); }
    else        { tasks.push(data.task); showToast('Task creato ✓','success'); }
    closeModal(); renderBoard();
  } catch (e) { showToast('Errore: ' + e.message, 'error'); }
}

async function moveTask(id, newStatus) {
  try {
    const data = await apiFetch('move', { id, status: newStatus });
    tasks = tasks.map(t => t.id == id ? data.task : t);
    const labels = { todo:'Da Fare', inprogress:'In Corso', done:'Completato' };
    showToast(`→ ${labels[newStatus]}`, 'success');
    COLUMNS.forEach(col => {
      const el = document.getElementById(`count-${col.status}`);
      if (el) el.textContent = tasks.filter(t => t.status === col.status).length;
    });
  } catch (e) { showToast('Errore: ' + e.message,'error'); await loadTasks(); }
}

let pendingDeleteId = null;
function confirmDelete(id, title) {
  pendingDeleteId = id;
  document.getElementById('confirm-msg').textContent = `Eliminare "${title}"? L'operazione non è reversibile.`;
  document.getElementById('confirm-backdrop').classList.add('open');
}
function closeConfirm() { pendingDeleteId = null; document.getElementById('confirm-backdrop').classList.remove('open'); }
document.getElementById('confirm-ok').addEventListener('click', async () => {
  if (!pendingDeleteId) return;
  try {
    await apiFetch('delete', { id: pendingDeleteId });
    tasks = tasks.filter(t => t.id !== pendingDeleteId);
    showToast('Task eliminato','success'); closeConfirm(); renderBoard();
  } catch (e) { showToast('Errore: ' + e.message,'error'); closeConfirm(); }
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeModal(); closeConfirm(); }
  if (e.key === 'Enter' && document.getElementById('task-modal').classList.contains('open') && document.activeElement.tagName !== 'TEXTAREA') saveTask();
  if ((e.ctrlKey||e.metaKey) && e.key === 'k') { e.preventDefault(); openModal(); }
});
document.getElementById('task-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = (type==='success'?'✅':'❌') + ' ' + escHtml(msg);
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
</script>
</body>
</html>

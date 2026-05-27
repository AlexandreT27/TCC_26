// ===== APP MAIN =====
const nomeUsuario = "Bernardo Pires";

// Toast global
let toastTimer;
function showToast(msg, icon = 'ti-check') {
  const t = document.getElementById('toast');
  t.innerHTML = `<i class="ti ${icon}"></i> ${msg}`;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2400);
}

// Sidebar toggle
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('collapsed');
}

// Navegação entre páginas
function navTo(pageId, el) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(pageId).classList.add('active');

  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.closest('.nav-item').classList.add('active');

  if (pageId === 'page-metas')    MetasModule.render();
  if (pageId === 'page-graficos') GraficosModule.render();
}

// Pesquisa
document.addEventListener('DOMContentLoaded', () => {
  document.querySelector('.username').textContent = nomeUsuario;

  document.getElementById('search-input')?.addEventListener('input', e => {
    if (e.target.value.length > 2) showToast(`Pesquisando: "${e.target.value}"`, 'ti-search');
  });

  MetasModule.init();
  GraficosModule.init();
});

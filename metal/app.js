
// ── Toast global ────────────────────────────────────────────────
let toastTimer;
function showToast(msg, icon = 'ti-check') {
  const t = document.getElementById('toast');
  t.innerHTML = `<i class="ti ${icon}"></i> ${msg}`;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2400);
}

// ── Sidebar toggle ───────────────────────────────────────────────
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('collapsed');
}

// ── Navegação entre páginas ──────────────────────────────────────
/**
 * @param {string} pageId   - id da div.page de destino
 * @param {Element} el      - elemento clicado no nav (para setar .active)
 * @param {string} [tab]    - tab opcional: 'conta' | 'perfil' | null
 */
function navTo(pageId, el, tab) {
  // Esconde todas as páginas
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(pageId).classList.add('active');

  // Marca item do menu
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.closest('.nav-item').classList.add('active');

  // Abre tab específica em page-conta
  if (pageId === 'page-conta' && tab) {
    const tabId = tab === 'perfil' ? 'perfil-tab' : 'conta-tab';
    const btnIdx = tab === 'perfil' ? 1 : 0;
    const btns = document.querySelectorAll('#conta-tabs .tab-btn');
    switchTab(tabId, btns[btnIdx]);
  }

  // Inicializa módulos ao entrar na página
  if (pageId === 'page-metas')    MetasModule.render();
  if (pageId === 'page-graficos') GraficosModule.render();
}

// ── Tabs (Conta / Editar Perfil) ─────────────────────────────────
function switchTab(id, btn) {
  document.querySelectorAll('#conta-tabs .tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('#page-conta .tab-panel').forEach(p => p.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const panel = document.getElementById(id);
  if (panel) panel.classList.add('active');
}

// ── Preview do nome no sidebar em tempo real ─────────────────────
function previewNome() {
  const nome      = document.getElementById('f-nome')?.value.trim()      || '';
  const sobrenome = document.getElementById('f-sobrenome')?.value.trim() || '';
  const full      = (nome + ' ' + sobrenome).trim();
  const el = document.getElementById('sidebar-username');
  if (el) el.textContent = full || '—';
}

// ── Toggle olho da senha ─────────────────────────────────────────
function togglePwd(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (!inp || !ico) return;
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.className = 'ti ti-eye-off';
  } else {
    inp.type = 'password';
    ico.className = 'ti ti-eye';
  }
}

// ── Tema claro / escuro (visual + hidden input) ───────────────────
function setTheme(theme) {
  // light por padrão.
  // O pill de tema salva a preferência via POST; aqui só dá feedback visual.
  const inputTema = document.getElementById('input-tema');
  if (inputTema) inputTema.value = theme;

  const optDark  = document.getElementById('opt-dark');
  const optLight = document.getElementById('opt-light');
  if (optDark)  optDark.classList.toggle('active',  theme === 'dark');
  if (optLight) optLight.classList.toggle('active', theme === 'light');

  showToast(
    theme === 'dark' ? 'Tema escuro ativado' : 'Tema claro ativado',
    theme === 'dark' ? 'ti-moon' : 'ti-sun'
  );
}

// ── Init ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Pesquisa do dashboard
  document.getElementById('search-input')?.addEventListener('input', e => {
    if (e.target.value.length > 2)
      showToast(`Pesquisando: "${e.target.value}"`, 'ti-search');
  });

  // Inicializa módulos
  MetasModule.init();
  GraficosModule.init();

  // Abre página correta após POST (injetado pelo PHP via data-attr)
  const paginaInicial = document.body.dataset.pagina;
  if (paginaInicial && paginaInicial !== 'page-metas') {
    // encontra o nav-item correspondente
    const pageMap = {
      'page-conta'   : 0,   // índice no nav-menu
      'page-config'  : 2,
      'page-graficos': 3,
    };
    const idx = pageMap[paginaInicial] ?? -1;
    const navEl = idx >= 0
      ? document.querySelectorAll('.nav-item')[idx]
      : null;

    navTo(paginaInicial, navEl);

    // se for perfil, abre a tab certa
    if (paginaInicial === 'page-conta') {
      const hasPerfil = document.querySelector('.alert');
      if (hasPerfil) {
        const btns = document.querySelectorAll('#conta-tabs .tab-btn');
        switchTab('perfil-tab', btns[1]);
      }
    }
  }
});

<?php
$nomeUsuario = "Bernardo Pires";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>METAL Financeiro - Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar">
  <div class="menu-toggle" onclick="toggleSidebar()">
    <i class="ti ti-menu-2"></i>
  </div>

  <div class="profile-container">
    <div class="avatar-placeholder"><i class="ti ti-user-circle"></i></div>
    <div class="username"><?php echo htmlspecialchars($nomeUsuario); ?></div>
  </div>

  <ul class="nav-menu">
    <li class="nav-item" onclick="navTo('page-dashboard', this)">
      <a><i class="ti ti-wallet"></i><span class="nav-label">Conta</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard', this)">
      <a><i class="ti ti-user"></i><span class="nav-label">Perfil</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard', this)">
      <a><i class="ti ti-settings"></i><span class="nav-label">Configuração</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-graficos', this)">
      <a><i class="ti ti-chart-bar"></i><span class="nav-label">Gráficos</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard', this)">
      <a><i class="ti ti-notes"></i><span class="nav-label">Notas</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard', this)">
      <a><i class="ti ti-book"></i><span class="nav-label">Cursos</span></a>
    </li>
    <li class="nav-item active" onclick="navTo('page-metas', this)">
      <a><i class="ti ti-target"></i><span class="nav-label">Metas</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard', this)">
      <a><i class="ti ti-switch-horizontal"></i><span class="nav-label">Mudar de conta</span></a>
    </li>
  </ul>

  <button class="btn-sair" onclick="showToast('Saindo...', 'ti-logout')">
    <i class="ti ti-logout"></i>
    <span class="btn-sair-label">Sair</span>
  </button>
</aside>

<!-- ===================== MAIN CONTENT ===================== -->
<main class="main-content">

  <div class="header-top">
    <div class="logo">METAL<span>Financeiro</span></div>
    <div class="header-icons">
      <button class="icon-btn" onclick="showToast('Sem novas notificações', 'ti-bell')" title="Notificações">
        <i class="ti ti-bell"></i>
        <span class="notif-dot"></span>
      </button>
      <button class="icon-btn" onclick="showToast('Central de ajuda', 'ti-help-circle')" title="Ajuda">
        <i class="ti ti-help-circle"></i>
      </button>
    </div>
  </div>

  <!-- ===== PAGE: DASHBOARD ===== -->
  <div class="page" id="page-dashboard">

    <div class="search-container">
      <div class="search-box">
        <input type="text" id="search-input" placeholder="Pesquisar...">
        <i class="ti ti-search"></i>
      </div>
    </div>

    <div class="section-title">Acesso rápido</div>

    <div class="cards-wrapper">
      <div class="cards-row-top">
        <div class="card" onclick="navTo('page-metas', document.querySelector('.nav-item:nth-child(7)'))">
          <i class="ti ti-target"></i>
          <span>Metas</span>
        </div>
      </div>
      <div class="cards-row-bottom">
        <div class="card" onclick="showToast('Tutoriais em breve!', 'ti-video')">
          <i class="ti ti-video"></i>
          <span>Tutoriais</span>
        </div>
        <div class="card" onclick="showToast('Calculadora em breve!', 'ti-calculator')">
          <i class="ti ti-calculator"></i>
          <span>Cálculos</span>
        </div>
      </div>
    </div>

    <div class="section-title">Resumo financeiro</div>
    <div class="stats-row">
      <div class="stat">
        <div class="stat-label">Saldo atual</div>
        <div class="stat-value">R$ 12.480</div>
        <div class="stat-up">↑ +3,2% este mês</div>
      </div>
      <div class="stat">
        <div class="stat-label">Meta mensal</div>
        <div class="stat-value">78%</div>
        <div class="stat-sub">R$ 7.800 de R$ 10.000</div>
      </div>
      <div class="stat">
        <div class="stat-label">Gastos</div>
        <div class="stat-value">R$ 3.210</div>
        <div class="stat-down">↓ -1,5% vs mês anterior</div>
      </div>
    </div>

  </div>

  <!-- ===== PAGE: METAS ===== -->
  <div class="page active" id="page-metas">

    <div class="page-header">
      <h2>Minhas <span>Metas</span></h2>
      <button class="btn-primary" onclick="MetasModule.abrirModal()">
        <i class="ti ti-plus"></i> Nova meta
      </button>
    </div>

    <div class="metas-resumo">
      <div class="resumo-card">
        <div class="resumo-num" id="resumo-total">0</div>
        <div class="resumo-label">Total de metas</div>
      </div>
      <div class="resumo-card">
        <div class="resumo-num" id="resumo-concluidas">0</div>
        <div class="resumo-label">Concluídas</div>
      </div>
      <div class="resumo-card">
        <div class="resumo-num" id="resumo-saldo">R$ 0</div>
        <div class="resumo-label">Total poupado</div>
      </div>
    </div>

    <div class="section-title">Suas metas</div>
    <div class="metas-list" id="metas-list"></div>

  </div>

  <!-- ===== PAGE: GRAFICOS ===== -->
  <div class="page" id="page-graficos">

    <div class="page-header">
      <h2>Meus <span>Gráficos</span></h2>
      <button class="btn-primary" onclick="GraficosModule.abrirModal()">
        <i class="ti ti-plus"></i> Novo gráfico
      </button>
    </div>

    <div class="graficos-grid" id="graficos-grid"></div>

  </div>

</main>

<!-- ===================== MODAL: NOVA META ===================== -->
<div class="modal-overlay" id="modal-meta">
  <div class="modal">
    <div class="modal-title" id="modal-meta-title">
      <i class="ti ti-target"></i> Nova meta
    </div>

    <form id="form-meta" onsubmit="return false">
      <div class="form-group">
        <label class="form-label">Nome da meta *</label>
        <input id="meta-nome" class="form-input" type="text" placeholder="Ex: Viagem para Europa">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Categoria</label>
          <select id="meta-categoria" class="form-select">
            <option value="viagem">✈ Viagem</option>
            <option value="casa">🏠 Casa</option>
            <option value="carro">🚗 Carro</option>
            <option value="educacao">📚 Educação</option>
            <option value="emergencia">🛡 Emergência</option>
            <option value="aposentadoria">📈 Aposentadoria</option>
            <option value="outro">⭐ Outro</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select id="meta-status" class="form-select">
            <option value="andamento">Em andamento</option>
            <option value="concluida">Concluída</option>
            <option value="atrasada">Atrasada</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Valor total (R$) *</label>
          <input id="meta-total" class="form-input" type="number" min="0" placeholder="15000">
        </div>
        <div class="form-group">
          <label class="form-label">Valor atual (R$)</label>
          <input id="meta-atual" class="form-input" type="number" min="0" placeholder="8500">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Prazo *</label>
        <input id="meta-prazo" class="form-input" type="month">
      </div>
    </form>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="MetasModule.fecharModal()">Cancelar</button>
      <button class="btn-primary" onclick="MetasModule.salvar()">
        <i class="ti ti-device-floppy"></i> Salvar
      </button>
    </div>
  </div>
</div>

<!-- ===================== MODAL: NOVO GRAFICO ===================== -->
<div class="modal-overlay" id="modal-grafico">
  <div class="modal">
    <div class="modal-title">
      <i class="ti ti-chart-bar"></i> Novo gráfico
    </div>

    <form id="form-grafico" onsubmit="return false">
      <div class="form-group">
        <label class="form-label">Título *</label>
        <input id="g-titulo" class="form-input" type="text" placeholder="Ex: Receitas vs Despesas">
      </div>

      <div class="form-group">
        <label class="form-label">Tipo de gráfico</label>
        <div class="grafico-tipos">
          <button type="button" class="tipo-btn selected" data-tipo="bar"     onclick="GraficosModule.selecionarTipo('bar')">
            <i class="ti ti-chart-bar"></i> Barras
          </button>
          <button type="button" class="tipo-btn" data-tipo="line"    onclick="GraficosModule.selecionarTipo('line')">
            <i class="ti ti-chart-line"></i> Linha
          </button>
          <button type="button" class="tipo-btn" data-tipo="doughnut" onclick="GraficosModule.selecionarTipo('doughnut')">
            <i class="ti ti-chart-donut"></i> Rosca
          </button>
          <button type="button" class="tipo-btn" data-tipo="pie"     onclick="GraficosModule.selecionarTipo('pie')">
            <i class="ti ti-chart-pie"></i> Pizza
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Rótulos (separados por vírgula) *</label>
        <input id="g-labels" class="form-input" type="text" placeholder="Jan, Fev, Mar, Abr, Mai, Jun">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nome da série 1</label>
          <input id="g-data1-nome" class="form-input" type="text" placeholder="Receitas">
        </div>
        <div class="form-group">
          <label class="form-label">Valores *</label>
          <input id="g-data1" class="form-input" type="text" placeholder="4200,4800,5100">
        </div>
      </div>

      <div id="wrapper-dataset2">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nome da série 2</label>
            <input id="g-data2-nome" class="form-input" type="text" placeholder="Despesas">
          </div>
          <div class="form-group">
            <label class="form-label">Valores série 2</label>
            <input id="g-data2" class="form-input" type="text" placeholder="3100,3400,2900">
          </div>
        </div>
      </div>
    </form>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="GraficosModule.fecharModal()">Cancelar</button>
      <button class="btn-primary" onclick="GraficosModule.salvar()">
        <i class="ti ti-device-floppy"></i> Adicionar
      </button>
    </div>
  </div>
</div>

<!-- ===================== TOAST ===================== -->
<div class="toast" id="toast"></div>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="metas.js"></script>
<script src="graficos.js"></script>
<script src="app.js"></script>

</body>
</html>

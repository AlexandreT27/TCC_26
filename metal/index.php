<?php
session_start();

/* ── Usuário (session) */
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = [
        'nome'      => 'Bernardo',
        'sobrenome' => 'Pires',
        'email'     => 'bernardo.pires@email.com',
        'telefone'  => '',
        'bio'       => '',
    ];
}

/* ── Configurações (session)  */
if (!isset($_SESSION['config'])) {
    $_SESSION['config'] = [
        'tema'           => 'linght',
        'fonte'          => 'medio',
        'animacoes'      => true,
        'alerta_metas'   => true,
        'resumo_semanal' => true,
        'alerta_gastos'  => false,
        'email_notif'    => true,
        '2fa'            => false,
        'ocultar_saldo'  => false,
        'sessao'         => '30min',
        'sync_auto'      => true,
    ];
}

$msgPerfil  = '';
$erroPerfil = '';
$msgConfig  = '';

/*  salvar perfil */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_perfil') {
    $nome      = trim(htmlspecialchars($_POST['nome']      ?? ''));
    $sobrenome = trim(htmlspecialchars($_POST['sobrenome'] ?? ''));
    $email     = trim(htmlspecialchars($_POST['email']     ?? ''));
    $telefone  = trim(htmlspecialchars($_POST['telefone']  ?? ''));
    $bio       = trim(htmlspecialchars($_POST['bio']       ?? ''));
    $novaSenha = $_POST['nova_senha']     ?? '';
    $confSenha = $_POST['confirma_senha'] ?? '';

    if ($nome === '' || $email === '') {
        $erroPerfil = 'Nome e e-mail são obrigatórios.';
    } elseif ($novaSenha !== '' && $novaSenha !== $confSenha) {
        $erroPerfil = 'As senhas não coincidem.';
    } else {
        $_SESSION['usuario'] = compact('nome','sobrenome','email','telefone','bio');
        $msgPerfil = 'Perfil atualizado com sucesso!';
    }
}

/* ── POST: salvar configurações ────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_config') {
    $_SESSION['config'] = [
        'tema'           => in_array($_POST['tema'] ?? '', ['dark','light']) ? $_POST['tema'] : 'dark',
        'fonte'          => htmlspecialchars($_POST['fonte']  ?? 'medio'),
        'sessao'         => htmlspecialchars($_POST['sessao'] ?? '30min'),
        'animacoes'      => isset($_POST['animacoes']),
        'alerta_metas'   => isset($_POST['alerta_metas']),
        'resumo_semanal' => isset($_POST['resumo_semanal']),
        'alerta_gastos'  => isset($_POST['alerta_gastos']),
        'email_notif'    => isset($_POST['email_notif']),
        '2fa'            => isset($_POST['2fa']),
        'ocultar_saldo'  => isset($_POST['ocultar_saldo']),
        'sync_auto'      => isset($_POST['sync_auto']),
    ];
    $msgConfig = 'Configurações salvas!';
}

/* ── Helpers ──────────*/
function chk($v): string  { return $v ? 'checked' : ''; }
function sel($a,$b): string { return $a === $b ? 'selected' : ''; }

$u   = $_SESSION['usuario'];
$cfg = $_SESSION['config'];
$nomeCompleto = $u['nome'] . ' ' . $u['sobrenome'];

/* qual page abrir direto após POST */
$paginaInicial = 'page-dashboard'; // padrão
if ($msgPerfil || $erroPerfil) $paginaInicial = 'page-dashboard';
if ($msgPerfil)                $paginaInicial = 'page-dashboard';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>METAL Financeiro</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body data-pagina="<?= $paginaInicial ?>">

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
  <div class="menu-toggle" onclick="toggleSidebar()">
    <i class="ti ti-menu-2"></i>
  </div>

  <div class="profile-container">
    <div class="avatar-placeholder"><i class="ti ti-user-circle"></i></div>
    <div class="username" id="sidebar-username"><?= htmlspecialchars($nomeCompleto) ?></div>
  </div>

  <ul class="nav-menu">
    <li class="nav-item" onclick="navTo('page-conta',   this)">
      <a><i class="ti ti-wallet"></i><span class="nav-label">Conta</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard',   this, 'perfil')">
      <a><i class="ti ti-user"></i><span class="nav-label">Perfil</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-config',  this)">
      <a><i class="ti ti-settings"></i><span class="nav-label">Configuração</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-graficos',this)">
      <a><i class="ti ti-chart-bar"></i><span class="nav-label">Gráficos</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard',this)">
      <a><i class="ti ti-notes"></i><span class="nav-label">Notas</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard',this)">
      <a><i class="ti ti-book"></i><span class="nav-label">Cursos</span></a>
    </li>
    <li class="nav-item active" onclick="navTo('page-metas', this)">
      <a><i class="ti ti-target"></i><span class="nav-label">Metas</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard',this)">
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

  <!-- ===== PAGE: GRÁFICOS ===== -->
  <div class="page" id="page-graficos">
    <div class="page-header">
      <h2>Meus <span>Gráficos</span></h2>
      <button class="btn-primary" onclick="GraficosModule.abrirModal()">
        <i class="ti ti-plus"></i> Novo gráfico
      </button>
    </div>
    <div class="graficos-grid" id="graficos-grid"></div>
  </div>

  <!-- ===== PAGE: CONTA & PERFIL ===== -->
  <div class="page" id="page-conta">

    <div class="page-header">
      <h2>Minha <span>Conta</span></h2>
    </div>

    <div class="tabs" id="conta-tabs">
      <button class="tab-btn active" onclick="switchTab('conta-tab', this)">
        <i class="ti ti-wallet"></i> Conta
      </button>
      <button class="tab-btn" onclick="switchTab('perfil-tab', this)">
        <i class="ti ti-user-edit"></i> Editar Perfil
      </button>
    </div>

    <!-- ── TAB: CONTA ── -->
    <div class="tab-panel active" id="conta-tab">
      <div class="conta-grid">

        <div class="saldo-hero">
          <div>
            <div class="saldo-hero-label">Saldo disponível</div>
            <div class="saldo-hero-value">R$ 12.480,00</div>
            <div class="saldo-hero-sub">↑ +3,2% este mês</div>
          </div>
          <div class="saldo-icon"><i class="ti ti-coins"></i></div>
        </div>

        <div class="info-card">
          <div class="info-card-label">Tipo de plano</div>
          <div class="info-card-value gold">Premium
            <span class="meta-badge badge-concluida" style="font-size:.72rem;margin-top:0;margin-left:6px;vertical-align:middle">
              <i class="ti ti-star"></i> Ativo
            </span>
          </div>
        </div>

        <div class="info-card">
          <div class="info-card-label">Status da conta</div>
          <div class="info-card-value">
            <span class="meta-badge badge-concluida" style="font-size:.72rem;margin-top:0">
              <i class="ti ti-circle-check"></i> Verificada
            </span>
          </div>
        </div>

        <div class="info-card">
          <div class="info-card-label">Meta mensal</div>
          <div class="info-card-value sm">R$ 7.800 <small>/ R$ 10.000</small></div>
          <div class="progress-bar-track">
            <div class="progress-bar-fill" style="width:78%"></div>
          </div>
          <div class="progress-label">78% concluído</div>
        </div>

        <div class="info-card">
          <div class="info-card-label">Gastos este mês</div>
          <div class="info-card-value red">R$ 3.210,00</div>
          <div class="info-card-sub">↓ -1,5% vs mês anterior</div>
        </div>

        <div class="info-card">
          <div class="info-card-label">Membro desde</div>
          <div class="info-card-value sm">Janeiro 2024</div>
        </div>

        <div class="info-card">
          <div class="info-card-label">Último acesso</div>
          <div class="info-card-value sm">Hoje, <?= date('H:i') ?></div>
        </div>

        <div class="info-card wide">
          <div class="info-card-label section-label">Atividade recente</div>
          <div class="activity-list">
            <div class="activity-row">
              <div class="act-icon green"><i class="ti ti-arrow-down-left"></i></div>
              <div class="act-info">
                <div class="act-title">Depósito recebido</div>
                <div class="act-date">Hoje, 08:15</div>
              </div>
              <div class="act-value green">+R$ 2.500</div>
            </div>
            <div class="activity-row">
              <div class="act-icon red"><i class="ti ti-arrow-up-right"></i></div>
              <div class="act-info">
                <div class="act-title">Pagamento de conta</div>
                <div class="act-date">Ontem, 19:30</div>
              </div>
              <div class="act-value red">-R$ 480</div>
            </div>
            <div class="activity-row">
              <div class="act-icon gold"><i class="ti ti-target"></i></div>
              <div class="act-info">
                <div class="act-title">Meta "Viagem Europa" atualizada</div>
                <div class="act-date">Seg, 14:05</div>
              </div>
              <div class="act-value gold">+R$ 800</div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── TAB: EDITAR PERFIL ── -->
    <div class="tab-panel" id="perfil-tab">

      <?php if ($msgPerfil): ?>
        <div class="alert alert-success"><i class="ti ti-circle-check"></i> <?= $msgPerfil ?></div>
      <?php endif; ?>
      <?php if ($erroPerfil): ?>
        <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= $erroPerfil ?></div>
      <?php endif; ?>

      <form method="POST" action="index.php#page-conta">
        <input type="hidden" name="acao" value="salvar_perfil">

        <div class="perfil-layout">

          <div class="avatar-editor">
            <div class="avatar-large">
              <i class="ti ti-user-circle"></i>
              <div class="avatar-overlay"><i class="ti ti-camera"></i></div>
            </div>
            <div class="avatar-hint">Clique para alterar<br><a>Remover foto</a></div>
          </div>

          <div>
            <div class="perfil-form-grid">

              <div class="form-group">
                <label class="form-label">Nome</label>
                <div class="input-icon-wrap">
                  <i class="ti ti-user"></i>
                  <input class="form-input" id="f-nome" name="nome" type="text"
                         value="<?= htmlspecialchars($u['nome']) ?>" required oninput="previewNome()">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Sobrenome</label>
                <div class="input-icon-wrap">
                  <i class="ti ti-user"></i>
                  <input class="form-input" id="f-sobrenome" name="sobrenome" type="text"
                         value="<?= htmlspecialchars($u['sobrenome']) ?>" oninput="previewNome()">
                </div>
              </div>

              <div class="form-group full">
                <label class="form-label">E-mail</label>
                <div class="input-icon-wrap">
                  <i class="ti ti-mail"></i>
                  <input class="form-input" id="f-email" name="email" type="email"
                         value="<?= htmlspecialchars($u['email']) ?>" required>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Telefone</label>
                <div class="input-icon-wrap">
                  <i class="ti ti-phone"></i>
                  <input class="form-input" name="telefone" type="tel"
                         placeholder="(11) 9xxxx-xxxx"
                         value="<?= htmlspecialchars($u['telefone']) ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">CPF</label>
                <div class="input-icon-wrap">
                  <i class="ti ti-id-badge"></i>
                  <input class="form-input" type="text" value="•••.•••.•••-••"
                         readonly style="cursor:not-allowed;opacity:.5">
                </div>
              </div>

              <div class="form-group full">
                <label class="form-label">Nova senha</label>
                <div class="input-icon-wrap password-wrap">
                  <i class="ti ti-lock"></i>
                  <input class="form-input" id="f-senha" name="nova_senha" type="password"
                         placeholder="Deixe em branco para não alterar">
                  <button type="button" class="eye-btn" onclick="togglePwd('f-senha','eye1')">
                    <i class="ti ti-eye" id="eye1"></i>
                  </button>
                </div>
              </div>

              <div class="form-group full">
                <label class="form-label">Confirmar senha</label>
                <div class="input-icon-wrap password-wrap">
                  <i class="ti ti-lock-check"></i>
                  <input class="form-input" id="f-conf" name="confirma_senha" type="password"
                         placeholder="Repita a nova senha">
                  <button type="button" class="eye-btn" onclick="togglePwd('f-conf','eye2')">
                    <i class="ti ti-eye" id="eye2"></i>
                  </button>
                </div>
              </div>

              <div class="form-group full">
                <label class="form-label">Biografia</label>
                <textarea class="form-input" name="bio" rows="3"
                          placeholder="Conte algo sobre você..."><?= htmlspecialchars($u['bio']) ?></textarea>
              </div>

            </div>

            <div class="save-row">
              <button type="button" class="btn-cancel"
                      onclick="switchTab('conta-tab', document.querySelector('#conta-tabs .tab-btn'))">
                Cancelar
              </button>
              <button type="submit" class="btn-save">
                <i class="ti ti-device-floppy"></i> Salvar alterações
              </button>
            </div>

            <div class="danger-zone">
              <h3><i class="ti ti-alert-triangle"></i> Zona de perigo</h3>
              <p>Ao excluir sua conta, todos os dados serão permanentemente removidos. Esta ação não pode ser desfeita.</p>
              <button type="button" class="btn-danger"
                      onclick="showToast('Funcionalidade restrita', 'ti-alert-triangle')">
                <i class="ti ti-trash"></i> Excluir minha conta
              </button>
            </div>

          </div>
        </div>
      </form>
    </div>

  </div><!-- /page-conta -->

  <!-- ===== PAGE: CONFIGURAÇÕES ===== -->
  <div class="page" id="page-config">

    <div class="page-header">
      <h2>Configurações <span>do sistema</span></h2>
    </div>

    <?php if ($msgConfig): ?>
      <div class="alert alert-success"><i class="ti ti-circle-check"></i> <?= $msgConfig ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php#page-config" id="form-config">
      <input type="hidden" name="acao" value="salvar_config">
      <input type="hidden" name="tema" id="input-tema" value="<?= htmlspecialchars($cfg['tema']) ?>">

      <!-- ── Aparência ── -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-palette"></i></div>
        <div class="section-hd-title">Aparência</div>
      </div>
      <div class="config-card">

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon gold"><i class="ti ti-sun-moon"></i></div>
            <div>
              <div class="config-row-title">Tema</div>
              <div class="config-row-desc">Modo escuro ou claro (visual apenas)</div>
            </div>
          </div>
          <div class="theme-pill">
            <button type="button" class="theme-option <?= $cfg['tema']==='dark'  ? 'active':'' ?>"
                    id="opt-dark"  onclick="setTheme('dark')">
              <i class="ti ti-moon"></i> Escuro
            </button>
            <button type="button" class="theme-option <?= $cfg['tema']==='light' ? 'active':'' ?>"
                    id="opt-light" onclick="setTheme('light')">
              <i class="ti ti-sun"></i> Claro
            </button>
          </div>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-typography"></i></div>
            <div>
              <div class="config-row-title">Tamanho da fonte</div>
              <div class="config-row-desc">Ajuste o tamanho base do texto</div>
            </div>
          </div>
          <select class="cfg-select" name="fonte">
            <option value="pequeno" <?= sel($cfg['fonte'],'pequeno') ?>>Pequeno</option>
            <option value="medio"   <?= sel($cfg['fonte'],'medio')   ?>>Médio</option>
            <option value="grande"  <?= sel($cfg['fonte'],'grande')  ?>>Grande</option>
          </select>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-sparkles"></i></div>
            <div>
              <div class="config-row-title">Animações de interface</div>
              <div class="config-row-desc">Transições e efeitos visuais</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="animacoes" <?= chk($cfg['animacoes']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

      </div>

      <!-- ── Notificações ── -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-bell"></i></div>
        <div class="section-hd-title">Notificações</div>
      </div>
      <div class="config-card">

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-bell-ringing"></i></div>
            <div>
              <div class="config-row-title">Alertas de metas</div>
              <div class="config-row-desc">Notificar quando uma meta for atingida</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="alerta_metas" <?= chk($cfg['alerta_metas']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-chart-line"></i></div>
            <div>
              <div class="config-row-title">Resumo semanal</div>
              <div class="config-row-desc">Relatório financeiro toda segunda-feira</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="resumo_semanal" <?= chk($cfg['resumo_semanal']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-alert-circle"></i></div>
            <div>
              <div class="config-row-title">Gastos acima do limite</div>
              <div class="config-row-desc">Avisar quando os gastos ultrapassarem o planejado</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="alerta_gastos" <?= chk($cfg['alerta_gastos']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-mail"></i></div>
            <div>
              <div class="config-row-title">Notificações por e-mail</div>
              <div class="config-row-desc">Receber cópia dos alertas no e-mail cadastrado</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="email_notif" <?= chk($cfg['email_notif']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

      </div>

      <!-- ── Privacidade ── -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-shield-lock"></i></div>
        <div class="section-hd-title">Privacidade & Segurança</div>
      </div>
      <div class="config-card">

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon gold"><i class="ti ti-fingerprint"></i></div>
            <div>
              <div class="config-row-title">Autenticação de dois fatores</div>
              <div class="config-row-desc">Camada extra de segurança ao login</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="2fa" <?= chk($cfg['2fa']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-eye-off"></i></div>
            <div>
              <div class="config-row-title">Ocultar saldos por padrão</div>
              <div class="config-row-desc">Esconder valores ao abrir o aplicativo</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="ocultar_saldo" <?= chk($cfg['ocultar_saldo']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-clock"></i></div>
            <div>
              <div class="config-row-title">Tempo de sessão</div>
              <div class="config-row-desc">Encerrar automaticamente após inatividade</div>
            </div>
          </div>
          <select class="cfg-select" name="sessao">
            <option value="15min" <?= sel($cfg['sessao'],'15min') ?>>15 minutos</option>
            <option value="30min" <?= sel($cfg['sessao'],'30min') ?>>30 minutos</option>
            <option value="1hora" <?= sel($cfg['sessao'],'1hora') ?>>1 hora</option>
            <option value="nunca" <?= sel($cfg['sessao'],'nunca') ?>>Nunca</option>
          </select>
        </div>

      </div>

      <!-- ── Dados ── -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-database"></i></div>
        <div class="section-hd-title">Dados & Exportação</div>
      </div>
      <div class="config-card">

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-file-spreadsheet"></i></div>
            <div>
              <div class="config-row-title">Exportar dados financeiros</div>
              <div class="config-row-desc">Baixe um relatório completo da conta</div>
            </div>
          </div>
          <div class="export-btns">
            <button type="button" class="btn-outline"
                    onclick="showToast('Gerando CSV...','ti-file-spreadsheet')">
              <i class="ti ti-file-spreadsheet"></i> CSV
            </button>
            <button type="button" class="btn-outline"
                    onclick="showToast('Gerando PDF...','ti-file-text')">
              <i class="ti ti-file-text"></i> PDF
            </button>
          </div>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-refresh"></i></div>
            <div>
              <div class="config-row-title">Sincronização automática</div>
              <div class="config-row-desc">Atualizar dados em segundo plano</div>
            </div>
          </div>
          <label class="toggle">
            <input type="checkbox" name="sync_auto" <?= chk($cfg['sync_auto']) ?>>
            <div class="toggle-track"></div>
          </label>
        </div>

        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-trash"></i></div>
            <div>
              <div class="config-row-title">Limpar cache local</div>
              <div class="config-row-desc">Remove dados temporários armazenados</div>
            </div>
          </div>
          <button type="button" class="btn-outline btn-danger-outline"
                  onclick="showToast('Cache limpo!','ti-trash')">
            <i class="ti ti-trash"></i> Limpar
          </button>
        </div>

      </div>

      <!-- ── Sobre ── -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-info-circle"></i></div>
        <div class="section-hd-title">Sobre o sistema</div>
      </div>
      <div class="config-card">
        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon gold"><i class="ti ti-brand-appgallery"></i></div>
            <div>
              <div class="config-row-title">METAL Financeiro</div>
              <div class="config-row-desc">Sistema de gestão financeira pessoal</div>
            </div>
          </div>
          <span class="version-badge"><i class="ti ti-tag"></i> v1.0.0</span>
        </div>
        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-refresh-alert"></i></div>
            <div>
              <div class="config-row-title">Verificar atualizações</div>
              <div class="config-row-desc">Última verificação: <?= date('d/m/Y') ?></div>
            </div>
          </div>
          <button type="button" class="btn-outline"
                  onclick="showToast('Sistema atualizado!','ti-check')">
            <i class="ti ti-refresh"></i> Verificar
          </button>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-cancel"
                onclick="showToast('Alterações descartadas','ti-x')">Cancelar</button>
        <button type="submit" class="btn-save">
          <i class="ti ti-device-floppy"></i> Salvar configurações
        </button>
      </div>

    </form>
  </div><!-- /page-config -->

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

<!-- ===================== MODAL: NOVO GRÁFICO ===================== -->
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
          <button type="button" class="tipo-btn selected" data-tipo="bar"
                  onclick="GraficosModule.selecionarTipo('bar')">
            <i class="ti ti-chart-bar"></i> Barras
          </button>
          <button type="button" class="tipo-btn" data-tipo="line"
                  onclick="GraficosModule.selecionarTipo('line')">
            <i class="ti ti-chart-line"></i> Linha
          </button>
          <button type="button" class="tipo-btn" data-tipo="doughnut"
                  onclick="GraficosModule.selecionarTipo('doughnut')">
            <i class="ti ti-chart-donut"></i> Rosca
          </button>
          <button type="button" class="tipo-btn" data-tipo="pie"
                  onclick="GraficosModule.selecionarTipo('pie')">
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

<!-- mensagens PHP → toast JS -->
<?php if ($msgPerfil): ?>
<script>
  document.addEventListener('DOMContentLoaded', () =>
    showToast('<?= addslashes($msgPerfil) ?>', 'ti-circle-check'));
</script>
<?php elseif ($erroPerfil): ?>
<script>
  document.addEventListener('DOMContentLoaded', () =>
    showToast('<?= addslashes($erroPerfil) ?>', 'ti-alert-circle'));
</script>
<?php endif; ?>
<?php if ($msgConfig): ?>
<script>
  document.addEventListener('DOMContentLoaded', () =>
    showToast('<?= addslashes($msgConfig) ?>', 'ti-circle-check'));
</script>
<?php endif; ?>

</body>
</html>

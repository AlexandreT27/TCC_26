<?php
/* ================================================================
   index.php  –  METAL Financeiro  |  Painel principal
   ================================================================ */
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure'   => false, 'httponly' => true, 'samesite' => 'Strict',
]);
require 'db.php';

session_start();
requireAuth();

/* ── Carrega dados frescos do usuário ────────────────────────────*/
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
if (!$u) { header('Location: logout.php'); exit(); }

/* ── Sincroniza session ──────────────────────────────────────────*/
$_SESSION['user_nome']     = $u['nome'];
$_SESSION['user_sobrenome']= $u['sobrenome'];
$_SESSION['user_avatar']   = $u['avatar'];
$_SESSION['user_tema']     = $u['tema'];
$_SESSION['user_perfil']   = $u['perfil'];

$nomeCompleto = $u['nome'] . ' ' . $u['sobrenome'];
$tema         = $u['tema'];
$avatarUrl    = $u['avatar'] ? UPLOAD_URL . htmlspecialchars($u['avatar']) : '';
$isAdmin      = ($u['perfil'] === 'admin');

/* ── Metas do usuário ────────────────────────────────────────────*/
$mStmt = $pdo->prepare('SELECT * FROM metas WHERE usuario_id = ? ORDER BY criado_em DESC');
$mStmt->execute([$u['id']]);
$metasDB = $mStmt->fetchAll();

/* ── Mensagens de feedback ───────────────────────────────────────*/
$msgPerfil  = '';  $erroPerfil = '';
$msgConfig  = '';
$msgAvatar  = '';  $erroAvatar = '';

/* ── POST: salvar perfil ─────────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_perfil') {
    csrfVerify();
    $nome      = trim(htmlspecialchars($_POST['nome']      ?? ''));
    $sobrenome = trim(htmlspecialchars($_POST['sobrenome'] ?? ''));
    $email     = trim($_POST['email'] ?? '');
    $telefone  = trim(htmlspecialchars($_POST['telefone']  ?? ''));
    $bio       = trim(htmlspecialchars($_POST['bio']       ?? ''));
    $novaSenha = $_POST['nova_senha']     ?? '';
    $confSenha = $_POST['confirma_senha'] ?? '';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroPerfil = 'Nome e e-mail válido são obrigatórios.';
    } elseif ($novaSenha !== '' && strlen($novaSenha) < 8) {
        $erroPerfil = 'Nova senha deve ter ao menos 8 caracteres.';
    } elseif ($novaSenha !== '' && $novaSenha !== $confSenha) {
        $erroPerfil = 'As senhas não coincidem.';
    } else {
        // E-mail duplicado (de outro usuário)
        $chk = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1');
        $chk->execute([$email, $u['id']]);
        if ($chk->fetch()) {
            $erroPerfil = 'Este e-mail já está em uso por outra conta.';
        } else {
            if ($novaSenha !== '') {
                $hash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE usuarios SET nome=?,sobrenome=?,email=?,telefone=?,bio=?,senha_hash=? WHERE id=?')
                    ->execute([$nome,$sobrenome,$email,$telefone,$bio,$hash,$u['id']]);
            } else {
                $pdo->prepare('UPDATE usuarios SET nome=?,sobrenome=?,email=?,telefone=?,bio=? WHERE id=?')
                    ->execute([$nome,$sobrenome,$email,$telefone,$bio,$u['id']]);
            }
            $msgPerfil = 'Perfil atualizado com sucesso!';
            // Recarrega dados
            $stmt->execute([$u['id']]);
            $u = $stmt->fetch();
            $nomeCompleto = $u['nome'] . ' ' . $u['sobrenome'];
            $_SESSION['user_nome'] = $u['nome'];
            $_SESSION['user_sobrenome'] = $u['sobrenome'];
        }
    }
}

/* ── POST: upload de avatar ──────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'upload_avatar') {
    csrfVerify();
    if (!empty($_FILES['avatar']['name'])) {
        $res = validarUploadAvatar($_FILES['avatar']);
        if ($res['ok']) {
            // Remove avatar antigo
            if ($u['avatar'] && file_exists(UPLOAD_DIR . $u['avatar'])) {
                @unlink(UPLOAD_DIR . $u['avatar']);
            }
            $pdo->prepare('UPDATE usuarios SET avatar = ? WHERE id = ?')
                ->execute([$res['filename'], $u['id']]);
            $_SESSION['user_avatar'] = $res['filename'];
            $avatarUrl = UPLOAD_URL . $res['filename'];
            $u['avatar'] = $res['filename'];
            $msgAvatar = 'Foto de perfil atualizada!';
        } else {
            $erroAvatar = $res['msg'];
        }
    }
}

/* ── POST: remover avatar ────────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'remover_avatar') {
    csrfVerify();
    if ($u['avatar'] && file_exists(UPLOAD_DIR . $u['avatar'])) {
        @unlink(UPLOAD_DIR . $u['avatar']);
    }
    $pdo->prepare('UPDATE usuarios SET avatar = "" WHERE id = ?')->execute([$u['id']]);
    $_SESSION['user_avatar'] = '';
    $avatarUrl = '';
    $u['avatar'] = '';
    $msgAvatar = 'Foto removida.';
}

/* ── POST: salvar configurações ──────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_config') {
    csrfVerify();
    $novoTema = in_array($_POST['tema'] ?? '', ['dark','light']) ? $_POST['tema'] : 'dark';
    $pdo->prepare('
        UPDATE usuarios SET
            tema=?,fonte=?,cfg_animacoes=?,cfg_alerta_metas=?,
            cfg_resumo_semanal=?,cfg_alerta_gastos=?,cfg_email_notif=?,
            cfg_2fa=?,cfg_ocultar_saldo=?,cfg_sessao=?,cfg_sync_auto=?
        WHERE id=?
    ')->execute([
        $novoTema,
        htmlspecialchars($_POST['fonte']  ?? 'medio'),
        isset($_POST['animacoes'])      ? 1 : 0,
        isset($_POST['alerta_metas'])   ? 1 : 0,
        isset($_POST['resumo_semanal']) ? 1 : 0,
        isset($_POST['alerta_gastos'])  ? 1 : 0,
        isset($_POST['email_notif'])    ? 1 : 0,
        isset($_POST['2fa'])            ? 1 : 0,
        isset($_POST['ocultar_saldo'])  ? 1 : 0,
        htmlspecialchars($_POST['sessao'] ?? '30min'),
        isset($_POST['sync_auto'])      ? 1 : 0,
        $u['id'],
    ]);
    $_SESSION['user_tema'] = $novoTema;
    $tema = $novoTema;
    $msgConfig = 'Configurações salvas!';
    $stmt->execute([$u['id']]);
    $u = $stmt->fetch();
}

/* ── POST: metas (CRUD) ───────────────────────*/
$msgMeta = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_meta') {
    csrfVerify();
    $mNome   = trim(htmlspecialchars($_POST['meta_nome']     ?? ''));
    $mCat    = htmlspecialchars($_POST['meta_categoria']     ?? 'outro');
    $mTotal  = (float)($_POST['meta_total'] ?? 0);
    $mAtual  = (float)($_POST['meta_atual'] ?? 0);
    $mPrazo  = htmlspecialchars($_POST['meta_prazo']         ?? '');
    $mStatus = htmlspecialchars($_POST['meta_status']        ?? 'andamento');
    $mId     = (int)($_POST['meta_id'] ?? 0);

    if ($mNome && $mTotal > 0 && $mPrazo) {
        if ($mId) {
            $pdo->prepare('UPDATE metas SET nome=?,categoria=?,valor_total=?,valor_atual=?,prazo=?,status=? WHERE id=? AND usuario_id=?')
                ->execute([$mNome,$mCat,$mTotal,$mAtual,$mPrazo,$mStatus,$mId,$u['id']]);
            $msgMeta = 'Meta atualizada!';
        } else {
            $pdo->prepare('INSERT INTO metas (usuario_id,nome,categoria,valor_total,valor_atual,prazo,status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$u['id'],$mNome,$mCat,$mTotal,$mAtual,$mPrazo,$mStatus]);
            $msgMeta = 'Meta criada!';
        }
        // Recarrega metas
        $mStmt->execute([$u['id']]);
        $metasDB = $mStmt->fetchAll();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_meta') {
    csrfVerify();
    $mId = (int)($_POST['meta_id'] ?? 0);
    $pdo->prepare('DELETE FROM metas WHERE id = ? AND usuario_id = ?')->execute([$mId, $u['id']]);
    $mStmt->execute([$u['id']]);
    $metasDB = $mStmt->fetchAll();
    $msgMeta = 'Meta excluída.';
}

/* ── Helpers ─────────────────────────────────────────────────────*/
function chk($v): string  { return $v ? 'checked' : ''; }
function sel($a,$b): string { return $a === $b ? 'selected' : ''; }

$csrf = csrfToken();

/* ── Página inicial após POST ────────────────────────────────────*/
$paginaInicial = 'page-conta';
if ($msgPerfil || $erroPerfil || $msgAvatar || $erroAvatar) $paginaInicial = 'page-conta';
if ($msgConfig)  $paginaInicial = 'page-config';
if ($msgMeta)    $paginaInicial = 'page-conta';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= $tema ?>">
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
    <?php if ($avatarUrl): ?>
      <div class="avatar-placeholder avatar-img">
        <img src="<?= $avatarUrl ?>" alt="Foto de perfil">
      </div>
    <?php else: ?>
      <div class="avatar-placeholder"><i class="ti ti-user-circle"></i></div>
    <?php endif; ?>
    <div class="username" id="sidebar-username"><?= htmlspecialchars($nomeCompleto) ?></div>
    <?php if ($isAdmin): ?>
      <div class="admin-badge"><i class="ti ti-shield-filled"></i> Administrador</div>
    <?php else: ?>
      <div class="user-email-sb"><?= htmlspecialchars($u['email']) ?></div>
    <?php endif; ?>
  </div>

  <ul class="nav-menu">
    <li class="nav-item" onclick="navTo('page-conta',this)">
      <a><i class="ti ti-wallet"></i><span class="nav-label">Conta</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-conta',this,'perfil')">
      <a><i class="ti ti-user"></i><span class="nav-label">Perfil</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-config',this)">
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
    <li class="nav-item active" onclick="navTo('page-metas',this)">
      <a><i class="ti ti-target"></i><span class="nav-label">Metas</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard',this)">
      <a><i class="ti ti-switch-horizontal"></i><span class="nav-label">Mudar de conta</span></a>
    </li>
  </ul>

  <a href="logout.php" class="btn-sair">
    <i class="ti ti-logout"></i>
    <span class="btn-sair-label">Sair</span>
  </a>
</aside>

<!-- ===================== MAIN ===================== -->
<main class="main-content">

  <div class="header-top">
    <div class="logo">METAL<span>Financeiro</span></div>
    <div class="header-icons">
      <button class="icon-btn" onclick="showToast('Sem novas notificações','ti-bell')" title="Notificações">
        <i class="ti ti-bell"></i><span class="notif-dot"></span>
      </button>
      <button class="icon-btn" onclick="showToast('Central de ajuda','ti-help-circle')" title="Ajuda">
        <i class="ti ti-help-circle"></i>
      </button>
    </div>
  </div>

  <!-- ===== DASHBOARD ===== -->
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
        <div class="card" onclick="navTo('page-metas',document.querySelector('.nav-item:nth-child(7)'))">
          <i class="ti ti-target"></i><span>Metas</span>
        </div>
      </div>
      <div class="cards-row-bottom">
        <div class="card" onclick="showToast('Tutoriais em breve!','ti-video')">
          <i class="ti ti-video"></i><span>Tutoriais</span>
        </div>
        <div class="card" onclick="showToast('Calculadora em breve!','ti-calculator')">
          <i class="ti ti-calculator"></i><span>Cálculos</span>
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

  <!-- ===== METAS ===== -->
  <div class="page active" id="page-metas">
    <div class="page-header">
      <h2>Minhas <span>Metas</span></h2>
      <button class="btn-primary" onclick="MetasModule.abrirModal()">
        <i class="ti ti-plus"></i> Nova meta
      </button>
    </div>
    <?php if ($msgMeta): ?>
      <div class="alert alert-success" style="margin-bottom:16px">
        <i class="ti ti-circle-check"></i> <?= htmlspecialchars($msgMeta) ?>
      </div>
    <?php endif; ?>
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

  <!-- ===== GRÁFICOS ===== -->
  <div class="page" id="page-graficos">
    <div class="page-header">
      <h2>Meus <span>Gráficos</span></h2>
      <button class="btn-primary" onclick="GraficosModule.abrirModal()">
        <i class="ti ti-plus"></i> Novo gráfico
      </button>
    </div>
    <div class="graficos-grid" id="graficos-grid"></div>
  </div>

  <!-- ===== CONTA & PERFIL ===== -->
  <div class="page" id="page-conta">
    <div class="page-header"><h2>Minha <span>Conta</span></h2></div>

    <div class="tabs" id="conta-tabs">
      <button class="tab-btn active" onclick="switchTab('conta-tab',this)">
        <i class="ti ti-wallet"></i> Conta
      </button>
      <button class="tab-btn" onclick="switchTab('perfil-tab',this)">
        <i class="ti ti-user-edit"></i> Editar Perfil
      </button>
    </div>

    <!-- TAB CONTA -->
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
            <span class="meta-badge badge-concluida" style="font-size:.72rem;margin:0 0 0 6px;vertical-align:middle">
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
          <div class="progress-bar-track"><div class="progress-bar-fill" style="width:78%"></div></div>
          <div class="progress-label">78% concluído</div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Gastos este mês</div>
          <div class="info-card-value red">R$ 3.210,00</div>
          <div class="info-card-sub">↓ -1,5% vs mês anterior</div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Membro desde</div>
          <div class="info-card-value sm"><?= date('d/m/Y', strtotime($u['criado_em'])) ?></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Último acesso</div>
          <div class="info-card-value sm">
            <?= $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : '—' ?>
          </div>
        </div>
        <div class="info-card wide">
          <div class="info-card-label section-label">Atividade recente</div>
          <div class="activity-list">
            <div class="activity-row">
              <div class="act-icon green"><i class="ti ti-arrow-down-left"></i></div>
              <div class="act-info"><div class="act-title">Depósito recebido</div><div class="act-date">Hoje, 08:15</div></div>
              <div class="act-value green">+R$ 2.500</div>
            </div>
            <div class="activity-row">
              <div class="act-icon red"><i class="ti ti-arrow-up-right"></i></div>
              <div class="act-info"><div class="act-title">Pagamento de conta</div><div class="act-date">Ontem, 19:30</div></div>
              <div class="act-value red">-R$ 480</div>
            </div>
            <div class="activity-row">
              <div class="act-icon gold"><i class="ti ti-target"></i></div>
              <div class="act-info"><div class="act-title">Meta "Viagem Europa" atualizada</div><div class="act-date">Seg, 14:05</div></div>
              <div class="act-value gold">+R$ 800</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB EDITAR PERFIL -->
    <div class="tab-panel" id="perfil-tab">

      <?php if ($msgAvatar):  ?><div class="alert alert-success" style="margin-bottom:16px"><i class="ti ti-circle-check"></i> <?= htmlspecialchars($msgAvatar) ?></div><?php endif; ?>
      <?php if ($erroAvatar): ?><div class="alert alert-error"   style="margin-bottom:16px"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erroAvatar) ?></div><?php endif; ?>
      <?php if ($msgPerfil):  ?><div class="alert alert-success" style="margin-bottom:16px"><i class="ti ti-circle-check"></i> <?= htmlspecialchars($msgPerfil) ?></div><?php endif; ?>
      <?php if ($erroPerfil): ?><div class="alert alert-error"   style="margin-bottom:16px"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erroPerfil) ?></div><?php endif; ?>

      <!-- ── Upload de avatar ── -->
      <div class="avatar-upload-section">
        <div class="avatar-upload-ring" id="avatar-preview-wrap" onclick="document.getElementById('file-avatar').click()">
          <?php if ($avatarUrl): ?>
            <img src="<?= $avatarUrl ?>" alt="Avatar" id="avatar-preview-img">
          <?php else: ?>
            <i class="ti ti-user-circle" id="avatar-preview-icon"></i>
          <?php endif; ?>
          <div class="avatar-upload-overlay"><i class="ti ti-camera"></i><span>Alterar</span></div>
        </div>

        <div class="avatar-upload-actions">
          <!-- Upload -->
          <form method="POST" enctype="multipart/form-data" id="form-avatar-upload">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="acao" value="upload_avatar">
            <input type="file" id="file-avatar" name="avatar"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   style="display:none" onchange="previewAvatar(this)">
            <button type="submit" class="btn-save" id="btn-salvar-avatar" style="display:none">
              <i class="ti ti-upload"></i> Salvar foto
            </button>
          </form>
          <!-- Remover -->
          <?php if ($avatarUrl): ?>
          <form method="POST" id="form-avatar-remove" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="acao" value="remover_avatar">
            <button type="submit" class="btn-danger" onclick="return confirm('Remover a foto de perfil?')">
              <i class="ti ti-trash"></i> Remover foto
            </button>
          </form>
          <?php endif; ?>
          <p class="avatar-hint">JPG, PNG, WEBP ou GIF · máx 2 MB</p>
        </div>
      </div>

      <!-- ── Dados pessoais ── -->
      <form method="POST" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="acao" value="salvar_perfil">

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
              <input class="form-input" name="email" type="email"
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
                     readonly style="opacity:.5;cursor:not-allowed">
            </div>
          </div>

          <div class="form-group full">
            <label class="form-label">Nova senha <small style="font-size:.7rem;text-transform:none">(deixe em branco para não alterar)</small></label>
            <div class="input-icon-wrap password-wrap">
              <i class="ti ti-lock"></i>
              <input class="form-input" id="f-senha" name="nova_senha" type="password"
                     placeholder="Mín. 8 caracteres">
              <button type="button" class="eye-btn" onclick="togglePwd('f-senha','eye1')">
                <i class="ti ti-eye" id="eye1"></i>
              </button>
            </div>
          </div>

          <div class="form-group full">
            <label class="form-label">Confirmar nova senha</label>
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
                  onclick="switchTab('conta-tab',document.querySelector('#conta-tabs .tab-btn'))">
            Cancelar
          </button>
          <button type="submit" class="btn-save">
            <i class="ti ti-device-floppy"></i> Salvar alterações
          </button>
        </div>

        <div class="danger-zone">
          <h3><i class="ti ti-alert-triangle"></i> Zona de perigo</h3>
          <p>Ao excluir sua conta, todos os dados serão permanentemente removidos.</p>
          <button type="button" class="btn-danger"
                  onclick="showToast('Entre em contato com o suporte para excluir sua conta.','ti-alert-triangle')">
            <i class="ti ti-trash"></i> Excluir minha conta
          </button>
        </div>

      </form>
    </div><!-- /perfil-tab -->
  </div><!-- /page-conta -->

  <!-- ===== CONFIGURAÇÕES ===== -->
  <div class="page" id="page-config">
    <div class="page-header"><h2>Configurações <span>do sistema</span></h2></div>

    <?php if ($msgConfig): ?>
      <div class="alert alert-success" style="margin-bottom:20px">
        <i class="ti ti-circle-check"></i> <?= htmlspecialchars($msgConfig) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php" id="form-config">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="acao"  value="salvar_config">
      <input type="hidden" name="tema"  id="input-tema" value="<?= $tema ?>">

      <!-- Aparência -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-palette"></i></div>
        <div class="section-hd-title">Aparência</div>
      </div>
      <div class="config-card">
        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon gold"><i class="ti ti-sun-moon"></i></div>
            <div><div class="config-row-title">Tema</div><div class="config-row-desc">Modo escuro ou claro</div></div>
          </div>
          <div class="theme-pill">
            <button type="button" class="theme-option <?= $tema==='dark'  ?'active':'' ?>" id="opt-dark"  onclick="setTheme('dark')"><i class="ti ti-moon"></i> Escuro</button>
            <button type="button" class="theme-option <?= $tema==='light' ?'active':'' ?>" id="opt-light" onclick="setTheme('light')"><i class="ti ti-sun"></i> Claro</button>
          </div>
        </div>
        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-typography"></i></div>
            <div><div class="config-row-title">Tamanho da fonte</div></div>
          </div>
          <select class="cfg-select" name="fonte">
            <option value="pequeno" <?= sel($u['fonte'],'pequeno') ?>>Pequeno</option>
            <option value="medio"   <?= sel($u['fonte'],'medio')   ?>>Médio</option>
            <option value="grande"  <?= sel($u['fonte'],'grande')  ?>>Grande</option>
          </select>
        </div>
        <div class="config-row">
          <div class="config-row-left">
            <div class="config-row-icon"><i class="ti ti-sparkles"></i></div>
            <div><div class="config-row-title">Animações</div></div>
          </div>
          <label class="toggle"><input type="checkbox" name="animacoes" <?= chk($u['cfg_animacoes']) ?>><div class="toggle-track"></div></label>
        </div>
      </div>

      <!-- Notificações -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-bell"></i></div>
        <div class="section-hd-title">Notificações</div>
      </div>
      <div class="config-card">
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-bell-ringing"></i></div><div><div class="config-row-title">Alertas de metas</div></div></div>
          <label class="toggle"><input type="checkbox" name="alerta_metas" <?= chk($u['cfg_alerta_metas']) ?>><div class="toggle-track"></div></label>
        </div>
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-chart-line"></i></div><div><div class="config-row-title">Resumo semanal</div></div></div>
          <label class="toggle"><input type="checkbox" name="resumo_semanal" <?= chk($u['cfg_resumo_semanal']) ?>><div class="toggle-track"></div></label>
        </div>
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-alert-circle"></i></div><div><div class="config-row-title">Gastos acima do limite</div></div></div>
          <label class="toggle"><input type="checkbox" name="alerta_gastos" <?= chk($u['cfg_alerta_gastos']) ?>><div class="toggle-track"></div></label>
        </div>
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-mail"></i></div><div><div class="config-row-title">E-mail de notificações</div></div></div>
          <label class="toggle"><input type="checkbox" name="email_notif" <?= chk($u['cfg_email_notif']) ?>><div class="toggle-track"></div></label>
        </div>
      </div>

      <!-- Privacidade -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-shield-lock"></i></div>
        <div class="section-hd-title">Privacidade & Segurança</div>
      </div>
      <div class="config-card">
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon gold"><i class="ti ti-fingerprint"></i></div><div><div class="config-row-title">Autenticação de dois fatores</div><div class="config-row-desc">Camada extra de segurança</div></div></div>
          <label class="toggle"><input type="checkbox" name="2fa" <?= chk($u['cfg_2fa']) ?>><div class="toggle-track"></div></label>
        </div>
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-eye-off"></i></div><div><div class="config-row-title">Ocultar saldos por padrão</div></div></div>
          <label class="toggle"><input type="checkbox" name="ocultar_saldo" <?= chk($u['cfg_ocultar_saldo']) ?>><div class="toggle-track"></div></label>
        </div>
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-clock"></i></div><div><div class="config-row-title">Tempo de sessão</div></div></div>
          <select class="cfg-select" name="sessao">
            <option value="15min" <?= sel($u['cfg_sessao'],'15min') ?>>15 minutos</option>
            <option value="30min" <?= sel($u['cfg_sessao'],'30min') ?>>30 minutos</option>
            <option value="1hora" <?= sel($u['cfg_sessao'],'1hora') ?>>1 hora</option>
            <option value="nunca" <?= sel($u['cfg_sessao'],'nunca') ?>>Nunca</option>
          </select>
        </div>
      </div>

      <!-- Dados -->
      <div class="section-hd">
        <div class="section-hd-icon"><i class="ti ti-database"></i></div>
        <div class="section-hd-title">Dados & Exportação</div>
      </div>
      <div class="config-card">
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-file-spreadsheet"></i></div><div><div class="config-row-title">Exportar dados</div></div></div>
          <div class="export-btns">
            <button type="button" class="btn-outline" onclick="showToast('Gerando CSV...','ti-file-spreadsheet')"><i class="ti ti-file-spreadsheet"></i> CSV</button>
            <button type="button" class="btn-outline" onclick="showToast('Gerando PDF...','ti-file-text')"><i class="ti ti-file-text"></i> PDF</button>
          </div>
        </div>
        <div class="config-row">
          <div class="config-row-left"><div class="config-row-icon"><i class="ti ti-refresh"></i></div><div><div class="config-row-title">Sincronização automática</div></div></div>
          <label class="toggle"><input type="checkbox" name="sync_auto" <?= chk($u['cfg_sync_auto']) ?>><div class="toggle-track"></div></label>
        </div>
      </div>

      <div class="save-bar">
        <button type="button" class="btn-cancel" onclick="showToast('Alterações descartadas','ti-x')">Cancelar</button>
        <button type="submit" class="btn-save"><i class="ti ti-device-floppy"></i> Salvar configurações</button>
      </div>
    </form>
  </div><!-- /page-config -->

</main>

<!-- ===== MODAL NOVA/EDITAR META ===== -->
<div class="modal-overlay" id="modal-meta">
  <div class="modal">
    <div class="modal-title" id="modal-meta-title"><i class="ti ti-target"></i> Nova meta</div>
    <form id="form-meta" method="POST" action="index.php">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="acao" value="salvar_meta">
      <input type="hidden" name="meta_id" id="meta_id_hidden" value="">

      <div class="form-group">
        <label class="form-label">Nome da meta *</label>
        <input id="meta-nome" class="form-input" name="meta_nome" type="text" placeholder="Ex: Viagem para Europa" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Categoria</label>
          <select id="meta-categoria" class="form-select" name="meta_categoria">
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
          <select id="meta-status" class="form-select" name="meta_status">
            <option value="andamento">Em andamento</option>
            <option value="concluida">Concluída</option>
            <option value="atrasada">Atrasada</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Valor total (R$) *</label>
          <input id="meta-total" class="form-input" name="meta_total" type="number" min="0" step="0.01" placeholder="15000" required>
        </div>
        <div class="form-group">
          <label class="form-label">Valor atual (R$)</label>
          <input id="meta-atual" class="form-input" name="meta_atual" type="number" min="0" step="0.01" placeholder="8500">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Prazo *</label>
        <input id="meta-prazo" class="form-input" name="meta_prazo" type="month" required>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="MetasModule.fecharModal()">Cancelar</button>
        <button type="submit" class="btn-primary"><i class="ti ti-device-floppy"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== MODAL GRÁFICO ===== -->
<div class="modal-overlay" id="modal-grafico">
  <div class="modal">
    <div class="modal-title"><i class="ti ti-chart-bar"></i> Novo gráfico</div>
    <form id="form-grafico" onsubmit="return false">
      <div class="form-group">
        <label class="form-label">Título *</label>
        <input id="g-titulo" class="form-input" type="text" placeholder="Ex: Receitas vs Despesas">
      </div>
      <div class="form-group">
        <label class="form-label">Tipo de gráfico</label>
        <div class="grafico-tipos">
          <button type="button" class="tipo-btn selected" data-tipo="bar"     onclick="GraficosModule.selecionarTipo('bar')"><i class="ti ti-chart-bar"></i> Barras</button>
          <button type="button" class="tipo-btn" data-tipo="line"    onclick="GraficosModule.selecionarTipo('line')"><i class="ti ti-chart-line"></i> Linha</button>
          <button type="button" class="tipo-btn" data-tipo="doughnut" onclick="GraficosModule.selecionarTipo('doughnut')"><i class="ti ti-chart-donut"></i> Rosca</button>
          <button type="button" class="tipo-btn" data-tipo="pie"     onclick="GraficosModule.selecionarTipo('pie')"><i class="ti ti-chart-pie"></i> Pizza</button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Rótulos (separados por vírgula) *</label>
        <input id="g-labels" class="form-input" type="text" placeholder="Jan, Fev, Mar">
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Nome série 1</label><input id="g-data1-nome" class="form-input" type="text" placeholder="Receitas"></div>
        <div class="form-group"><label class="form-label">Valores *</label><input id="g-data1" class="form-input" type="text" placeholder="4200,4800,5100"></div>
      </div>
      <div id="wrapper-dataset2">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Nome série 2</label><input id="g-data2-nome" class="form-input" type="text" placeholder="Despesas"></div>
          <div class="form-group"><label class="form-label">Valores série 2</label><input id="g-data2" class="form-input" type="text" placeholder="3100,3400,2900"></div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="GraficosModule.fecharModal()">Cancelar</button>
        <button type="button" class="btn-primary" onclick="GraficosModule.salvar()"><i class="ti ti-device-floppy"></i> Adicionar</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== TOAST ===== -->
<div class="toast" id="toast"></div>

<!-- ===== DADOS PHP → JS ===== -->
<script>
// Metas vindas do banco
window.METAS_DB = <?= json_encode(array_map(fn($m) => [
  'id'         => (int)$m['id'],
  'nome'       => $m['nome'],
  'categoria'  => $m['categoria'],
  'atual'      => (float)$m['valor_atual'],
  'total'      => (float)$m['valor_total'],
  'prazo'      => $m['prazo'],
  'status'     => $m['status'],
], $metasDB)) ?>;

window.MSG_META   = <?= json_encode($msgMeta) ?>;
window.CSRF_TOKEN = <?= json_encode($csrf) ?>;
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="metas.js"></script>
<script src="graficos.js"></script>
<script src="app.js"></script>
</body>
</html>

<?php
$user = Auth::user();

// 🤖 AUTO-BUMP: Detecta mudanças e versiona automaticamente
$autoBumpResult = Version::autoBump();

// Se fez bump automático, adicionar mensagem de sucesso
if ($autoBumpResult && $autoBumpResult['action'] === 'auto_bumped') {
    $result = $autoBumpResult['result'];
    $_SESSION['success'] = "🤖 Versionamento automático: <strong>{$result['old_version']}</strong> → <strong>{$result['new_version']}</strong> ({$result['type']})";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

  <head>
    <?php require_once __DIR__ . '/../includes/_admin-head.php'; ?>
    <title>Dashboard - <?= ADMIN_NAME ?></title>
  </head>

  <body class="page-dashboard">

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="m-dashboard__container">

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert--success">
          <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert--error">
          <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="m-dashboard__stats">

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon">
            <i data-lucide="database"></i>
          </div>
          <div class="m-dashboard__stat-value"><?= strtoupper(DB_TYPE) ?></div>
          <div class="m-dashboard__stat-label">Banco de Dados</div>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon">
            <i data-lucide="zap"></i>
          </div>
          <div class="m-dashboard__stat-value">AEGIS</div>
          <div class="m-dashboard__stat-label">v<?= Version::current() ?></div>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon">
            <i data-lucide="package"></i>
          </div>
          <div class="m-dashboard__stat-value"><?= count(ModuleManager::getInstalled()) ?></div>
          <div class="m-dashboard__stat-label">Módulos Instalados</div>
        </div>

      </div>
      
      <h3 class="m-dashboard__section-title">Setup</h3>

      <div class="m-dashboard__stats">
        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="settings"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Configurações</div>
          <div class="m-dashboard__stat-label">Configurar sistema</div>
          <a href="<?= url('/admin/settings') ?>" class="m-dashboard__btn">Configurar Sistema</a>
        </div>        
      </div>

      <h3 class="m-dashboard__section-title">Gerenciamento de Site</h3>

      <div class="m-dashboard__stats">

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-text"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Páginas</div>
          <div class="m-dashboard__stat-label">Gerenciar páginas do site</div>
          <a href="<?= url('/admin/pages') ?>" class="m-dashboard__btn">Gerenciar Páginas</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-code"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Includes</div>
          <div class="m-dashboard__stat-label">Gerenciar includes do sistema</div>
          <a href="<?= url('/admin/includes') ?>" class="m-dashboard__btn">Gerenciar Includes</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="menu"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Menu</div>
          <div class="m-dashboard__stat-label">Gerenciar menu de navegação</div>
          <a href="<?= url('/admin/menu') ?>" class="m-dashboard__btn">Gerenciar Menu</a>
        </div>        

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="box"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Módulos</div>
          <div class="m-dashboard__stat-label">Gerenciar módulos instalados</div>
          <a href="<?= url('/admin/modules') ?>" class="m-dashboard__btn">Gerenciar Módulos</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="box"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Cruds</div>
          <div class="m-dashboard__stat-label">Gerenciar os cruds do sistema</div>
          <a href="<?= url('/admin/cruds') ?>" class="m-dashboard__btn">Gerenciar Cruds</a>
        </div>        

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="users"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Administradores</div>
          <div class="m-dashboard__stat-label">Gerenciar usuários admin</div>
          <a href="<?= url('/admin/admins') ?>" class="m-dashboard__btn">Gerenciar Admins</a>
        </div>
      </div>     

      <h3 class="m-dashboard__section-title">SEO</h3>

      <div class="m-dashboard__stats">

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-text"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Checker</div>
          <div class="m-dashboard__stat-label">Ver Checker</div>
          <a href="<?= url('/admin/checker') ?>" class="m-dashboard__btn">Gerenciar Checker</a>
        </div>      

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-text"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Robots</div>
          <div class="m-dashboard__stat-label">Gerenciar Robots.txt</div>
          <a href="<?= url('/admin/robots') ?>" class="m-dashboard__btn">Gerenciar Robots</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-text"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Sitemap</div>
          <div class="m-dashboard__stat-label">Gerenciar Sitemap</div>
          <a href="<?= url('/admin/sitemap') ?>" class="m-dashboard__btn">Gerenciar Sitemap</a>
        </div>        

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-text"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Uptime robot</div>
          <div class="m-dashboard__stat-label">Gerenciar Uptime robot</div>
          <a href="<?= url('/admin/uptime-robot') ?>" class="m-dashboard__btn">Gerenciar Uptime Robot</a>
        </div>             

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-text"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Google Pagespeed</div>
          <div class="m-dashboard__stat-label">Gerenciar Google Pagespeed</div>
          <a href="<?= url('/admin/pagespeed') ?>" class="m-dashboard__btn">Gerenciar Pagespeed</a>
        </div>        

   

      </div>         

      <h3 class="m-dashboard__section-title">Relatórios</h3>

      <div class="m-dashboard__stats">    
        
        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="file-spreadsheet"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Import CSV</div>
          <div class="m-dashboard__stat-label">Importar dados via arquivo CSV</div>
          <a href="<?= url('/admin/import-csv') ?>" class="m-dashboard__btn">Importar CSV</a>
        </div>      

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="database"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Fontes de Dados</div>
          <div class="m-dashboard__stat-label">Gerenciar fontes de dados</div>
          <a href="<?= url('/admin/data-sources') ?>" class="m-dashboard__btn">Gerenciar Fontes</a>
        </div>         

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="bar-chart"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Relatórios</div>
          <div class="m-dashboard__stat-label">Gerenciar relatórios</div>
          <a href="<?= url('/admin/reports') ?>" class="m-dashboard__btn">Gerenciar Relatórios</a>
        </div>
   
      </div>

      <h3 class="m-dashboard__section-title">Sistema</h3>

      <div class="m-dashboard__stats">
        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="activity"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Health Check</div>
          <div class="m-dashboard__stat-label">Status do sistema e módulos</div>
          <a href="<?= url('/admin/health') ?>" class="m-dashboard__btn">Verificar Sistema</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="tag"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Versionamento</div>
          <div class="m-dashboard__stat-label">
            <?php if ($autoBumpResult && $autoBumpResult['action'] === 'auto_bumped'): ?>
              <span class="m-dashboard__badge m-dashboard__badge--success">🤖 Auto-bump ativo</span>
            <?php elseif ($autoBumpResult && $autoBumpResult['action'] === 'suggestion_only'): ?>
              <span class="m-dashboard__badge m-dashboard__badge--warning">⚠️ Sugestão pendente</span>
            <?php else: ?>
              Gerenciar versões do framework
            <?php endif; ?>
          </div>
          <a href="<?= url('/admin/version') ?>" class="m-dashboard__btn">Ver Versões</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="hard-drive"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Cache</div>
          <div class="m-dashboard__stat-label">Gerenciar cache do sistema</div>
          <a href="<?= url('/admin/cache') ?>" class="m-dashboard__btn">Gerenciar Cache</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="puzzle"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Componentes</div>
          <div class="m-dashboard__stat-label">Ver componentes disponíveis</div>
          <a href="<?= url('/admin/components') ?>" class="m-dashboard__btn">Ver Componentes</a>
        </div>
      </div>

      <h3 class="m-dashboard__section-title">Deploy</h3>

      <div class="m-dashboard__stats">
     

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="rocket"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Deploy</div>
          <div class="m-dashboard__stat-label">Deploy de código e banco de dados</div>
          <a href="<?= url('/admin/deploy') ?>" class="m-dashboard__btn">Fazer Deploy</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="database"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Importar SQL</div>
          <div class="m-dashboard__stat-label">Importar banco de dados (.sql)</div>
          <a href="<?= url('/admin/import-sql') ?>" class="m-dashboard__btn">Importar Banco</a>
        </div>

      </div>      

      <?php if (Core::membersEnabled()): ?>
      <h3 class="m-dashboard__section-title">Sistema de Membros</h3>
      <div class="m-dashboard__stats">
        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="users"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Membros</div>
          <div class="m-dashboard__stat-label">Gerenciar membros cadastrados</div>
          <a href="<?= url('/admin/members') ?>" class="m-dashboard__btn">Gerenciar Membros</a>
        </div>

        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="tags"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Grupos</div>
          <div class="m-dashboard__stat-label">Gerenciar grupos de membros</div>
          <a href="<?= url('/admin/groups') ?>" class="m-dashboard__btn">Gerenciar Grupos</a>
        </div>
      </div>
      <?php endif; ?>

      <?php
      // Verificar módulos instalados
      $installedModules = ModuleManager::getInstalled();
      if (!empty($installedModules)):
      ?>
      <h3 class="m-dashboard__section-title">Módulos Instalados</h3>
      <div class="m-dashboard__stats">
        <?php
        // Definir informações dos módulos
        $modulesInfo = [
          'palpites' => [
            'icon' => 'trophy',
            'title' => 'Palpites',
            'description' => 'Sistema de palpites esportivos',
            'url' => '/admin/palpites'
          ],
          'enquetes' => [
            'icon' => 'bar-chart',
            'title' => 'Enquetes',
            'description' => 'Sistema de enquetes e votações',
            'url' => '/admin/enquetes'
          ],
          'noticias' => [
            'icon' => 'newspaper',
            'title' => 'Notícias',
            'description' => 'Gerenciamento de notícias',
            'url' => '/admin/noticias'
          ]
        ];

        foreach ($installedModules as $module):
          $info = $modulesInfo[$module] ?? [
            'icon' => 'package',
            'title' => ucfirst($module),
            'description' => 'Módulo ' . ucfirst($module),
            'url' => '/admin/' . $module
          ];
        ?>
        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="<?= $info['icon'] ?>"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little"><?= htmlspecialchars($info['title']) ?></div>
          <div class="m-dashboard__stat-label"><?= htmlspecialchars($info['description']) ?></div>
          <a href="<?= url($info['url']) ?>" class="m-dashboard__btn">Acessar Módulo</a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>


      <h3 class="m-dashboard__section-title">Melhorias</h3>

      <div class="m-dashboard__stats">
    
        <div class="m-dashboard__stat-card">
          <div class="m-dashboard__stat-icon"><i data-lucide="rocket"></i></div>
          <div class="m-dashboard__stat-value m-dashboard__stat-little">Melhorias</div>
          <div class="m-dashboard__stat-label">Futuras Melhorias</div>
          <a href="<?= url('/admin/improvements') ?>" class="m-dashboard__btn">Gerenciar melhorias</a>
        </div>

      </div>   
      
      

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      lucide.createIcons();
    </script>

  </body>
  
</html>

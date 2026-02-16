/**
 * Dashboard JavaScript
 * Sidebar collapse, Dark mode toggle, Submenu accordion
 */

document.addEventListener('DOMContentLoaded', function() {

    // ========================================
    // SIDEBAR COLLAPSE
    // ========================================
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const breadcrumbToggle = document.getElementById('breadcrumbToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('l-sidebar--collapsed');

            // Salvar estado no localStorage
            const isCollapsed = sidebar.classList.contains('l-sidebar--collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
        });
    }

    // Breadcrumb toggle (mesmo comportamento)
    if (breadcrumbToggle) {
        breadcrumbToggle.addEventListener('click', function() {
            sidebar.classList.toggle('l-sidebar--collapsed');

            // Salvar estado no localStorage
            const isCollapsed = sidebar.classList.contains('l-sidebar--collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
        });
    }

    // Restaurar estado do sidebar
    if (localStorage.getItem('sidebarCollapsed') === '1') {
        sidebar.classList.add('l-sidebar--collapsed');
    }

    // Mobile: Toggle sidebar
    if (sidebarBackdrop) {
        // Abrir sidebar (mobile)
        const mobileToggle = document.querySelector('.m-header__menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.add('l-sidebar--open');
                sidebarBackdrop.classList.add('sidebar-backdrop--active');
                document.body.style.overflow = 'hidden';
            });
        }

        // Fechar sidebar (backdrop click)
        sidebarBackdrop.addEventListener('click', function() {
            sidebar.classList.remove('l-sidebar--open');
            sidebarBackdrop.classList.remove('sidebar-backdrop--active');
            document.body.style.overflow = '';
        });
    }

    // ========================================
    // SUBMENU ACCORDION
    // ========================================
    const menuItems = document.querySelectorAll('[data-submenu]');

    menuItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const parent = this.closest('.menu-item');
            const submenu = parent.querySelector('.submenu');

            // Toggle open
            parent.classList.toggle('menu-item--open');

            if (submenu) {
                if (parent.classList.contains('menu-item--open')) {
                    submenu.classList.add('submenu--open');
                } else {
                    submenu.classList.remove('submenu--open');
                }
            }
        });
    });

    // Abrir automaticamente categorias que contêm página ativa
    const activeCategories = document.querySelectorAll('.menu-item.active');
    activeCategories.forEach(function(category) {
        const submenu = category.querySelector('.submenu');
        if (submenu) {
            category.classList.add('menu-item--open');
            submenu.classList.add('submenu--open');
        }
    });

    // ========================================
    // USER DROPDOWN
    // ========================================
    const userToggle = document.querySelector('.user-toggle');
    const userDropdown = document.querySelector('.m-header__dropdown');

    if (userToggle && userDropdown) {
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('m-header__dropdown--active');
        });

        // Fechar ao clicar fora
        document.addEventListener('click', function() {
            userDropdown.classList.remove('m-header__dropdown--active');
        });

        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

});

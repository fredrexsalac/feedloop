'use strict';

(function () {
    const statsSelector = '.stat-number';

    function animateStats() {
        const stats = document.querySelectorAll(statsSelector);
        if (!stats.length || !('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const target = entry.target;
                const targetValue = parseInt(target.getAttribute('data-target'), 10) || 0;
                let currentValue = 0;
                const increment = Math.ceil(targetValue / 50);
                const interval = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= targetValue) {
                        currentValue = targetValue;
                        clearInterval(interval);
                    }
                    target.textContent = currentValue + (target.dataset.suffix || '');
                }, 40);
                observer.unobserve(target);
            });
        });

        stats.forEach(stat => observer.observe(stat));
    }

    function loadDynamicData() {
        fetch('api/get_stats.php')
            .then(response => response.json())
            .then(data => {
                if (!data?.success) return;
                const map = {
                    totalFeedback: '+',
                    activeUsers: '+',
                    responseTime: '',
                    satisfaction: '%'
                };
                Object.entries(map).forEach(([id, suffix]) => {
                    const el = document.getElementById(id);
                    if (!el || !(id in data)) return;
                    el.textContent = data[id] + suffix;
                    el.dataset.suffix = suffix;
                    el.dataset.target = data[id];
                });
            })
            .catch(() => {
                console.log('Using default stats - PHP connection not available');
            });
    }

    async function checkSession() {
        try {
            const response = await fetch('api/check_session.php');
            const userData = await response.json();
            if (userData?.logged_in) {
                updateNavForLoggedInUser(userData);
            }
        } catch (error) {
            console.log('Session check failed - user not logged in');
        }
    }

    function updateNavForLoggedInUser(userData) {
        const navbarNav = document.querySelector('.navbar-nav');
        if (!navbarNav) return;

        const navItems = navbarNav.querySelectorAll('.nav-item');
        Array.from(navItems).slice(-3).forEach(item => item.remove());

        const formsLink = document.createElement('li');
        formsLink.className = 'nav-item';
        formsLink.innerHTML = '<a class="nav-link btn btn-success ms-2 px-3 text-white" href="pages/user_portal.php">Forms</a>';
        navbarNav.appendChild(formsLink);

        const welcomeText = document.createElement('li');
        welcomeText.className = 'nav-item';
        welcomeText.innerHTML = `
            <span class="navbar-text me-2 d-flex align-items-center">
                ${userData.profile_pic ? `<img src="${userData.profile_pic.replace('../', '')}" alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #28a745;">` : ''}
                Welcome, <strong>${userData.full_name}</strong>
            </span>
        `;
        navbarNav.appendChild(welcomeText);

        const logoutLink = document.createElement('li');
        logoutLink.className = 'nav-item';
        logoutLink.innerHTML = '<a class="nav-link btn btn-outline-secondary ms-2 px-3" href="auth/logout.php">Logout</a>';
        navbarNav.appendChild(logoutLink);
    }

    document.addEventListener('DOMContentLoaded', function () {
        animateStats();
        loadDynamicData();
        checkSession();
        initSmoothScroll();
        initNavbarScroll();
    });
})();

(function () {
    const installKey = 'fl_install_prompt_shown';
    let deferredPrompt = null;

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function initNavbarScroll() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }

    function initCookieBanner() {
        try {
            const banner = document.getElementById('cookieConsent');
            if (!banner) return;
            const accepted = localStorage.getItem('fl_cookie_consent');
            if (!accepted) {
                banner.style.display = 'block';
            }
            document.getElementById('cookieAccept')?.addEventListener('click', function () {
                localStorage.setItem('fl_cookie_consent', 'accepted');
                banner.style.display = 'none';
            });
            document.getElementById('cookieDecline')?.addEventListener('click', function () {
                localStorage.setItem('fl_cookie_consent', 'declined');
                banner.style.display = 'none';
            });
        } catch (e) {
            console.warn('Cookie banner init failed', e);
        }
    }

    function initPwaPrompt() {
        const modalEl = document.getElementById('installAppModal');
        const installModal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const installBtn = document.getElementById('installConfirmBtn');
        const dismissBtn = document.getElementById('installDismissBtn');
        const textEl = document.getElementById('installModalText');

        fetch('api/session/status.php')
            .then(r => r.json())
            .then(s => {
                if (!s?.success || !textEl) return;
                const role = (s.logged_in ? (s.role || 'user') : 'guest');
                textEl.textContent = role === 'guest'
                    ? 'Install FeedLoop for faster access to announcements and forms as a guest.'
                    : 'Install FeedLoop for quick access to your forms, notifications, and profile.';
            })
            .catch(() => {});

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (localStorage.getItem(installKey) === '1') return;
            installModal?.show();
        });

        installBtn?.addEventListener('click', async function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            localStorage.setItem(installKey, '1');
            installModal?.hide();
        });

        dismissBtn?.addEventListener('click', function () {
            localStorage.setItem(installKey, '1');
        });
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return;
        navigator.serviceWorker.register('service-worker.js').catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCookieBanner();
        initPwaPrompt();
        registerServiceWorker();
    });
})();

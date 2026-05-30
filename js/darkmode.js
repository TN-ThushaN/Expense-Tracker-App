(function () {
  // Apply saved preference immediately to avoid flash
  if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark');
    if (document.body) {
      document.body.classList.add('dark');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Sync body dark class with documentElement dark class on load
    if (document.documentElement.classList.contains('dark')) {
      document.body.classList.add('dark');
    }

    const btn = document.getElementById('darkToggle');
    if (!btn) return;

    function updateIcon() {
      btn.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
    }
    updateIcon();

    btn.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      document.documentElement.classList.toggle('dark');
      const theme = document.body.classList.contains('dark') ? 'dark' : 'light';
      localStorage.setItem('theme', theme);
      updateIcon();
    });
  });
})();
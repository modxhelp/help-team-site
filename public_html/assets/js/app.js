const currentPath = window.location.pathname.replace(/\/$/, '') || '/';

document.querySelectorAll('.nav a').forEach((link) => {
  const linkPath = new URL(link.href).pathname.replace(/\/$/, '') || '/';

  if (linkPath === currentPath) {
    link.setAttribute('aria-current', 'page');
  }
});

document.querySelectorAll('[data-submit-placeholder]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    form.querySelector('[data-form-status]').textContent = 'Форма подключится на следующем этапе.';
  });
});

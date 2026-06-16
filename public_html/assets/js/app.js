const currentPath = window.location.pathname.replace(/\/$/, '') || '/';

document.querySelectorAll('.nav a').forEach((link) => {
  const linkPath = new URL(link.href).pathname.replace(/\/$/, '') || '/';

  if (linkPath === currentPath) {
    link.setAttribute('aria-current', 'page');
  }
});

const growTextarea = (textarea) => {
  textarea.style.height = 'auto';
  textarea.style.height = `${textarea.scrollHeight}px`;
};

document.querySelectorAll('textarea[data-autogrow]').forEach((textarea) => {
  growTextarea(textarea);
  textarea.addEventListener('input', () => growTextarea(textarea));
});

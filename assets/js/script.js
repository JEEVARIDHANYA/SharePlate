document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.main-nav');
  if (toggle && nav) toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!expanded));
    nav.classList.toggle('is-open', !expanded);
  });

  document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
  }));

  document.querySelectorAll('form[data-validate]').forEach(form => form.addEventListener('submit', event => {
    if (!form.reportValidity()) event.preventDefault();
    const password = form.querySelector('[name="password"]');
    const confirm = form.querySelector('[name="confirm_password"]');
    if (password && confirm && password.value !== confirm.value) {
      event.preventDefault(); confirm.setCustomValidity('Passwords do not match.'); confirm.reportValidity();
    }
  }));
  document.querySelectorAll('[name="confirm_password"]').forEach(input => input.addEventListener('input', () => input.setCustomValidity('')));

  const updateCountdowns = () => document.querySelectorAll('[data-countdown]').forEach(el => {
    const deadline = new Date(el.dataset.countdown).getTime();
    const remaining = deadline - Date.now();
    if (!Number.isFinite(deadline) || remaining <= 0) { el.textContent = 'Expired'; el.classList.add('is-expired'); return; }
    const days = Math.floor(remaining / 86400000);
    const hours = Math.floor((remaining % 86400000) / 3600000);
    const minutes = Math.floor((remaining % 3600000) / 60000);
    el.textContent = days ? `${days}d ${hours}h left` : `${hours}h ${minutes}m left`;
  });
  updateCountdowns();
  window.setInterval(updateCountdowns, 60000);
});

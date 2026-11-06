(function () {
  'use strict';

  const active = new Map();
  const indicator = document.createElement('div');
  indicator.className = 'ui-loading-indicator';
  indicator.hidden = true;
  indicator.setAttribute('role', 'status');
  indicator.setAttribute('aria-live', 'polite');
  indicator.innerHTML = '<p>Loading, please wait…</p><div class="ui-loading-skeleton" aria-hidden="true"><div class="ui-shimmer ui-shimmer-icon"></div><div class="ui-shimmer-lines"><div class="ui-shimmer ui-shimmer-line"></div><div class="ui-shimmer ui-shimmer-line"></div></div></div>';
  document.body.appendChild(indicator);

  function stop(control) {
    const state = active.get(control);
    if (!state) return;
    clearTimeout(state.timer);
    control.classList.remove('ui-pending');
    if (state.busy === null) control.removeAttribute('aria-busy');
    else control.setAttribute('aria-busy', state.busy);
    active.delete(control);
    indicator.hidden = active.size === 0;
  }

  function start(control, persistent = false) {
    if (!control) return;
    const previous = active.get(control);
    if (previous && previous.persistent && !persistent) return;
    const busy = previous ? previous.busy : control.getAttribute('aria-busy');
    if (previous) clearTimeout(previous.timer);
    control.classList.add('ui-pending');
    control.setAttribute('aria-busy', 'true');
    active.set(control, {busy, persistent, timer: persistent ? null : setTimeout(() => stop(control), 400)});
    indicator.hidden = false;
  }

  function reset() {
    Array.from(active.keys()).forEach(stop);
  }

  window.UIFeedback = {start: control => start(control, true), stop};

  document.addEventListener('click', event => {
    const control = event.target.closest('button,a[href]');
    if (!control || control.disabled || control.dataset.loadingIgnore !== undefined) return;
    if (active.get(control)?.persistent) {
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }
    // Keep same-window navigation busy until the next document arrives.
    // Immediate actions (tabs, ratings, clear, scrolling) get a brief transition.
    const navigation = control.matches('a[href]') && !control.hasAttribute('data-download')
      && control.target !== '_blank' && !control.getAttribute('href').startsWith('#')
      && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey;
    start(control, navigation);
  }, true);

  document.addEventListener('submit', event => {
    if (event.defaultPrevented) return;
    if (active.get(event.target)?.persistent) {
      event.preventDefault();
      return;
    }
    start(event.target, true);
    start(event.submitter || event.target.querySelector('button[type="submit"],button:not([type])'), true);
  });

  document.addEventListener('invalid', () => {
    Array.from(active.keys()).forEach(control => {
      if (!active.get(control).persistent) stop(control);
    });
  }, true);

  document.addEventListener('change', event => {
    if (event.target.matches('[data-auto-submit]')) event.target.form.requestSubmit();
  });

  document.addEventListener('click', async event => {
    const link = event.target.closest('a[data-download]');
    if (!link || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    start(link, true);
    try {
      const response = await fetch(link.href);
      if (!response.ok || !response.headers.get('content-type')?.includes('text/csv')) {
        throw new Error('Unable to download the CSV. Please reload the page and check that you are signed in.');
      }
      const url = URL.createObjectURL(await response.blob());
      const download = document.createElement('a');
      download.href = url;
      download.download = response.headers.get('content-disposition')?.match(/filename="([^"]+)"/)?.[1] || 'feedback.csv';
      download.dataset.loadingIgnore = '';
      document.body.appendChild(download);
      download.click();
      download.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (error) {
      window.alert(error.message || 'Unable to download the CSV. Please try again.');
    } finally {
      stop(link);
    }
  });

  window.addEventListener('pageshow', reset);
  // A visible skeleton while the initial document and its images finish loading.
  if (document.readyState !== 'complete') {
    start(document.body, true);
    window.addEventListener('load', () => stop(document.body), {once: true});
  }
})();

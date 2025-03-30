const hooks = {}, hooked = new WeakMap(), pending = {};

export const hook = (selector = '', hook = (e) => null) => {

  for (const e of document.querySelectorAll(selector)) {
    call(e, [hook]);
  }

  hooks[selector] ??= [];
  hooks[selector].push(hook);
}
 
const sync = (key, value) => {
  for (const recipient of document.querySelectorAll(`[data-sync="${ key }"]`)) {
    recipient.textContent = value;
  }

  document.dispatchEvent(new CustomEvent(`bq:sync/${ key }`, { detail: value }));
  document.dispatchEvent(new CustomEvent(`bq:sync`, { detail: value }));
}

export const dom = (html = '') => {
  const doc = new DOMParser().parseFromString(html, 'text/html');

  for (const node of doc.querySelectorAll('[data-sync]')) {
    sync(node.dataset.sync, node.textContent);
  }

  return doc;
}

/** -- */

const call = (e, hooks = []) => {
  const done = hooked.get(e) ?? [];

  for (const hook of hooks) {
    if (!done.includes(hook)) {
      hook(e);

      done.push(hook);
      hooked.set(e, done);
    }       
  }
}

const observer = new MutationObserver(mutations => {

  for (const e of mutations) {
    const target = e.target;

    for (const selector in hooks) {     
      for (const e of target.querySelectorAll(selector)) {
        call(e, hooks[selector]);
      }
    }
  }

});

observer.observe(document, { childList: true, subtree: true });

hook('[type="reset"]', (e) => {

  e.addEventListener('click', (ev) => {
    const form = e.closest('form');

    for (const input of form?.querySelectorAll('input') ?? []) {
      input.value = '';
    }

    form.dispatchEvent(new Event('submit', { bubbles: true }));

    ev.stopPropagation();
    ev.preventDefault();
  });

});

hook('[data-trigger]', (e) => {
  let timeout;

  e.addEventListener(e.dataset.trigger, () => {
    clearTimeout(timeout);

    const submit = () => e.closest('form').dispatchEvent(new Event('submit', { bubbles: true }));

    e.dataset.delay ? timeout = setTimeout(submit, parseInt(e.dataset.delay)) : submit();
  });
  
});

hook('form', (e) => {
  const select  = e.dataset.select,
        append  = e.dataset.append,
        remove  = e.dataset.remove,
        replace = e.dataset.replace;

  e.addEventListener('submit', async (ev) => {
    const url    = new URL(e.dataset.url ?? location.pathname, location.href),
          method = (e.method ?? 'GET').toUpperCase();

    ev.stopPropagation();
    ev.preventDefault();

    document.dispatchEvent(new CustomEvent(`bq:fetch`, { 
      detail: e.dataset.url ?? location.pathname 
    }));

    for (const input of e.querySelectorAll('input,select')) {

      if (method == 'GET') {

        if (input.type == 'checkbox') {
          input.checked ? url.searchParams.set(input.name, input.value) : null;
        } else if (input.value) {
          url.searchParams.set(input.name, input.value);
        }
      }

    }

    e.classList.add('--loading');

    if (append || replace) {
      document.querySelector(append || replace).classList.add('--loading');
    }
  
    if (remove) {
      for (const e of document.querySelectorAll(remove)) {
        e.classList.add('--remove');
      }
    }

    if (pending[url.pathname]) {
      pending[url.pathname].abort('-');
    } 
  
    pending[url.pathname] = new AbortController();
  
    const response = await fetch(url, { signal: pending[url.pathname].signal }).catch(_ => ({ ok: false }));

    if (response.ok) {
      const html = dom(await response.text());

      delete pending[url.pathname];
      
      if (url.pathname == location.pathname) {
        window.history.replaceState(null, null, url);
      }

      if (remove) {
        for (const e of document.querySelectorAll(remove)) {
          e.remove();
        }
      }
      
      if (append) {
        const appendContainer = document.querySelector(append);

        for (const e of html.querySelectorAll(select)) {
          appendContainer.append(e);
        }
      }

      if (replace) {
        document.querySelector(replace).replaceWith(html.querySelector(replace));
      }
    }

    if (append) {
      const appendContainer = document.querySelector(append);
      
      requestAnimationFrame(() => appendContainer.classList.remove('--loading'));
    }

    e.classList.remove('--loading');

    document.dispatchEvent(new CustomEvent(`bq:fetched`, { 
      detail: e.dataset.url ?? location.pathname 
    }));

  });
});
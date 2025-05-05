const hooks = {}, hooked = new WeakMap(), pending = {};

document.startViewTransition ??= (func) => func();

export const hook = (selector = '', hook = (e) => null) => {

  for (const e of document.querySelectorAll(selector)) {
    call(e, [hook]);
  }

  hooks[selector] ??= [];
  hooks[selector].push(hook);
}
 
const sync = (key, value, attributes = []) => {

  for (const recipient of document.querySelectorAll(`[data-sync="${ key }"]`)) {
    value ? recipient.textContent = value : null;

    for (const attr of attributes) {
      recipient.setAttribute(attr.name, attr.value);
    }
  }

  document.dispatchEvent(new CustomEvent(`bq:sync/${ key }`, { detail: value }));
  document.dispatchEvent(new CustomEvent(`bq:sync`, { detail: value }));
}

export const dom = (html = '') => {
  const doc = new DOMParser().parseFromString(html, 'text/html');

  for (const node of doc.querySelectorAll('[data-sync]')) {
    sync(
      node.dataset.sync, node.textContent, 
      Array.from(node.attributes).filter(
        attr => attr.name.startsWith('data-sync.')
      ).map(
        attr => ({
          name: attr.name.replace('data-sync.', ''),
          value: attr.value
        })
      )
    );
  }

  for (const update of doc.querySelectorAll('node-update')) {
    const nodes = document.querySelectorAll(update.dataset.select);

    for (const node of nodes) {
      
      for (const patch of update.children) {
        const k = patch.nodeName.toLowerCase();

        if (k == 'class-remove') {
          node.classList.remove(patch.dataset.name);
        }

        if (k == 'class-add') {
          node.classList.add(patch.dataset.name);
        }

        if (k == 'attribute-remove') {
          node.removeAttribute(patch.dataset.name);
        }

        if (k == 'attribute-set') {
          node.setAttribute(patch.dataset.name, patch.dataset.value ?? '');
        }
      }
    }    
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

const reset = (form) => {
  for (const input of form?.querySelectorAll('input,select,textarea,[name]') ?? []) {
    input.value = '';
  }
}

hook('[type="reset"]', (e) => {

  e.addEventListener('click', (ev) => {
    const form = e.closest('form');

    reset(form);

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

  const replace = e.dataset.replace;
  const append  = e.dataset.append;
  const prepend = e.dataset.prepend;
  const remove  = e.dataset.remove;
  const select  = e.dataset.select;

  e.addEventListener('submit', async (ev) => {
    if (e.hasAttribute('data-native')) return;

    const submitter = ev.submitter;

    const action = e.dataset.url
                ?? (submitter ? submitter.getAttribute('formaction') : null)
                ?? e.action
                ?? location.pathname;

    const url = new URL(action, location.href);

    const method = ((submitter && submitter.getAttribute('formmethod')) ?? e.getAttribute('method') ?? 'GET').toUpperCase();

    const contentType = (submitter && submitter.getAttribute('formenctype')) ?? e.getAttribute('enctype') ?? 'application/x-www-form-urlencoded';

    let data = null;

    if (contentType === 'application/json') {
      data = {};
    }

    ev.stopPropagation();
    ev.preventDefault();

    document.dispatchEvent(new CustomEvent(`bq:fetch`, {
      detail: e.action ?? location.pathname
    }));

    const setProperty = (key, v) => {
      const parts = key.split('.');
      let d = data;

      for (let i = 0, k; i < parts.length; i++) {
        k = parts[i];

        if (i === parts.length - 1) {
          if (Array.isArray(d[k])) {
            d[k].push(v);
          } else if (d[k]) {
            d[k] = [d[k]];
            d[k].push(v);
          } else {
            d[k] = v;
          }
          continue;
        }

        d[k] ??= {};
        d = d[k];
      }
    };

    const valueOf = (input) => {
      return input.type === 'checkbox' ? input.checked
           : input.type === 'number'   ? input.valueAsNumber
           : input.value;
    };

    const inputs = e.querySelectorAll('input, select, textarea, [name]');

    for (const input of inputs) {
      
      if (input.type === 'radio' && !input.checked) continue;

      const v = valueOf(input);

      if (method === 'GET') {
        if (v !== false) url.searchParams.set(input.name, v);
      } else if (method === 'POST' && contentType === 'application/x-www-form-urlencoded') {
        data ??= new URLSearchParams();
        if (v !== false) data.append(input.name, v);
      } else if (method === 'POST' && contentType === 'application/json') {
        setProperty(input.name, v);
      }
    }

    e.classList.add('--loading');

    if (append || replace) {
      document.querySelector(append || replace)?.classList.add('--loading');
    }

    if (remove) {
      for (const el of document.querySelectorAll(remove)) {
        el.classList.add('--remove');
      }
    }

    if (pending[url.pathname]) {
      pending[url.pathname].abort('-');
    }

    pending[url.pathname] = new AbortController();

    const response = await fetch(url, {
      method: method,
      signal: pending[url.pathname].signal,
      body: method === 'GET' ? null
           : method === 'POST' && contentType === 'application/json' ? JSON.stringify(data)
           : data,
      headers: {
        'content-type': contentType,
        'accept': 'text/html'
      },
      credentials: 'same-origin'
    }).catch(_ => ({ ok: false }));


    if (response.ok) {

      const redirectTo = response.headers.get('X-Redirect');

      if (redirectTo) {
        location.href = redirectTo;
        return;
      }

      const html = dom(await response.text());

      delete pending[url.pathname];

      if (url.pathname === location.pathname) {
        window.history.replaceState(null, null, url.pathname);
      }

      document.startViewTransition(() => {
        if (remove) {
          for (const el of document.querySelectorAll(remove)) {
            el.remove();
          }
        }

        if (append) {
          const container = document.querySelector(append);
          for (const el of html.querySelectorAll(select)) {
            container.append(el);
          }
        }

        if (replace) {
          const oldEl = document.querySelector(replace);
          const newEl = html.querySelector(select || replace);
          if (oldEl && newEl) {
            oldEl.replaceWith(newEl);
          }
        }

        if (prepend) {
          const p = document.querySelector(prepend);
          const newEl = html.querySelector(select);
          if (p && newEl) {
            p.parentNode.insertBefore(newEl, p);
          }
        }

        if (method !== 'GET') {
          reset(e);
        }
      });
    }

    if (append) {
      const appendContainer = document.querySelector(append);
      requestAnimationFrame(() => appendContainer?.classList.remove('--loading'));
    }

    e.classList.remove('--loading');

    document.dispatchEvent(new CustomEvent(`bq:fetched`, {
      detail: e.dataset.url ?? location.pathname
    }));
  });
});
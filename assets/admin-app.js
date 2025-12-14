(function (wp) {
  const { createElement, useState, useEffect } = wp.element;
  const apiFetch = wp.apiFetch;

  function App() {
    const [cfg, setCfg] = useState(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
      apiFetch({ path: '/qbnox-smtp/v1/settings' })
        .then(setCfg);
    }, []);

    if (!cfg) return createElement('p', null, 'Loading settings…');

    const update = (path, value) => {
      const next = { ...cfg };
      next[path[0]][path[1]] = value;
      setCfg(next);
    };

    const save = () => {
      setSaving(true);
      apiFetch({
        path: '/qbnox-smtp/v1/settings',
        method: 'POST',
        data: cfg
      }).then(() => setSaving(false));
    };

const testMail = () => {
  apiFetch({
    path: '/qbnox-smtp/v1/test-mail',
    method: 'POST'
  }).then(res => {
    if (res.status === 'success') {
      alert(`Test email accepted by SMTP.\nSent to: ${res.to}`);
    } else {
      alert(`Test email failed.\nReason: ${res.message}`);
    }
  });
};

    return createElement(
      'div',
      { className: 'qbnox-card' },

      createElement('h2', null, 'SMTP Configuration'),

      createElement('input', {
        placeholder: 'SMTP Host',
        value: cfg.smtp?.host || '',
        onChange: e => update(['smtp','host'], e.target.value)
      }),

      createElement('input', {
        placeholder: 'SMTP Username',
        value: cfg.smtp?.username || '',
        onChange: e => update(['smtp','username'], e.target.value)
      }),

      createElement('input', {
        type: 'password',
        placeholder: 'SMTP Password',
        value: cfg.smtp?.password || '',
        onChange: e => update(['smtp','password'], e.target.value)
      }),

      createElement('input', {
        placeholder: 'From Email',
        value: cfg.smtp?.from_email || '',
        onChange: e => update(['smtp','from_email'], e.target.value)
      }),

      createElement(
        'p',
        null,
        createElement(
          'button',
          { className: 'button button-primary', onClick: save, disabled: saving },
          saving ? 'Saving…' : 'Save Settings'
        ),
        ' ',
        createElement(
          'button',
          { className: 'button', onClick: testMail },
          'Send Test Email'
        )
      )
    );
  }

  document.addEventListener('DOMContentLoaded', () => {
    wp.element.render(
      createElement(App),
      document.getElementById('qbnox-smtp-root')
    );
  });
})(window.wp);


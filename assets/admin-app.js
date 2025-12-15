(function (wp) {
  const { createElement, useState, useEffect } = wp.element;
  const apiFetch = wp.apiFetch;

  const PROVIDERS = {
    custom: { label: 'Custom SMTP' },
    brevo: {
      label: 'Brevo',
      host: 'smtp-relay.brevo.com',
      port: 587,
      encryption: 'tls',
    },
    postmark: {
      label: 'Postmark',
      host: 'smtp.postmarkapp.com',
      port: 587,
      encryption: 'tls',
    },
    ses: {
      label: 'Amazon SES',
      host: 'email-smtp.us-east-1.amazonaws.com',
      port: 587,
      encryption: 'tls',
    },
  };

  function App() {
    const [cfg, setCfg] = useState(null);
    const [tab, setTab] = useState('smtp');
    const [saving, setSaving] = useState(false);
    const [showPass, setShowPass] = useState(false);
    const [health, setHealth] = useState(null);

    useEffect(() => {
      apiFetch({ path: '/qbnox-smtp/v1/settings' }).then(setCfg);
    }, []);

    if (!cfg) {
      return createElement('p', null, 'Loading…');
    }

    /**
     * Update a single SMTP field safely
     */
    const update = (field, value) => {
      setCfg((prev) => {
        const next = JSON.parse(JSON.stringify(prev));
        next.smtp[field] = value;
        return next;
      });
    };

    /**
     * Apply provider preset (single atomic update)
     */
    const applyProvider = (provider) => {
      setCfg((prev) => {
        const next = JSON.parse(JSON.stringify(prev));
        next.smtp.provider = provider;

        const preset = PROVIDERS[provider];
        if (preset && preset.host) {
          next.smtp.host = preset.host;
          next.smtp.port = preset.port;
          next.smtp.encryption = preset.encryption;
        }

        return next;
      });
    };

    const save = () => {
      setSaving(true);
      apiFetch({
        path: '/qbnox-smtp/v1/settings',
        method: 'POST',
        data: cfg,
      }).then(() => setSaving(false));
    };

    const testMail = () => {
      apiFetch({
        path: '/qbnox-smtp/v1/test-mail',
        method: 'POST',
      }).then((res) => {
        setHealth(res.status === 'success' ? 'ok' : 'bad');
        alert(
          res.status === 'success'
            ? `✅ Test email accepted.\nSent to: ${res.to}`
            : `❌ Test failed.\n${res.message}`
        );
      });
    };

    return createElement(
      'div',
      { className: 'qbnox-card' },

      /* Tabs */
      createElement(
        'div',
        { className: 'qbnox-tabs' },
        ['smtp', /* 'oauth', 'analytics', */ 'license', 'aboutus'].map((t) =>
          createElement(
            'div',
            {
              key: t,
              className: 'qbnox-tab ' + (tab === t ? 'active' : ''),
              onClick: () => setTab(t),
            },
            t === 'smtp'
              ? 'SMTP Settings'
              : t === 'oauth'
                ? 'OAuth (Coming Soon)'
                : t === 'license'
                  ? 'MIT License'
                  : t === 'aboutus'
                    ? 'About Us'
                    : t
          )
        )
      ),

      /* SMTP TAB */
      tab === 'smtp' &&
        createElement(
          'div',
          null,

          /* Provider */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'PROVIDER'),
            createElement(
              'select',
              {
                value: cfg.smtp?.provider || 'custom',
                onChange: (e) => applyProvider(e.target.value),
              },
              Object.keys(PROVIDERS).map((p) =>
                createElement(
                  'option',
                  { key: p, value: p },
                  PROVIDERS[p].label
                )
              )
            )
          ),

          /* SMTP Host */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'SMTP HOST'),
            createElement('input', {
              type: 'text',
              value: cfg.smtp?.host || '',
              onChange: (e) => update('host', e.target.value),
            })
          ),

          /* Port */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'PORT'),
            createElement('input', {
              type: 'number',
              value: cfg.smtp?.port || 587,
              onChange: (e) => update('port', e.target.value),
            })
          ),

          /* Encryption */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'ENCRYPTION'),
            createElement(
              'select',
              {
                value: cfg.smtp?.encryption || 'tls',
                onChange: (e) => update('encryption', e.target.value),
              },
              ['tls', 'ssl', 'none'].map((v) =>
                createElement('option', { key: v, value: v }, v.toUpperCase())
              )
            )
          ),

          /* Username */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'USERNAME'),
            createElement('input', {
              type: 'text',
              value: cfg.smtp?.username || '',
              onChange: (e) => update('username', e.target.value),
            })
          ),

          /* Password (eye icon) */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'PASSWORD'),
            createElement(
              'div',
              { className: 'qbnox-password-wrap' },
              createElement('input', {
                type: showPass ? 'text' : 'password',
                value: cfg.smtp?.password || '',
                onChange: (e) => update('password', e.target.value),
              }),
              createElement('span', {
                className:
                  'dashicons ' +
                  (showPass
                    ? 'dashicons-visibility open'
                    : 'dashicons-hidden closed') +
                  ' qbnox-eye',
                title: showPass ? 'Hide password' : 'Show password',
                role: 'button',
                'aria-label': showPass ? 'Hide password' : 'Show password',
                onClick: () => setShowPass(!showPass),
              })
            )
          ),

          /* From Email */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'FROM EMAIL'),
            createElement('input', {
              type: 'text',
              value: cfg.smtp?.from_email || '',
              onChange: (e) => update('from_email', e.target.value),
            })
          ),

          /* From Name */
          createElement(
            'div',
            { className: 'qbnox-field' },
            createElement('label', null, 'FROM NAME'),
            createElement('input', {
              type: 'text',
              value: cfg.smtp?.from_name || '',
              onChange: (e) => update('from_name', e.target.value),
            })
          ),

          /* Actions */
          createElement(
            'div',
            { className: 'qbnox-actions' },
            createElement(
              'button',
              {
                className: 'button button-primary',
                onClick: save,
                disabled: saving,
              },
              saving ? 'Saving…' : 'Save Settings'
            ),
            ' ',
            createElement(
              'button',
              { className: 'button', onClick: testMail },
              'Send Test Email'
            ),
            health &&
              createElement(
                'span',
                { className: 'qbnox-health ' + health },
                health === 'ok' ? '● Healthy' : '● Error'
              )
          )
        ),
      /*
      tab === 'oauth' &&
        createElement(
          'p',
          null,
          'OAuth support will appear here in the next release.'
        ),

      tab === 'analytics' &&
        createElement(
          'p',
          null,
          'Delivery analytics are collected via provider webhooks.'
        ),
	*/
      tab === 'license' &&
        createElement(
          'div',
          { className: 'qbnox-license' },

          createElement('h2', null, 'MIT License'),

          createElement(
            'p',
            null,
            'This plugin is released under the MIT License, a permissive open-source license that allows free use, modification, and distribution.'
          ),

          createElement(
            'pre',
            {
              style: {
                background: '#f6f7f7',
                padding: '12px',
                maxHeight: '300px',
                overflow: 'auto',
                whiteSpace: 'pre-wrap',
                border: '1px solid #ccd0d4',
              },
            },
            `MIT License

Copyright (c) 2025 Qbnox Systems Private Limited

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.`
          ),

          createElement(
            'p',
            { style: { marginTop: '12px', fontStyle: 'italic' } },
            'A copy of this license is also included in the plugin source code.'
          )
        ),
      tab === 'aboutus' &&
        createElement(
          'div',
          { className: 'qbnox-about' },

          createElement('h2', null, 'About Qbnox Systems'),

          createElement(
            'p',
            null,
            'Qbnox Systems is a technology services company specializing in managed hosting, infrastructure operations, and enterprise support for open-source platforms.'
          ),

          createElement(
            'p',
            null,
            'We provide secure, scalable, and compliant hosting solutions for Moodle, Pressbooks, WordPress, and a wide range of other open-source software used by universities, government institutions, and enterprises.'
          ),

          createElement(
            'p',
            null,
            'Our focus is on reliability, security, and long-term maintainability, enabling institutions to concentrate on teaching, research, and digital innovation while we manage the underlying platform and infrastructure.'
          ),

          createElement('h3', null, 'Contact Information'),

          createElement(
            'ul',
            { className: 'qbnox-contact-list' },

            createElement(
              'li',
              null,
              createElement('strong', null, 'Contact Person: '),
              'Ugendreshwar Kudupudi'
            ),

            createElement(
              'li',
              null,
              createElement('strong', null, 'Email: '),
              createElement(
                'a',
                { href: 'mailto:ugen@qbnox.com' },
                'ugen@qbnox.com'
              )
            ),

            createElement(
              'li',
              null,
              createElement('strong', null, 'Phone: '),
              '+91 90085 11933'
            ),

            createElement(
              'li',
              null,
              createElement('strong', null, 'Website: '),
              createElement(
                'a',
                {
                  href: 'https://www.qbnox.com',
                  target: '_blank',
                  rel: 'noopener noreferrer',
                },
                'https://www.qbnox.com'
              )
            ),

            createElement(
              'li',
              null,
              createElement('strong', null, 'GitHub: '),
              createElement(
                'a',
                {
                  href: 'https://github.com/ugenkudupudiqbnox/qbnox-smtp',
                  target: '_blank',
                  rel: 'noopener noreferrer',
                },
                'https://github.com/ugenkudupudiqbnox/qbnox-smtp'
              )
            )
          ),

          createElement(
            'p',
            { style: { marginTop: '16px', fontStyle: 'italic' } },
            'For enterprise support, managed hosting, compliance requirements, or security-related queries, please reach out using the contact details above.'
          )
        ),
      createElement(
        'div',
        { style: { marginTop: '20px' } },

        createElement(
          'a',
          {
            href: 'https://github.com/ugenkudupudiqbnox/qbnox-smtp/issues/new',
            target: '_blank',
            rel: 'noopener noreferrer',
            className: 'button button-primary',
            style: { marginRight: '10px' },
          },
          'Report Issue on GitHub'
        )
      )
    );
  }

  wp.element.render(
    createElement(App),
    document.getElementById('qbnox-smtp-root')
  );
})(window.wp);

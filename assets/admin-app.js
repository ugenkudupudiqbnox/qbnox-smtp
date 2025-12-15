(function (wp) {
  const { createElement, useState, useEffect } = wp.element;
  const apiFetch = wp.apiFetch;

  const PROVIDERS = {
    custom: { label: 'Custom SMTP' },
    brevo: {
      label: 'Brevo',
      host: 'smtp-relay.brevo.com',
      port: 587,
      encryption: 'tls'
    },
    postmark: {
      label: 'Postmark',
      host: 'smtp.postmarkapp.com',
      port: 587,
      encryption: 'tls'
    },
    ses: {
      label: 'Amazon SES',
      host: 'email-smtp.us-east-1.amazonaws.com',
      port: 587,
      encryption: 'tls'
    }
  };

  function App() {
    const [cfg, setCfg] = useState(null);
    const [tab, setTab] = useState('smtp');
    const [saving, setSaving] = useState(false);
    const [showPass, setShowPass] = useState(false);
    const [health, setHealth] = useState(null);
    const startOAuth = (provider) => {
		  wp.apiFetch({
			  path: '/qbnox-smtp/v1/oauth/start',
			  method: 'POST',
			  data: { provider }
		  }).then(res => {
			  if (res.url) {
				  window.location.href = res.url;
			  }
		  }).catch(err => {
			  alert('OAuth start failed');
			  console.error(err);
		  });
     };

     const disconnectOAuth = () => {
		  wp.apiFetch({
			  path: '/qbnox-smtp/v1/oauth/disconnect',
			  method: 'POST'
		  }).then(() => {
			  window.location.reload();
		  });
     };

useEffect(() => {
  wp.apiFetch({ path: '/qbnox-smtp/v1/oauth/status' })
    .then(res => {
      setSettings(s => ({
        ...s,
        oauth_connected: res.connected,
        oauth_provider: res.provider
      }));
    });
}, []);

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
      setCfg(prev => {
        const next = JSON.parse(JSON.stringify(prev));
        next.smtp[field] = value;
        return next;
      });
    };

    /**
     * Apply provider preset (single atomic update)
     */
    const applyProvider = provider => {
      setCfg(prev => {
        const next = JSON.parse(JSON.stringify(prev));
        next.smtp.provider = provider;

        const preset = PROVIDERS[provider];
        if (preset && preset.host) {
          next.smtp.host       = preset.host;
          next.smtp.port       = preset.port;
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
        data: cfg
      }).then(() => setSaving(false));
    };

    const testMail = () => {
      apiFetch({
        path: '/qbnox-smtp/v1/test-mail',
        method: 'POST'
      }).then(res => {
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
        ['smtp', 'oauth', 'analytics'].map(t =>
          createElement(
            'div',
            {
              key: t,
              className: 'qbnox-tab ' + (tab === t ? 'active' : ''),
              onClick: () => setTab(t)
            },
            t === 'smtp'
              ? 'SMTP Settings'
              : t === 'oauth'
              ? 'OAuth (Coming Soon)'
              : 'Analytics'
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
                onChange: e => applyProvider(e.target.value)
              },
              Object.keys(PROVIDERS).map(p =>
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
              onChange: e => update('host', e.target.value)
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
              onChange: e => update('port', e.target.value)
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
                onChange: e => update('encryption', e.target.value)
              },
              ['tls', 'ssl', 'none'].map(v =>
                createElement(
                  'option',
                  { key: v, value: v },
                  v.toUpperCase()
                )
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
              onChange: e => update('username', e.target.value)
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
                onChange: e => update('password', e.target.value)
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
                'aria-label': showPass
                  ? 'Hide password'
                  : 'Show password',
                onClick: () => setShowPass(!showPass)
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
              onChange: e => update('from_email', e.target.value)
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
              onChange: e => update('from_name', e.target.value)
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
                disabled: saving
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

      /* OAuth TAB */
/* OAuth TAB */
tab === 'oauth' &&
  createElement(
    'div',
    { className: 'qbnox-oauth-tab' },

    createElement(
      'p',
      null,
      'Connect your Google or Microsoft account to enable OAuth.'
    ),

    createElement(
      'button',
      {
        className: 'button button-primary',
        onClick: () => {
          wp.apiFetch({
            path: '/qbnox-smtp/v1/oauth/start',
            method: 'POST',
            data: { provider: 'google' }
          }).then(res => {
            if (res.url) {
              window.location.href = res.url;
            }
          }).catch(err => {
            console.error(err);
            alert('Failed to start Google OAuth');
          });
        }
      },
      'Connect with Google'
    ),

    createElement(
      'button',
      {
        className: 'button',
        style: { marginLeft: '10px' },
        onClick: () => {
          wp.apiFetch({
            path: '/qbnox-smtp/v1/oauth/start',
            method: 'POST',
            data: { provider: 'microsoft' }
          }).then(res => {
            if (res.url) {
              window.location.href = res.url;
            }
          }).catch(err => {
            console.error(err);
            alert('Failed to start Microsoft OAuth');
          });
        }
      },
      'Connect with Microsoft'
    )
  ),

  createElement(
  'div',
  { style: { marginTop: '15px' } },

  createElement(
    'button',
    {
      className: 'button',
      onClick: () => {
        wp.apiFetch({
          path: '/qbnox-smtp/v1/oauth/status',
          method: 'GET'
        }).then(res => {
          if (res.connected) {
            alert(
              'OAuth OK (' + res.provider + ')\nExpires in: ' +
              res.expires_in + ' seconds'
            );
          } else {
            alert('OAuth error: ' + res.error);
          }
        }).catch(err => {
          console.error(err);
          alert('Failed to check OAuth status');
        });
      }
    },
    'Test OAuth Connection'
  )
),

      /* Analytics TAB */
      tab === 'analytics' &&
        createElement(
          'p',
          null,
          'Delivery analytics are collected via provider webhooks.'
        )
    );
  }

  wp.element.render(
    createElement(App),
    document.getElementById('qbnox-smtp-root')
  );
})(window.wp);

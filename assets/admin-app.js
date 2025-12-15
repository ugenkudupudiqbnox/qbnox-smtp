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
    const [oauthStatus, setOauthStatus] = useState(null);
    const [tab, setTab] = useState('smtp');
    const [saving, setSaving] = useState(false);
    const [showPass, setShowPass] = useState(false);
    const [health, setHealth] = useState(null);

    useEffect(() => {
      apiFetch({ path: '/qbnox-smtp/v1/settings' }).then(setCfg);
    }, []);

    useEffect(() => {
      if (tab === 'oauth') {
        apiFetch({ path: '/qbnox-smtp/v1/oauth/status' }).then(setOauthStatus);
      }
    }, [tab]);

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
      (function (wp) {
        const { createElement, useState, useEffect } = wp.element;
        const apiFetch = wp.apiFetch;

        const PROVIDERS = {
          custom: { label: 'Custom SMTP' },
          brevo: { label: 'Brevo', host: 'smtp-relay.brevo.com', port: 587, encryption: 'tls' },
          postmark: { label: 'Postmark', host: 'smtp.postmarkapp.com', port: 587, encryption: 'tls' },
          ses: { label: 'Amazon SES', host: 'email-smtp.us-east-1.amazonaws.com', port: 587, encryption: 'tls' },
        };

        function App() {
          const [cfg, setCfg] = useState(null);
          const [oauthStatus, setOauthStatus] = useState(null);
          const [tab, setTab] = useState('smtp');
          const [saving, setSaving] = useState(false);
          const [showPass, setShowPass] = useState(false);
          const [health, setHealth] = useState(null);

          useEffect(() => {
            apiFetch({ path: '/qbnox-smtp/v1/settings' }).then(setCfg);
          }, []);

          useEffect(() => {
            if (tab === 'oauth') {
              apiFetch({ path: '/qbnox-smtp/v1/oauth/status' }).then(setOauthStatus);
            }
          }, [tab]);

          if (!cfg) return createElement('p', null, 'Loading…');

          const update = (field, value) => setCfg((prev) => ({ ...prev, smtp: { ...prev.smtp, [field]: value } }));

          const applyProvider = (provider) => setCfg((prev) => {
            const next = JSON.parse(JSON.stringify(prev));
            next.smtp.provider = provider;
            const preset = PROVIDERS[provider];
            if (preset && preset.host) {
              next.smtp.host = preset.host; next.smtp.port = preset.port; next.smtp.encryption = preset.encryption;
            }
            return next;
          });

          const save = () => { setSaving(true); apiFetch({ path: '/qbnox-smtp/v1/settings', method: 'POST', data: cfg }).then(() => setSaving(false)); };

          const testMail = () => {
            apiFetch({ path: '/qbnox-smtp/v1/test-send', method: 'POST' }).then((res) => {
              if (!res) return alert('No response');
              if (res.status === 'sent') { setHealth('ok'); alert(`✅ Test sent via: ${res.method}`); }
              else { setHealth('bad'); alert(`❌ Test failed (${res.method || 'unknown'}): ${res.message || JSON.stringify(res)}`); }
            }).catch(() => alert('Request failed'));
          };

          return createElement('div', { className: 'qbnox-card' },
            createElement('div', { className: 'qbnox-tabs' }, ['smtp','oauth','license','aboutus'].map((t) => createElement('div', { key: t, className: 'qbnox-tab '+(tab===t?'active':''), onClick: ()=>setTab(t) }, t==='smtp'?'SMTP Settings':t==='oauth'?'OAuth':t==='license'?'MIT License':'About Us'))),

            tab==='smtp' && createElement('div', null,
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'PROVIDER'), createElement('select',{value:cfg.smtp?.provider||'custom', onChange:(e)=>applyProvider(e.target.value)}, Object.keys(PROVIDERS).map(p=>createElement('option',{key:p,value:p},PROVIDERS[p].label)))) ,
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'SMTP HOST'), createElement('input',{type:'text', value:cfg.smtp?.host||'', onChange:(e)=>update('host',e.target.value)})),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'PORT'), createElement('input',{type:'number', value:cfg.smtp?.port||587, onChange:(e)=>update('port',e.target.value)})),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'ENCRYPTION'), createElement('select',{value:cfg.smtp?.encryption||'tls', onChange:(e)=>update('encryption',e.target.value)}, ['tls','ssl','none'].map(v=>createElement('option',{key:v,value:v},v.toUpperCase())))),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'USERNAME'), createElement('input',{type:'text', value:cfg.smtp?.username||'', onChange:(e)=>update('username',e.target.value)})),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'PASSWORD'), createElement('div',{className:'qbnox-password-wrap'}, createElement('input',{type: showPass? 'text':'password', value:cfg.smtp?.password||'', onChange:(e)=>update('password',e.target.value)}), createElement('span',{className:'dashicons '+(showPass?'dashicons-visibility open':'dashicons-hidden closed')+' qbnox-eye', title: showPass? 'Hide password':'Show password', role:'button', 'aria-label': showPass? 'Hide password':'Show password', onClick: ()=>setShowPass(!showPass)}))),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'FROM EMAIL'), createElement('input',{type:'text', value:cfg.smtp?.from_email||'', onChange:(e)=>update('from_email',e.target.value)})),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'FROM NAME'), createElement('input',{type:'text', value:cfg.smtp?.from_name||'', onChange:(e)=>update('from_name',e.target.value)})),
              createElement('div',{className:'qbnox-actions'}, createElement('button',{className:'button button-primary', onClick:save, disabled:saving}, saving? 'Saving…':'Save Settings'), ' ', createElement('button',{className:'button', onClick:testMail}, 'Send Test Email'), health && createElement('span',{className:'qbnox-health '+health}, health==='ok'?'● Healthy':'● Error'))
            ),

            tab==='oauth' && createElement('div', null,
              createElement('h2', null, 'OAuth Configuration'),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'Provider'), createElement('select',{value:cfg.oauth?.provider||'', onChange:(e)=>setCfg((prev)=>({ ...prev, oauth: { ...prev.oauth, provider: e.target.value } }))}, createElement('option',{value:''},'None'), createElement('option',{value:'google'},'Google Workspace (Gmail API)'), createElement('option',{value:'microsoft'},'Microsoft 365 (Graph API)'))),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'Client ID'), createElement('input',{type:'text', value:cfg.oauth?.client_id||'', onChange:(e)=>setCfg((prev)=>({ ...prev, oauth:{ ...prev.oauth, client_id: e.target.value } }))})),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'Client Secret'), createElement('input',{type:'text', value:cfg.oauth?.client_secret||'', onChange:(e)=>setCfg((prev)=>({ ...prev, oauth:{ ...prev.oauth, client_secret: e.target.value } }))})),
              createElement('div',{className:'qbnox-field'}, createElement('label',null,'Service Account / From Email'), createElement('input',{type:'text', value:cfg.oauth?.email||'', onChange:(e)=>setCfg((prev)=>({ ...prev, oauth:{ ...prev.oauth, email: e.target.value } }))})),

              // Redirect URI display + help links
              createElement('div', { className: 'qbnox-field' },
                createElement('label', null, 'Redirect URI (copy into provider OAuth settings)'),
                (function () {
                  const restRoot = (typeof wpApiSettings !== 'undefined' && wpApiSettings.root) ? wpApiSettings.root : '/wp-json/';
                  const redirect = window.location.origin + restRoot + 'qbnox-smtp/v1/oauth/callback';
                  return createElement('div', null,
                    createElement('input', { type: 'text', readOnly: true, value: redirect, style: { width: '70%' } }),
                    ' ',
                    createElement('button', { className: 'button', onClick: () => { navigator.clipboard && navigator.clipboard.writeText(redirect); alert('Redirect URI copied to clipboard'); } }, 'Copy')
                  );
                })()
              ),

              createElement('div', { className: 'qbnox-field' },
                createElement('label', null, 'Provider Setup Help'),
                createElement('div', null,
                  createElement('a', { href: 'https://developers.google.com/identity/protocols/oauth2/web-server', target: '_blank', rel: 'noopener noreferrer' }, 'Google: Configure OAuth 2.0 (Web server)'),
                  ' — ',
                  createElement('a', { href: 'https://developers.google.com/identity/protocols/oauth2/limited-input-device#creatingcred', target: '_blank', rel: 'noopener noreferrer' }, 'Google: Create OAuth credentials')
                ),
                createElement('div', null,
                  createElement('a', { href: 'https://learn.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app', target: '_blank', rel: 'noopener noreferrer' }, 'Microsoft: Register an app'),
                  ' — ',
                  createElement('a', { href: 'https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-protocols-oidc', target: '_blank', rel: 'noopener noreferrer' }, 'Microsoft: OAuth 2.0 / OIDC details')
                )
              ),
              createElement('div',{className:'qbnox-actions'}, createElement('button',{className:'button button-primary', onClick: ()=>{ setSaving(true); apiFetch({ path: '/qbnox-smtp/v1/settings', method: 'POST', data: cfg }).then(()=>setSaving(false)); }, disabled: saving}, saving? 'Saving…':'Save OAuth Config'), ' ', createElement('button',{className:'button', onClick: ()=>{ const provider = cfg.oauth?.provider || ''; if(!provider){ alert('Select a provider first'); return; } apiFetch({ path: '/qbnox-smtp/v1/oauth/start', method: 'POST', data: { provider } }).then((res)=>{ if(res && res.url){ window.open(res.url, '_blank'); alert('Authorization started in a new tab. After granting consent, return here and click Refresh Status.'); } else { alert('Failed to start OAuth flow'); } }); } }, 'Start OAuth Flow'), ' ', createElement('button',{className:'button', onClick: ()=>{ if(!confirm('Disconnect and remove stored OAuth tokens?')) return; apiFetch({ path: '/qbnox-smtp/v1/oauth/disconnect', method: 'POST' }).then(()=>{ alert('Disconnected'); setOauthStatus(null); }); } }, 'Disconnect')),
              oauthStatus && createElement('div',{style:{marginTop:'12px'}}, createElement('strong',null,'Connection:'), ' ', oauthStatus.oauth? createElement('span', null, `Connected as ${oauthStatus.oauth.email || 'unknown'} — expires ${new Date((oauthStatus.oauth.expires_at || 0) * 1000).toLocaleString()}`) : createElement('span', null, 'Not connected'), ' ', createElement('button',{className:'button', onClick: ()=>apiFetch({ path: '/qbnox-smtp/v1/oauth/status' }).then(setOauthStatus), style:{marginLeft:'8px'}}, 'Refresh Status'), oauthStatus.last_error && createElement('div',{style:{marginTop:'8px', color:'#a00'}}, createElement('strong', null, 'Last refresh error:'), createElement('pre', null, JSON.stringify(oauthStatus.last_error, null, 2))))
            ),

            tab==='license' && createElement('div',{className:'qbnox-license'}, createElement('h2',null,'MIT License'), createElement('p',null,'This plugin is released under the MIT License, a permissive open-source license that allows free use, modification, and distribution.'), createElement('pre',{style:{background:'#f6f7f7', padding:'12px', maxHeight:'300px', overflow:'auto', whiteSpace:'pre-wrap', border:'1px solid #ccd0d4'}}, `MIT License\n\nCopyright (c) 2025 Qbnox Systems Private Limited\n\nPermission is hereby granted, free of charge, to any person obtaining a copy\nof this software and associated documentation files (the "Software"), to deal\nin the Software without restriction, including without limitation the rights\nto use, copy, modify, merge, publish, distribute, sublicense, and/or sell\ncopies of the Software, and to permit persons to whom the Software is\nfurnished to do so, subject to the following conditions:\n\nThe above copyright notice and this permission notice shall be included in\nall copies or substantial portions of the Software.\n\nTHE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR\nIMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,\nFITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE\nAUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER\nLIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,\nOUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN\nTHE SOFTWARE.`), createElement('p',{style:{marginTop:'12px', fontStyle:'italic'}}, 'A copy of this license is also included in the plugin source code.')),

            tab==='aboutus' && createElement('div',{className:'qbnox-about'}, createElement('h2',null,'About Qbnox Systems'), createElement('p',null,'Qbnox Systems is a technology services company specializing in managed hosting, infrastructure operations, and enterprise support for open-source platforms.'), createElement('p',null,'We provide secure, scalable, and compliant hosting solutions for Moodle, Pressbooks, WordPress, and a wide range of other open-source software used by universities, government institutions, and enterprises.'), createElement('p',null,'Our focus is on reliability, security, and long-term maintainability, enabling institutions to concentrate on teaching, research, and digital innovation while we manage the underlying platform and infrastructure.'), createElement('h3',null,'Contact Information'), createElement('ul',{className:'qbnox-contact-list'}, createElement('li',null, createElement('strong', null, 'Contact Person: '), 'Ugendreshwar Kudupudi'), createElement('li',null, createElement('strong', null, 'Email: '), createElement('a',{href:'mailto:ugen@qbnox.com'}, 'ugen@qbnox.com')), createElement('li',null, createElement('strong', null, 'Phone: '), '+91 90085 11933'), createElement('li',null, createElement('strong', null, 'Website: '), createElement('a',{href:'https://www.qbnox.com', target:'_blank', rel:'noopener noreferrer'}, 'https://www.qbnox.com')), createElement('li',null, createElement('strong', null, 'GitHub: '), createElement('a',{href:'https://github.com/ugenkudupudiqbnox/qbnox-smtp', target:'_blank', rel:'noopener noreferrer'}, 'https://github.com/ugenkudupudiqbnox/qbnox-smtp'))), createElement('p',{style:{marginTop:'16px', fontStyle:'italic'}}, 'For enterprise support, managed hosting, compliance requirements, or security-related queries, please reach out using the contact details above.')),

            createElement('div',{style:{marginTop:'20px'}}, createElement('a',{href:'https://github.com/ugenkudupudiqbnox/qbnox-smtp/issues/new', target:'_blank', rel:'noopener noreferrer', className:'button button-primary', style:{marginRight:'10px'}}, 'Report Issue on GitHub'))
          );
        }

        wp.element.render(createElement(App), document.getElementById('qbnox-smtp-root'));
      })(window.wp);

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

( function( wp ) {
    const { createElement, useState, useEffect } = wp.element;
    const apiFetch = wp.apiFetch;

    function Analytics() {
        const [data, setData] = useState([]);
        const [loading, setLoading] = useState(true);

        useEffect(() => {
            apiFetch({ path: '/qbnox-smtp/v1/analytics' })
                .then(res => {
                    setData(res || []);
                    setLoading(false);
                })
                .catch(() => setLoading(false));
        }, []);

        if (loading) {
            return createElement('p', null, 'Loading analytics…');
        }

        return createElement(
            'table',
            { className: 'widefat striped' },
            createElement(
                'thead',
                null,
                createElement('tr', null,
                    createElement('th', null, 'Event'),
                    createElement('th', null, 'Count')
                )
            ),
            createElement(
                'tbody',
                null,
                data.map(row =>
                    createElement('tr', { key: row.event },
                        createElement('td', null, row.event),
                        createElement('td', null, row.total)
                    )
                )
            )
        );
    }

    function App() {
        return createElement(
            'div',
            { className: 'qbnox-card' },
            createElement('h2', null, 'Email Delivery Analytics'),
            createElement(
                'p',
                null,
                'Live delivery, bounce, complaint, open and click events.'
            ),
            createElement(Analytics, null)
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('qbnox-smtp-root');
        if (root) {
            wp.element.render(createElement(App), root);
        }
    });

})( window.wp );
import React from 'react';
import ReactDOM from 'react-dom';
import GraphiQL from 'graphiql';

import "graphiql/graphiql.css"

const App = () => (
    <GraphiQL
        fetcher={async graphQLParams => {
            const data = await fetch(
                '/graphql',
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(graphQLParams),
                    credentials: 'same-origin',
                },
            );
            return data.json().catch(() => data.text());
        }}
    />
);

ReactDOM.render(<App/>, document.getElementById('root'));

// Hot Module Replacement
// @ts-ignore
if (typeof module !== 'undefined' && module.hot) {
    // @ts-ignore
    module.hot.accept();
}
<?php
namespace Leoloso\GraphQLByPoPWPPlugin\Admin;

use Leoloso\GraphQLByPoPWPPlugin\Admin\AbstractMenuPage;

/**
 * GraphiQL page
 */
class GraphiQLPage extends AbstractMenuPage {

    public function print(): void
    {
        ?>
        <div id="graphiql" style="height: 100vh;"></div>

        <!--script
        crossorigin
        src="https://unpkg.com/react/umd/react.production.min.js"
        ></script-->
        <!--script
        crossorigin
        src="https://unpkg.com/react-dom/umd/react-dom.production.min.js"
        ></script-->
        <!--script
        crossorigin
        src="https://unpkg.com/graphiql/graphiql.min.js"
        ></script-->

        <div id="graphiql">Loading...</div>
        <!--script defer src="graphiql.js?version=1.1.0" type="application/javascript"></script-->
        <script defer type="application/javascript">
/**
 * This GraphiQL example illustrates how to use some of GraphiQL's props
 * in order to enable reading and updating the URL parameters, making
 * link sharing of queries a little bit easier.
 *
 * This is only one example of this kind of feature, GraphiQL exposes
 * various React params to enable interesting integrations.
 */

// Parse the search string to get url parameters.
var search = window.location.search;
var parameters = {};
search
  .substr(1)
  .split('&')
  .forEach(function(entry) {
    var eq = entry.indexOf('=');
    if (eq >= 0) {
      parameters[decodeURIComponent(entry.slice(0, eq))] = decodeURIComponent(
        entry.slice(eq + 1),
      );
    }
  });

// When the query and variables string is edited, update the URL bar so
// that it can be easily shared.
function onEditQuery(newQuery) {
  parameters.query = newQuery;
  updateURL();
}

function onEditVariables(newVariables) {
  parameters.variables = newVariables;
  updateURL();
}

function onEditOperationName(newOperationName) {
  parameters.operationName = newOperationName;
  updateURL();
}

function updateURL() {
  var newSearch =
    '?' +
    Object.keys(parameters)
      .filter(function(key) {
        return Boolean(parameters[key]);
      })
      .map(function(key) {
        return (
          encodeURIComponent(key) + '=' + encodeURIComponent(parameters[key])
        );
      })
      .join('&');
  history.replaceState(null, null, newSearch);
}

/*
 * Converts a string to a bool.
 *
 * This conversion will:
 *
 *  - match 'true', 'on', or '1' as true.
 *  - ignore all white-space padding
 *  - ignore capitalization (case).
 *
 * '  tRue  ','ON', and '1   ' will all evaluate as true.
 *
 * Taken from https://stackoverflow.com/a/264180
 *
 */
function strToBool(s)
{
    // will match one and only one of the string 'true','1', or 'on' rerardless
    // of capitalization and regardless off surrounding white-space.
    //
    regex=/^\s*(true|1|on)\s*$/i
    return regex.test(s);
}

// Defines a GraphQL fetcher using the fetch API. You're not required to
// use fetch, and could instead implement graphQLFetcher however you like,
// as long as it returns a Promise or Observable.
function graphQLFetcher(graphQLParams) {
  let nonce = (window.graphQLByPoPGraphiQLSettings && window.graphQLByPoPGraphiQLSettings.nonce) ? window.graphQLByPoPGraphiQLSettings.nonce : null;
  let apiURL = (window.graphQLByPoPGraphiQLSettings && window.graphQLByPoPGraphiQLSettings.endpoint) ? window.graphQLByPoPGraphiQLSettings.endpoint : window.location.origin;

  // Copy parameters
  if (parameters.use_namespace && strToBool(parameters.use_namespace)) {
    apiURL += '&use_namespace=true';
  }
  return fetch(apiURL, {
    method: 'post',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce
    },
    body: JSON.stringify(graphQLParams),
    credentials: 'include',
  })
    .then(function(response) {
      return response.text();
    })
    .then(function(responseBody) {
      try {
        return JSON.parse(responseBody);
      } catch (error) {
        return responseBody;
      }
    });
}

// Render <GraphiQL /> into the body.
// See the README in the top level of this module to learn more about
// how you can customize GraphiQL by providing different values or
// additional child elements.
ReactDOM.render(
  React.createElement(GraphiQL, {
    fetcher: graphQLFetcher,
    query: parameters.query,
    variables: parameters.variables,
    operationName: parameters.operationName,
    onEditQuery: onEditQuery,
    onEditVariables: onEditVariables,
    // defaultVariableEditorOpen: true,
    // docExplorerOpen: true,
    onEditOperationName: onEditOperationName,
    response: "Click the \"Execute Query\" button, or press Ctrl+Enter (Command+Enter in Mac)"
  }),
  document.getElementById('graphiql'),
);

        </script>
        <?php
    }

    public function init(): void
    {

    }
}

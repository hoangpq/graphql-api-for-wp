<?php
namespace Leoloso\GraphQLByPoPWPPlugin\Blocks;

use Leoloso\GraphQLByPoPWPPlugin\General\URLParamHelpers;

/**
 * GraphiQL block
 */
class GraphiQLBlock extends AbstractGraphQLByPoPBlock
{
    protected function getBlockName(): string
    {
        return 'graphiql';
    }

    protected function isDynamicBlock(): bool
    {
        return true;
    }

    public function renderBlock($attributes, $content): string
	{
		$content = sprintf(
            '<div class="%s">',
            $this->getBlockClassName()
        );
		$query = $attributes['query'];
		$variables = $attributes['variables'];
		if (true) {
			$url = 'http://playground.localhost:8888/graphiql/';
			// We need to reproduce the `encodeURIComponent` JavaScript function, because that's how the GraphiQL client adds the parameters to the URL
			// Important! We can't use function `add_query_arg` because it re-encodes the URL!
			// So build the URL manually
			$url .= (strpos($url, '?') === false ? '?' : '&').'query='.URLParamHelpers::encodeURIComponent($query);
			// Add variables parameter always (empty if no variables defined), so that GraphiQL doesn't use a cached one
			$url .= '&variables='.($variables ? URLParamHelpers::encodeURIComponent($variables) : '');
			$content .= sprintf(
				'<p><a href="%s">%s</a></p>',
				$url,
				__('View query in GraphiQL', 'graphql-by-pop')
			);
		}
		$content .= sprintf(
			'<p><strong>%s</strong></p>',
			__('GraphQL Query:', 'graphql-by-pop')
		).sprintf(
			'<pre><code class="language-graphql">%s</code></pre>',
			$query
		);
		if ($variables) {
			$content .= sprintf(
				'<p><strong>%s</strong></p>',
				__('Variables:', 'graphql-by-pop')
			).sprintf(
				'<pre><code class="language-json">%s</code></pre>',
				$variables
			);
		}
		$content .= '</div>';
		return $content;
	}
}
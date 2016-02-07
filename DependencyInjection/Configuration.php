<?php

namespace freefair\ResourceBundleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();

		$rootNode = $treeBuilder->root('resource_bundle');

		$rootNode
			->children()
				->scalarNode("asset_dir")->end()
				->scalarNode("bower_dir")->end()
				->arrayNode("bundles")
					->useAttributeAsKey("bundle")
					->prototype("array")
						->children()
							->booleanNode("minify")->defaultTrue()->end()
							->booleanNode("debug")->defaultFalse()->end()
							->scalarNode("name")->defaultNull()->end()
							->scalarNode("type")->end()
							->arrayNode("files")
								->prototype("scalar")->end()
							->end()
						->end()
					->end()
				->end()
			->end()
		;

		return $treeBuilder;
	}
}

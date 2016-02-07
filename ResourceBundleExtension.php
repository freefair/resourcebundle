<?php
namespace freefair\ResourceBundleBundle;

use freefair\ResourceBundleBundle\Controller\DefaultController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResourceBundleExtension extends \Twig_Extension
{
	private static $mimetype_element_map = array(
		"text/javascript" => "<script type=\"text/javascript\" src=\"%url%\"></script>",
		"text/css" => "<link rel=\"stylesheet\" type=\"text/css\" href=\"%url%\" />"
	);

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @var UrlGeneratorInterface
	 */
	private $url;

	public function __construct(ContainerInterface $container, UrlGeneratorInterface $url){
		$this->container = $container;
		$this->url = $url;
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction("renderBundle", array($this, "renderBundle"))
		);
	}

	public function getName()
	{
		return "resource_bundle";
	}

	public function renderBundle($name) {
		$conf = $this->container->getParameter("resource_bundle.config");
		$bundle = $this->container->getParameter("resource_bundle.config.bundles");
		$bundle = $bundle[$name];
		if($bundle["debug"])
		{
			$files = DefaultController::getFilesFromBundle($conf["bower_dir"], $conf["asset_dir"], $bundle, DefaultController::$mimetype_file_mapping[$bundle["type"]]);
			foreach($files as $file)
			{
				$this->renderFile($file, $bundle["type"]);
			}
		}
		else {
			$this->renderFile($this->url->generate("resource_bundle_bundle") . "?bundlename=$name", $bundle["type"]);
		}
	}

	private function renderFile($url, $type){
		echo str_replace("%url%", $url, self::$mimetype_element_map[$type]);
	}
}

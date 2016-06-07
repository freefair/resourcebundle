<?php

namespace freefair\ResourceBundleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\CSS;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

define("DIR_SEP", "/");

class DefaultController extends Controller
{
	public static $mimetype_file_mapping = array("text/javascript" => ".js", "text/css" => ".css");
	private static $mimetype_minifier_mapping = array("text/javascript" => JS::class, "text/css" => CSS::class);

	private static function normalizeFilename($f)
	{
		if(strpos($f, "./") === 0)
			return substr($f, 2);
		return $f;
	}

	private static function scanDir($dir){
		$result = array();
		$dh = opendir($dir);

		while($file = readdir($dh)) {
			if($file == "." || $file == "..") continue;
			if(is_dir($dir . DIR_SEP . $file)) {
				$result = array_merge($result, self::scanDir($dir . DIR_SEP . $file));
			} else {
				$result[] = $dir . DIR_SEP . $file;
			}
		}

		closedir($dh);
		return $result;
	}

	private static $list = null;
	private static function getFileList($basedir) {
		if(self::$list == null) {
			self::$list = self::scanDir($basedir);
		}
		return self::$list;
	}

	private static function findFiles($regex, $basedir)
	{
		if(strpos($regex, "regex:") === 0) $regex = substr($regex, 6);
		else $regex = str_replace("*", "([^/]*)", str_replace("**", "(.+)", $regex));

		$regex = "~$basedir" . DIR_SEP . "$regex~";

		$fileList = self::getFileList($basedir);
		$result = array();
		foreach($fileList as $file) {
			if(preg_match($regex, $file))
				$result[] = $file;
		}
		return $result;
	}

	/**
	 * @param $bowerDir
	 * @param $baseDir
	 * @param $bundle
	 * @param $fileending
	 * @return array|mixed
	 */
	public static function getFilesFromBundle($bowerDir, $baseDir, $bundle, $fileending)
	{
		$files = array_map(function ($f) use ($bowerDir, $baseDir) {
			$explode = explode(":", $f, 2);
			if (count($explode) == 1)
				$explode = array("file", $explode[0]);


			if ($explode[0] == "bower") {
				$directory = $bowerDir . DIR_SEP . $explode[1] . DIR_SEP;
				$bowerConfig = json_decode(file_get_contents($directory . ".bower.json"));
				if (is_array($bowerConfig->main)) {
					return array_map(function ($v) use ($directory) {
						return $directory . self::normalizeFilename($v);
					}, $bowerConfig->main);
				} else {
					return array($directory . self::normalizeFilename($bowerConfig->main));
				}
			} else if ($explode[0] == "file") {
				return self::findFiles($explode[1], $baseDir);
			}

			return array($baseDir . DIR_SEP . $f);
		}, $bundle["files"]);
		$files = call_user_func_array('array_merge', $files);
		$files = array_filter($files, function ($value) use ($fileending) {
			return strpos($value, $fileending) == strlen($value) - strlen($fileending);
		});
		return $files;
	}

	private function getCacheDirectory() {
		$filename = $this->container->getParameter('kernel.cache_dir') . '/resource_bundle/';

		$fs = new Filesystem();
		try {
			if(!$fs->exists($filename))
				$fs->mkdir($filename);
		} catch (IOException $e) {
			echo "An error occured while creating your directory";
		}
		return $filename;
	}

	private function getCacheFile($filename){
		$filename = $this->getCacheDirectory() . $filename;
		return $filename;
	}

	private function cacheFileExists($filename){
		$fs = new Filesystem();
		return $fs->exists($filename);
	}

    public function bundleAction() {
	    $request = Request::createFromGlobals();
	    $bundlename = $request->get("bundlename");
	    $cacheFile = $this->getCacheFile($bundlename);
	    $bundle = $this->container->getParameter("resource_bundle.config.bundles")[$bundlename];
	    $filetype = $bundle["type"];

	    if($this->cacheFileExists($cacheFile) && !$bundle["debug"])
		    $content = file_get_contents($cacheFile);
	    else {
		    $obj = $this->container->getParameter("resource_bundle.config");
		    $baseDir = $obj["asset_dir"];
		    $bowerDir = $obj["bower_dir"];

		    $fileending = self::$mimetype_file_mapping[$filetype];
		    $files = self::getFilesFromBundle($bowerDir, $baseDir, $bundle, $fileending);

		    $content = "";
		    foreach ($files as $file) {
			    if($bundle["debug"])
				    $content .= "/* -------- FILE: $file -------- */\n";
			    $content .= file_get_contents($file) . "\n\n";
		    }

		    if ($bundle["minify"] && !$bundle["debug"]) {
			    $minifier = self::$mimetype_minifier_mapping[$filetype];
			    $obj = new $minifier();
			    $obj->add($content);
			    $content = $obj->execute();
		    }

		    file_put_contents($cacheFile, $content);
	    }

        return new Response($content, 200, array('content-type' => $filetype));
    }
}


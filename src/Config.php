<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Cecil\Exception\Exception;
use Dflydev\DotAccessData\Data;

/**
 * Class Config.
 */
class Config
{
    /**
     * Config.
     *
     * @var Data
     */
    protected $data;
    /**
     * Source directory.
     *
     * @var string
     */
    protected $sourceDir;
    /**
     * Destination directory.
     *
     * @var string
     */
    protected $destinationDir;

    /**
     * Config constructor.
     *
     * @param Config|array|null $config
     */
    public function __construct($config = null)
    {
        $data = new Data(include __DIR__.'/../config/default.php');

        if ($config) {
            if ($config instanceof self) {
                $data->importData($config->getAll());
            } elseif (is_array($config)) {
                $data->import($config);
            }
        }

        /**
         * Apply environment variables.
         */
        $applyEnv = function ($array) use ($data) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($array),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $leafValue) {
                $path = [];
                foreach (range(0, $iterator->getDepth()) as $depth) {
                    $path[] = $iterator->getSubIterator($depth)->key();
                }
                $sPath = implode('_', $path);
                if ($getEnv = getenv('CECIL_'.strtoupper($sPath))) {
                    $data->set(str_replace('_', '.', strtolower($sPath)), $getEnv);
                }
            }
        };
        $applyEnv($data->export());

        $this->setFromData($data);
    }

    /**
     * Import array config to current config.
     *
     * @param array $config
     */
    public function import($config)
    {
        if (is_array($config)) {
            $data = $this->getAll();
            $origin = $data->export();
            $data->import($config);
            $data->import($origin);
            $this->setFromData($data);
        }
    }

    /**
     * Set config data.
     *
     * @param Data $data
     *
     * @return $this
     */
    protected function setFromData(Data $data)
    {
        if ($this->data !== $data) {
            $this->data = $data;
        }

        return $this;
    }

    /**
     * Get config data.
     *
     * @return Data
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Get data as array.
     *
     * @return array
     */
    public function getAllAsArray()
    {
        return $this->data->export();
    }

    /**
     * Return a config value.
     *
     * @param string $key
     * @param string $default
     *
     * @return array|mixed|null
     */
    public function get($key, $default = '')
    {
        return $this->data->get($key, $default);
    }

    /**
     * Set source directory.
     *
     * @param null $sourceDir
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setSourceDir($sourceDir = null)
    {
        if ($sourceDir === null) {
            $sourceDir = getcwd();
        }
        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException(sprintf("'%s' is not a valid source directory.", $sourceDir));
        }
        $this->sourceDir = $sourceDir;

        return $this;
    }

    /**
     * Get source directory.
     *
     * @return string
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * Set destination directory.
     *
     * @param null $destinationDir
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setDestinationDir($destinationDir = null)
    {
        if ($destinationDir === null) {
            $destinationDir = $this->sourceDir;
        }
        if (!is_dir($destinationDir)) {
            throw new \InvalidArgumentException(sprintf("'%s' is not a valid destination directory.", $destinationDir));
        }
        $this->destinationDir = $destinationDir;

        return $this;
    }

    /**
     * Get destination directory.
     *
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * Path helpers.
     */

    /**
     * Return content directory path.
     *
     * @return string
     */
    public function getContentPath()
    {
        return $this->getSourceDir().'/'.$this->get('content.dir');
    }

    /**
     * Return templates directory path.
     *
     * @return string
     */
    public function getLayoutsPath()
    {
        return $this->getSourceDir().'/'.$this->get('layouts.dir');
    }

    /**
     * Return themes directory path.
     *
     * @return string
     */
    public function getThemesPath()
    {
        return $this->getSourceDir().'/'.$this->get('themes.dir');
    }

    /**
     * Return internal templates directory path.
     *
     * @return string
     */
    public function getInternalLayoutsPath()
    {
        return __DIR__.'/../'.$this->get('layouts.internal.dir');
    }

    /**
     * Return output directory path.
     *
     * @return string
     */
    public function getOutputPath()
    {
        return $this->getSourceDir().'/'.$this->get('output.dir');
    }

    /**
     * Return static files directory path.
     *
     * @return string
     */
    public function getStaticPath()
    {
        return $this->getSourceDir().'/'.$this->get('static.dir');
    }

    /**
     * Themes helpers.
     */

    /**
     * Return theme(s).
     *
     * @return array|null
     */
    public function getTheme()
    {
        if ($themes = $this->get('theme')) {
            if (is_array($themes)) {
                return $themes;
            }

            return [$themes];
        }
    }

    /**
     * Has a (valid) theme(s)?
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasTheme()
    {
        if ($themes = $this->getTheme()) {
            foreach ($themes as $theme) {
                if (!Util::getFS()->exists($this->getThemeDirPath($theme, 'layouts'))) {
                    throw new Exception(sprintf(
                        "Theme directory '%s/%s/layouts' not found!",
                        $this->getThemesPath(),
                        $theme
                    ));
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Return the path of a specific theme's directory.
     *
     * @param string $theme
     * @param string $dir
     *
     * @return string
     */
    public function getThemeDirPath($theme, $dir = 'layouts')
    {
        return $this->getThemesPath().'/'.$theme.'/'.$dir;
    }
}

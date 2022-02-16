<?php

namespace LaravelReady\ThemeManager\Services;

use PhpZip\ZipFile;
use Illuminate\Support\Str;
use PhpZip\Exception\ZipException;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

use PhpZip\Constants\ZipCompressionMethod;
use LaravelReady\ThemeManager\Support\ThemeSupport;
use LaravelReady\ThemeManager\Services\ThemeManager;

class Theme
{
    public $alias;

    public $name;
    public $description;
    public $vendor;
    public $theme;
    public $version = '1.0.0';
    public $status = true;
    public $group = 'web';
    public $authors = [];

    public $path;
    public $asset_path;
    public $views;
    public $preview;
    public $preview_default;

    public function __construct(array $details = null)
    {
        if ($details) {
            foreach ($details as $key => $value) {
                $this->$key = $value;
            }

            return $this;
        }
    }

    /**
     * Theme vendor
     */
    public function setVendor(string $vendor): self
    {
        $this->vendor = Str::slug($vendor);

        return $this;
    }

    /**
     * Theme name
     *
     * @param string $name
     * @param string|null $theme theme slug
     */
    public function setName(string $name, string $theme = null): self
    {
        $this->name = $name;

        if (!is_null($theme)) {
            $this->theme = Str::slug($theme);
        }

        return $this;
    }

    /**
     * Theme slug
     */
    public function setTheme(string $theme): self
    {
        $this->theme = Str::slug($theme);

        return $this;
    }

    /**
     * Theme description
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Add author
     */
    public function addAuthor(string $authorName, string $authorsContact = null): self
    {
        $this->authors[] = [
            'name' => $authorName,
            'contact' => $authorsContact
        ];

        return $this;
    }

    /**
     * Theme version
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Theme status
     */
    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Theme group
     */
    public function setGroup(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get theme details
     */
    public function get(): object
    {
        return (object) [
            'vendor' => $this->vendor,
            'name' => $this->name,
            'theme' => $this->theme,
            'description' => $this->description,
            'authors' => $this->authors,
            'version' => $this->version,
            'status' => $this->status,
            'group' => $this->group,
        ];
    }

    /**
     * Make new theme
     */
    public function create()
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themePairs = "{$this->vendor}/{$this->theme}";
        $themeFolder = "{$themesFolder}/{$themePairs}";
        $themeTemplateFolder = __DIR__ . '/./../../resources/theme-template';

        if (!File::exists($themeFolder)) {
            File::makeDirectory($themeFolder, 0755, true);
        } else {
            return [
                'result' => false,
                'message' => "Theme folder already exists: {$themePairs}"
            ];
        }

        if (File::exists($themeFolder)) {
            $isCopied = File::copyDirectory($themeTemplateFolder, $themeFolder);

            if ($isCopied) {
                $themeConfigs = $this->get();
                $themeConfigFile = ThemeSupport::prettyJson($themeConfigs);

                $result = File::put("{$themeFolder}/theme-configs.json", $themeConfigFile);

                if (!$result) {
                    return [
                        'result' => false,
                        'message' => 'Theme configs could not created.'
                    ];
                }
            }

            ThemeManager::reScanThemes();

            return [
                'result' => true,
                'message' => "Theme \"{$themePairs}\" created successfully"
            ];
        }

        return [
            'result' => false,
            'message' => 'Theme folder not found. Please try again.'
        ];
    }

    /**
     * Set theme status
     *
     * After theme status updated rescans themes
     *
     * @param string $themeAliases
     * @param bool $status
     *
     * @param bool
     */
    public function updateStatus(bool $status): bool
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themeConfigsFile = "{$themesFolder}/{$this->vendor}/{$this->theme}/theme-configs.json";

        if (File::exists($themeConfigsFile)) {
            $themeConfigs = json_decode(File::get($themeConfigsFile));

            if ($themeConfigs) {
                $themeConfigs->status = $status;

                $result = File::put($themeConfigsFile, ThemeSupport::prettyJson($themeConfigs));

                if ($result) {
                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * Pack theme files
     *
     * @param bool
     */
    public function pack(): bool
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themeFolder = "{$themesFolder}/{$this->vendor}/{$this->theme}";
        $themeZipOutputFile = "{$themesFolder}/{$this->vendor}/{$this->vendor}-{$this->theme}-{$this->version}.zip";

        if (File::exists($themeFolder)) {

            $zipFile = new ZipFile();

            try{
                $zipFile->addDir($themeFolder, '/', ZipCompressionMethod::DEFLATED);

                $themeDir = new \DirectoryIterator($themeFolder);

                if (File::exists($themeZipOutputFile)) {
                    File::delete($themeZipOutputFile);
                }

                foreach ($themeDir as $dir) {
                    if ($dir->isDir() && !$dir->isDot() && $dir->getFilename() !== 'vendor' && $dir->getFilename() !== 'node_modules') {
                        $path = $dir->getRealPath();
                        $folderName = $dir->getFilename();
                        
                        $zipFile->addDirRecursive($path, $folderName, ZipCompressionMethod::DEFLATED);
                    }
                }

                $zipFile->saveAsFile($themeZipOutputFile)->close();

                return File::exists($themeZipOutputFile);
            }
            catch(ZipException $exp){
                return false;
            }
            finally{
                $zipFile->close();
            }
        }

        return false;
    }

    /**
     * Delete the theme
     */
    public function delete()
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themeFolder = "{$themesFolder}/{$this->vendor}/{$this->theme}";

        if (File::exists($themeFolder)) {
            return File::deleteDirectory($themeFolder);
        }

        return false;
    }
}

<?php

namespace WPDev\Template;

class PluginTemplate extends Template
{
    protected $filePath;
    protected $pluginFolder;

    /**
     * Locates and renders a theme template. Falls back to the absolute `$file_path` passed in.
     *
     * @param string $file_path The absolute path to the file in the plugin. The basename will be used when searching in the themes.
     * @param array $data Data to be passed to the template.
     */
    public function __construct(string $file_path, array $data = [])
    {
        $this->filePath = $file_path;
        $this->pluginFolder   = $this->pluginFolderName($file_path);
        parent::__construct($file_path, $data);

    }

    public function getTemplate(): string
    {
        if ($template = parent::getTemplate()) {
            return $template;
        }

        if (file_exists($this->filePath)) {
            return $this->filePath;
        }

        return '';
    }

    public function includeTemplate()
    {
        if (!parent::includeTemplate()) {
            $data = $this->data;
            extract($data, EXTR_OVERWRITE);
            include $this->filePath;
        }
    }

    protected function excludedPaths(): array
    {
        return [];
    }

    protected function paths(): array
    {
        return [
            get_stylesheet_directory()."/templates/plugins/{$this->pluginFolder}",
            get_template_directory()."/templates/plugins/{$this->pluginFolder}",
        ];
    }

	/**
	 * Gets the plugin folder name containing the template file.
	 *
	 * We use this to help avoid template naming conflicts between plugins and themes.
	 * Also has the added benefit of keeping the project organized.
	 */
    protected function pluginFolderName(string $file_path): string
    {
        $relative_plugin_path = str_replace(WP_PLUGIN_DIR.'/', '', $file_path);
        $file_path_array      = explode('/', $relative_plugin_path);

        return $file_path_array[0];
    }
}
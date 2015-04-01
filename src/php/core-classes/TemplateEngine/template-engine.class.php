<?php

/*  Copyright 2013 MarvinLabs (contact@marvinlabs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

class CUAR_TemplateEngine
{
    /** @var bool true to output debugging info */
    private $enable_debug = false;

    /** @var string The slug of the plugin (usually its folder name) */
    private $plugin_slug = 'define-me';

    /**
     * Constructor
     *
     * @param string $plugin_slug
     * @param bool $enable_debug
     */
    function __construct($plugin_slug, $enable_debug = false)
    {
        $this->plugin_slug = $plugin_slug;
        $this->enable_debug = $enable_debug;
    }


    /**
     * @param boolean $enable_debug
     */
    public function enable_debug($enable_debug = true)
    {
        $this->enable_debug = $enable_debug;
    }

    /**
     * Checks all templates overridden by the user to see if they need an update
     * @param $dirs_to_scan The directories to scan
     * @return array An array containing all the outdated template files found
     */
    public function check_templates($dirs_to_scan)
    {
        $outdated_templates = array();

        foreach ($dirs_to_scan as $dir => $title) {
            $template_finder = new CUAR_TemplateFinder($this);
            $template_finder->scan_directory($dir);

            $tmp = $template_finder->get_outdated_templates();
            if (!empty($tmp)) {
                $outdated_templates[$title] = $tmp;
            }

            unset($template_finder);
        }

        return $outdated_templates;
    }

    /**
     * Takes a default template file as parameter. It will look in the theme's directory to see if the user has
     * customized the template. If so, it returns the path to the customized file. Else, it returns the default
     * passed as parameter.
     *
     * Order of preference is:
     * 1. user-directory/filename
     * 2. user-directory/fallback-filename
     * 3. default-directory/filename
     * 4. default-directory/fallback-filename
     *
     * @param $default_root
     * @param $filename
     * @param string $sub_directory
     * @param string $fallback_filename
     * @return string
     */
    public function get_template_file_path($default_root, $filename, $sub_directory = '', $fallback_filename = '')
    {
        $relative_path = (!empty($sub_directory)) ? trailingslashit($sub_directory) . $filename : $filename;

        $possible_locations = apply_filters('cuar/ui/template-directories',
            array(
                untrailingslashit(WP_CONTENT_DIR) . '/' . $this->plugin_slug,
                untrailingslashit(get_stylesheet_directory()) . '/' . $this->plugin_slug,
                untrailingslashit(get_stylesheet_directory())));

        // Look for the preferred file first
        foreach ($possible_locations as $dir) {
            $path = trailingslashit($dir) . $relative_path;
            if (file_exists($path)) {
                if ($this->enable_debug) {
                    $this->print_template_path_debug_info($path);
                }

                return $path;
            }
        }

        // Then for the fallback alternative if any
        if (!empty($fallback_filename)) {
            $fallback_relative_path = (!empty($sub_directory))
                ? trailingslashit($sub_directory) . $fallback_filename
                : $fallback_filename;

            foreach ($possible_locations as $dir) {
                $path = trailingslashit($dir) . $fallback_relative_path;
                if (file_exists($path)) {
                    if ($this->enable_debug) {
                        $this->print_template_path_debug_info($path);
                    }

                    return $path;
                }
            }
        }

        // Then from default directories. We allow multiple default root directories.
        if (!is_array($default_root)) {
            $default_root = array($default_root);
        }

        foreach ($default_root as $root_path) {
            $path = trailingslashit($root_path) . $relative_path;
            if (file_exists($path)) {
                if ($this->enable_debug) {
                    $this->print_template_path_debug_info($path);
                }

                return $path;
            }

            if (!empty($fallback_filename)) {
                /** @noinspection PhpUndefinedVariableInspection because of the if(!empty... */
                $path = trailingslashit($root_path) . $fallback_relative_path;
                if (file_exists($path)) {
                    if ($this->enable_debug) {
                        $this->print_template_path_debug_info($path, $filename);
                    }

                    return $path;
                }
            }
        }

        if ($this->enable_debug) {
            echo "\n<!-- TEMPLATE < $filename > REQUESTED BUT NOT FOUND (WILL NOT BE USED) -->\n";
        }

        return '';
    }

    /**
     * Output some debugging information about a template we have included (or tried to)
     * @param string $path
     * @param string $original_filename
     */
    private function print_template_path_debug_info($path, $original_filename = null)
    {
        $dirname = dirname($path);
        $strip_from = strpos($path, 'customer-area');
        $dirname = $strip_from > 0 ? strstr($dirname, 'customer-area') : $dirname;

        $filename = basename($path);

        if ($original_filename == null) {
            echo "\n<!-- TEMPLATE < $filename > IN FOLDER < $dirname > -->\n";
        } else {
            echo "\n<!-- TEMPLATE < $original_filename > NOT FOUND BUT USING FALLBACK: < $filename > IN FOLDER < $dirname > -->\n";
        }
    }
}
<?php

namespace WPDev\Facades;

use Exception;
use InvalidArgumentException;

class PostType
{
    // todo method for capability_type
    // todo method for capabilities
    // todo method for capability_type
    // todo method for capabilities

    protected $defaultSupports = ['title', 'editor', 'thumbnail'];
    protected $name;
    public $singularName = '';
    public $pluralName = '';
    public $overrideArgs = [];

	/**
	 * Constructor. For more fluid syntax use `PostType::create()`
	 *
	 * @param string $name The name of the post type. Should be singular.
	 */
    public function __construct(string $name)
    {
        $this->name = $name;

        $this->validateName();
    }

    /**
     * Adds to the 'supports' array
     *
     * @param string $arg
     *
     * @return $this
     */
    public function addSupportArg($arg = '')
    {
        if ( ! $arg) {
            return $this;
        }

        if (empty($this->overrideArgs['supports'])) {
            $this->overrideArgs['supports'] = [];
        }

        $this->overrideArgs['supports'][] = $arg;

        return $this;
    }

    private function buildArgs()
    {
        return $this->deepMergeArray($this->buildDefaultArgs(), $this->overrideArgs);
    }

    private function buildDefaultArgs()
    {
        $defaultArgs = [

            'labels' => [
                'name'                  => $this->getPluralName(),
                'singular_name'         => $this->getSingularName(),
                'add_new'               => "Add New",
                'add_new_item'          => "Add New {$this->getSingularName()}",
                'edit_item'             => "Edit {$this->getSingularName()}",
                'new_item'              => "New {$this->getSingularName()}",
                'view_item'             => "View {$this->getSingularName()}",
                'view_items'            => "View {$this->getPluralName()}",
                'search_items'          => "Search {$this->getPluralName()}",
                'not_found'             => "No {$this->getPluralName()} found",
                'not_found_in_trash'    => "No {$this->getPluralName()} found in Trash",
                'parent_item_colon'     => "Parent {$this->getSingularName()}:",
                'all_items'             => "All {$this->getPluralName()}",
                'archives'              => "{$this->getSingularName()} Archives",
                'attributes'            => "{$this->getSingularName()} Attributes",
                'insert_into_item'      => "Insert into {$this->getSingularName()}",
                'uploaded_to_this_item' => "Uploaded to this {$this->getSingularName()}",
                'featured_image'        => 'Featured Image',
                'set_featured_image'    => 'Set featured image',
                'remove_featured_image' => 'Remove featured image',
                'use_featured_image'    => 'Use as featured image',
                'menu_name'             => $this->getPluralName(),
                'filter_items_list'     => "Filter {$this->getPluralName()} list",
                'items_list_navigation' => "{$this->getPluralName()} list navigation",
                'items_list'            => "{$this->getPluralName()} list",
                'name_admin_bar'        => $this->getSingularName(),
            ],

            'description' => "Handles the {$this->getPluralName()}",

            /**
             * Implies:
             * exclude_from_search = false
             * publicly_queryable = true
             * show_ui = true
             * show_in_nav_menus = true
             * show_in_menu = true
             * show_in_admin_bar = true
             */
            'public'      => true,

            'menu_position' => 5, // below posts

            'supports' => $this->defaultSupports,
        ];

        return $defaultArgs;
    }

    /**
     * Whether or not the post_type can be exported
     * Default: true
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function canExport(bool $bool = true)
    {
        return $this->setArg('can_export', $bool);
    }

	/**
	 * For a more fluid syntax.
	 *
	 * @param string $name
	 * @return $this
	 */
	public static function create(string $name) {
		return new static($name);
    }

    /**
     * Whether to delete posts of this type when deleting a user. If true, posts of this type
     * belonging to the user will be moved to trash when then user is deleted. If false, posts
     * of this type belonging to the user will not be trashed or deleted. If not set (the default),
     * posts are trashed if post_type_supports('author'). Otherwise posts are not trashed or deleted.
     *
     * Default: null
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function deleteWithUser(bool $bool = true)
    {
        return $this->setArg('delete_with_user', $bool);
    }

    /**
     * Deregisters the post type.
     */
    public function deregister() {
    	self::deregisterManually($this->name);
    }

    /**
     * Calls `unregister_post_type`. Meant for use in deactivation hook.
     *
     * @param string $name
     *
     * @throws \Exception
     */
    public static function deregisterManually(string $name = '')
    {
        if ( ! $name) {
            throw new Exception('Need to pass in the name of the post type to deregister');
        }

        unregister_post_type($name);
    }

    private function formatName(bool $plural = false)
    {
        $name = str_replace('_', ' ', $this->name);

        // capitalize hyphenated words
        if (strpos($name, '-')) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
        }

        if ($plural) {
            $name .= 's';
        }

        return ucwords($name);
    }

    private function getSingularName()
    {
        if ( ! $this->singularName) {
            $this->singularName = $this->formatName();
        }

        return $this->singularName;
    }

    private function getPluralName()
    {
        if ( ! $this->pluralName && ! $this->singularName) {
            $this->pluralName = $this->formatName(true);
        } elseif ( ! $this->pluralName) {
            $this->pluralName = $this->singularName.'s';
        }

        return $this->pluralName;
    }

    /**
     * Enables post type archives. Will use $post_type as archive slug by default.
     * Note: Will generate the proper rewrite rules if rewrite is enabled.
     * Also use rewrite to change the slug used. If string, it should be translatable.
     *
     * @param bool|string $val
     *
     * @return $this
     */
    public function hasArchive($val = true)
    {
        return $this->setArg('has_archive', $val);
    }

    /**
     * This sets the endpoint mask. However `rewrite['ep_mask']` takes precedence if it's set there too.
     *
     * @param int|const $endpoint Constant preferred to avoid future failure (core updates)
     *
     * @return $this
     */
    public function permalinkEPMask($endpoint = EP_PERMALINK)
    {
        return $this->setArg('permalink_epmask', $endpoint);
    }

    /**
     * True (default) will use the post type slug
     * False disables query_var key use. A post type cannot be loaded at /?{query_var}={single_post_slug}
     * A string essentially overrides the post type slug /?{query_var_string}={single_post_slug}
     *
     * Remember this is for query_vars not for permalink slug.
     *
     * @param bool|string $query_var
     *
     * @return $this
     */
    public function queryVar($query_var = true)
    {
        return $this->setArg('query_var', $query_var);
    }

    /**
     * Registers the activation hook.
     */
    public function handleActivationHook() {
        add_action('activate_plugin', [$this, 'registerManually']);
    }

    /**
     * Registers the deactivation hook.
     */
    public function handleDeactivationHook() {
        add_action('deactivate_plugin', [$this, 'deregister']);
    }

    /**
     * Use this method if you want need to use a named function
     * to register your post type.
     *
     * @return \WP_Error|\WP_Post_Type
     */
    public function registerManually()
    {
        return register_post_type($this->name, $this->buildArgs());
    }

    /**
     * Registers the post type. Hooks and all.
     *
     * It uses an anonymous function so if you need to allow other plugins
     * to be able to use `@see remove_action()` then you should use `@see registerManually()`
     *
     * @param bool $handle_activation_hooks Whether to register activation and deactivation hooks
     * @param callable|null $callback
     *
     * @return $this
     */
    public function register(bool $handle_activation_hooks = true, callable $callback = null)
    {
    	if ($handle_activation_hooks) {
    		$this->handleActivationHook();
    		$this->handleDeactivationHook();
	    }

        add_action('init', function () use ($callback) {
            if ($callback) {
                $response = register_post_type($this->name, $this->buildArgs());
                $callback($response);
            } else {
                register_post_type($this->name, $this->buildArgs());
            }
        });

        return $this;
    }

    /**
     * Provide a callback function that will be called when setting up the meta boxes for the edit form.
     *
     * The callback function takes one argument `$post`, which contains the `WP_Post` object for the currently edited post.
     * Do remove_meta_box() and add_meta_box() calls in the callback.
     *
     * @param array|string $callback
     *
     * @return $this
     */
    public function registerMetaBoxCB($callback = '')
    {
        return $this->setArg('register_meta_box_cb', $callback);
    }

    /**
     * The base slug that this post type will use when accessed using the REST API.
     * Default: $post_type
     *
     * @param string $rest_base
     *
     * @return $this
     */
    public function restBase(string $rest_base)
    {
        if ( ! $rest_base) {
            $rest_base = $this->name;
        }

        return $this->setArg('rest_base', $rest_base);
    }

    /**
     * An optional custom controller to use instead of `WP_REST_Posts_Controller`. Must be a subclass of `WP_REST_Controller`.
     * Default: WP_REST_Posts_Controller
     *
     * @param string $controller
     *
     * @return $this
     */
    public function restControllerClass(string $controller = 'WP_REST_Posts_Controller')
    {
        return $this->setArg('rest_controller_class', $controller);
    }

    /**
     * Set the `rewrite` arg.
     *
     * ['slug']         string Customize the permalink structure slug. Defaults to the $post_type value. Should be translatable.
     * ['with_front']   bool Should the permalink structure be prepended with the front base.
     *                  (example: if your permalink structure is /blog/,
     *                  then your links will be: false->/news/, true->/blog/news/). Defaults to true
     * ['feeds']        bool Should a feed permalink structure be built for this post type. Defaults to has_archive value.
     * ['pages']        bool Should the permalink structure provide for pagination. Defaults to true
     * ['ep_mask']      const If not specified, then it inherits from permalink_epmask(if permalink_epmask is set),
     *                  otherwise defaults to EP_PERMALINK
     *                  see @link https://make.wordpress.org/plugins/2012/06/07/rewrite-endpoints-api/
     *                  and also @link https://code.tutsplus.com/articles/the-rewrite-api-post-types-taxonomies--wp-25488
     *
     * @param array|bool $val (see above)
     *
     * @return $this
     */
    public function rewrite($val = true)
    {
        return $this->setArg('rewrite', $val);
    }

    /**
     * Set an arg. Can be used to override the defaults.
     *
     * This is just a catch-all. In case there isn't a more semantic
     * method or if that's just your preference.
     *
     * @param string $key
     * @param mixed $val
     *
     * @return $this
     */
    public function setArg(string $key = '', $val = '')
    {
        $this->overrideArgs[$key] = $val;

        return $this;
    }

    /**
     * Whether to expose this post type in the `REST API`.
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function showInRest(bool $bool = true)
    {
        return $this->setArg('show_in_rest', $bool);
    }

    /**
     * Exclude from search results
     *
     * If you set to true, on the taxonomy page (ex: taxonomy.php)
     * WordPress will not find your posts and/or pagination will make 404 error...
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function excludeFromSearch(bool $bool = true)
    {
        return $this->setArg('exclude_from_search', $bool);
    }

    /**
     * Whether the post type is hierarchical (e.g. page).
     *
     * Allows Parent to be specified.
     * The 'supports' parameter should contain 'page-attributes' to show the
     * parent select box on the editor page.
     *
     * Note: this parameter was intended for Pages. Be careful when choosing it
     * for your custom post type - if you are planning to have very many entries
     * (say - over 2-3 thousand), you will run into load time issues. With this
     * parameter set to true WordPress will fetch all IDs of that particular post
     * type on each administration page load for your post type. Servers with
     * limited memory resources may also be challenged by this parameter being set to true.
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function hierarchical(bool $bool = true)
    {
        return $this->setArg('hierarchical', $bool);
    }

    /**
     * Whether to use the internal default meta capability handling.
     *
     * Note: If set it to false then standard admin role can't edit the posts types.
     * Then the edit_post capability must be added to all roles to add or edit the posts types.
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function mapMetaCap(bool $bool = true)
    {
        return $this->setArg('map_meta_cap', $bool);
    }

    /**
     * The menu icon.
     *
     * @param string $icon name of Dashicon, URL to icon, or base64 encoded svg with fill="black"
     *
     * @link https://developer.wordpress.org/resource/dashicons/
     *
     * @return $this
     */
    public function menuIcon(string $icon = '')
    {
        return $this->setArg('menu_icon', $icon);
    }

    /**
     * The position in the menu order the post type should appear.
     *
     * `show_in_menu` must be true.
     * Default: defaults to below Comments
     * 5 - below Posts
     * 10 - below Media
     * 15 - below Links
     * 20 - below Pages
     * 25 - below comments
     * 60 - below first separator
     * 65 - below Plugins
     * 70 - below Users
     * 75 - below Tools
     * 80 - below Settings
     * 100 - below second separator
     *
     * @param int $position
     *
     * @return $this
     */
    public function menuPosition(int $position = 25)
    {
        return $this->setArg('menu_position', $position);
    }

    /**
     * Removes a supports arg. Use this to remove one of the defaults.
     *
     * @param string $feature The feature to remove
     *
     * @return $this
     */
    public function removeSupportArg(string $feature)
    {
        if (($key = array_search($feature, $this->defaultSupports)) !== false) {
            unset($this->defaultSupports[$key]);
        }

        return $this;
    }


/**
     * Set the plural name. Useful if simply appending an 's' isn't grammatically correct.
     *
     * @param string $plural_name
     *
     * @return $this
     */
    public function setPluralName(string $plural_name = '')
    {
        $this->pluralName = $plural_name;

        return $this;
    }

    /**
     * Sets the `public` arg.
     *
     * Implies:
     * exclude_from_search = false
     * publicly_queryable = true
     * show_in_nav_menus = true
     * show_ui = true
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function public(bool $bool = true)
    {
        return $this->setArg('public', $bool);
    }

    /**
     * Whether queries can be performed on the front end as part of parse_request().
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function publiclyQueryable(bool $bool = true)
    {
        return $this->setArg('publicly_queryable', $bool);
    }

    /**
     * Whether to make this post type available in the WordPress admin bar.
     *
     * Default: value of the show_in_menu argument
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function showInAdminBar(bool $bool = true)
    {
        return $this->setArg('show_in_admin_bar', $bool);
    }

    /**
     * Show this post type in the menu.
     *
     * @param bool|string $val - If string is given it will be a submenu
     * if that url exists. Examples: 'tools.php' or 'edit.php?post_type=page';
     *
     * @return $this
     */
    public function showInMenu($val = true)
    {
        return $this->setArg('show_in_menu', $val);
    }

    /**
     * Post type is available for selection in navigation menus.
     *
     * Default: value of public argument
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function showInNavMenus(bool $bool = true)
    {
        return $this->setArg('show_in_nav_menus', $bool);
    }

    /**
     * Whether to generate a default UI for managing this post type in the admin.
     *
     * Default: value of public argument
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function showUI(bool $bool = true)
    {
        return $this->setArg('show_ui', $bool);
    }

    /**
     * Overrides auto generated singular name.
     *
     * @param string $singular_name
     *
     * @return $this
     */
    public function setSingularName(string $singular_name = '')
    {
        $this->singularName = $singular_name;

        return $this;
    }

    /**
     * Support author.
     *
     * @return $this
     */
    public function supportsAuthor()
    {
        return $this->addSupportArg('author');
    }

    /**
     * Support comments.
     *
     * @return $this
     */
    public function supportsComments()
    {
        return $this->addSupportArg('comments');
    }

    /**
     * Support custom fields.
     *
     * @return $this
     */
    public function supportsCustomFields()
    {
        return $this->addSupportArg('custom-fields');
    }

    /**
     * Support editor.
     *
     * @return $this
     */
    public function supportsEditor()
    {
        return $this->addSupportArg('editor');
    }

    /**
     * Support excerpt.
     *
     * @return $this
     */
    public function supportsExcerpt()
    {
        return $this->addSupportArg('excerpt');
    }

    /**
     * Support featured image (aka thumbnail).
     *
     * Alias for @see \WPDev\PostType\PostType::supportsThumbnail()
     *
     * @return $this
     */
    public function supportsFeaturedImage()
    {
        return $this->supportsThumbnail();
    }

    /**
     * Support page attributes.
     *
     * @return $this
     */
    public function supportsPageAttributes()
    {
        return $this->addSupportArg('page-attributes');
    }

    /**
     * Support post formats.
     *
     * @return $this
     */
    public function supportsPostFormats()
    {
        return $this->addSupportArg('post-formats');
    }

    /**
     * Support revisions.
     *
     * @return $this
     */
    public function supportsRevisions()
    {
        return $this->addSupportArg('revisions');
    }

    /**
     * Alternative to `@see supportsFeaturedImage`. Support featured image (aka thumbnail).
     *
     * @return $this
     */
    public function supportsThumbnail()
    {
        return $this->addSupportArg('thumbnail');
    }

    /**
     * Support title.
     *
     * @return $this
     */
    public function supportsTitle()
    {
        return $this->addSupportArg('title');
    }

    /**
     * Support trackbacks.
     *
     * @return $this
     */
    public function supportsTrackbacks()
    {
        return $this->addSupportArg('trackbacks');
    }

    /**
     * False can be passed as value instead of an array to
     * prevent default (title and editor) behavior
     *
     * @param array|false $features
     *
     * @return $this
     */
    public function supports($features = ['editor', 'title'])
    {
        return $this->setArg('supports', $features);
    }

    /**
     * An array of registered taxonomies like category or post_tag that will be used with this post type.
     *
     * This can be used in lieu of calling register_taxonomy_for_object_type() directly.
     * Custom taxonomies still need to be registered with register_taxonomy().
     *
     * @param array $taxonomies
     *
     * @return $this
     */
    public function taxonomies(array $taxonomies = [])
    {
        return $this->setArg('taxonomies', $taxonomies);
    }

    private function validateName()
    {
        $reserved_names = [
            'post',
            'page',
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'action',
            'author',
            'order',
            'order',
            'theme',
        ];

        if (!$this->name) {
            throw new InvalidArgumentException('Empty string not valid.');
        }

        if (in_array($this->name, $reserved_names)) {
            throw new InvalidArgumentException("'{$this->name}' is a WordPress reserved name");
        }

        if (strpos($this->name, ' ') !== false) {
            throw new InvalidArgumentException('Post type machine name cannot contain spaces.');
        }

        if (strtolower($this->name) !== $this->name) {
            throw new InvalidArgumentException('Post type machine name cannot contain capital letters.');
        }

        if (strlen($this->name) > 20) {
            throw new InvalidArgumentException('Post type machine name cannot exceed 20 characters. Current name is '.strlen($this->name).' characters long.');
        }
    }

    private function deepMergeArray()
    {
        $arrays = func_get_args();
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {

                // if it's zero-based, append it
                if (is_integer($key)) {
                    $result[] = $value;
                    continue;
                }

                // if it's a new arg (aka key)
                if ( ! isset($result[$key])) {
                    $result[$key] = $value;
                    continue;
                }

                $old_value = $result[$key];

                // Recurse when both values are arrays.
                if (is_array($old_value) && is_array($value)) {
                    $result[$key] = $this->deepMergeArray($old_value, $value);
                    continue;
                }

                // else override
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
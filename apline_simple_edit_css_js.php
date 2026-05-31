<?php
/**
 * APLINE Simple Edit CSS/JS module for PrestaShop 9.
 *
 * Inject custom CSS and JavaScript into the shop front-end without touching the
 * theme. Each fragment is a "snippet" managed like rows (drag&drop ordering,
 * on/off, edit). Classic PrestaShop API, no front build step.
 *
 * Trust model: snippet code is injected verbatim as raw <style>/<script>. This
 * is intentional — the input comes from a back-office admin with full rights,
 * never from a customer. It is not XSS: customers cannot create snippets.
 *
 * @author    APLINE Arkadiusz Pielechowski
 * @copyright APLINE Arkadiusz Pielechowski
 * @license   Custom Attribution License v1.0 - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/AplineSimpleEditCssJsSnippet.php';

class apline_simple_edit_css_js extends Module
{
    const FORMAT_CSS_KEY = 'ASEC_FORMAT_CSS_ON_SAVE';

    const ADMIN_CONTROLLER = 'AdminAplineSimpleEditCssJsSnippet';

    /**
     * Hooks the module renders on. Both are always registered and are NOT
     * configurable (they are the foundation of the module). Per-snippet
     * placement is decided by the `location` column, not globally.
     *
     * @return array
     */
    public static function getAvailableHooks()
    {
        return [
            'displayHeader' => 'Page <head> (inline CSS + JS)',
            'displayBeforeBodyClosingTag' => 'Before </body> (inline JS)',
        ];
    }

    public function __construct()
    {
        $this->name = 'apline_simple_edit_css_js';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'APLINE Arkadiusz Pielechowski';
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('APLINE Simple Edit CSS/JS for PrestaShop 9', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        $this->description = $this->trans('Inject custom CSS and JavaScript snippets into the front-end without editing your theme. Manage snippets like rows: drag & drop ordering, enable/disable, edit.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module? All snippets and their version history will be deleted.', [], 'Modules.Aplinesimpleeditcssjs.Admin');

        $this->ps_versions_compliancy = ['min' => '9.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->installDb()
            || !$this->installConfiguration()
            || !$this->installHooks()
            || !$this->installTab()
        ) {
            // Roll back to a clean state so the shop is never left half-installed.
            $this->uninstall();
            $this->_errors[] = $this->trans('Installation failed and was rolled back. Please check the database permissions and try again.', [], 'Modules.Aplinesimpleeditcssjs.Admin');

            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Each step is idempotent; uninstall must not fail because something is already gone.
        $this->uninstallTab();

        // Drop the version table first (it has an FK to the snippet table).
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'asec_snippet_version`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'asec_snippet`');

        Configuration::deleteByName(self::FORMAT_CSS_KEY);

        return parent::uninstall();
    }

    /**
     * Create both tables (raw CREATE TABLE so LONGTEXT/ENUM are exact) and seed
     * two starter snippets. InnoDB is forced on both tables so the foreign key
     * is guaranteed to be created regardless of the shop's default engine.
     *
     * @return bool
     */
    private function installDb()
    {
        $snippet = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'asec_snippet` (
            `id_asec_snippet` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `type` ENUM(\'css\', \'js\') NOT NULL,
            `code` LONGTEXT NOT NULL,
            `location` ENUM(\'head\', \'body_end\') NOT NULL DEFAULT \'head\',
            `load_when` ENUM(\'immediate\', \'on_ready\') NOT NULL DEFAULT \'immediate\',
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_asec_snippet`),
            KEY `idx_active_position` (`active`, `position`),
            KEY `idx_type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

        if (!Db::getInstance()->execute($snippet)) {
            return false;
        }

        // FK name carries _DB_PREFIX_ because MySQL constraint names are unique
        // per database (two shops sharing a DB would collide on a fixed name).
        $version = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'asec_snippet_version` (
            `id_asec_snippet_version` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_asec_snippet` INT(10) UNSIGNED NOT NULL,
            `code` LONGTEXT NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_asec_snippet_version`),
            KEY `idx_snippet_date` (`id_asec_snippet`, `date_add`),
            CONSTRAINT `fk_' . _DB_PREFIX_ . 'asec_ver_snip`
                FOREIGN KEY (`id_asec_snippet`)
                REFERENCES `' . _DB_PREFIX_ . 'asec_snippet` (`id_asec_snippet`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

        if (!Db::getInstance()->execute($version)) {
            return false;
        }

        return $this->seedSnippets();
    }

    /**
     * Insert the two starter snippets. The version history starts empty.
     *
     * @return bool
     */
    private function seedSnippets()
    {
        $now = date('Y-m-d H:i:s');
        $pos = 1;
        foreach ($this->getSeedSnippets() as $snip) {
            // html_ok = true on `code` so the educational HTML inside comments
            // (e.g. <a>, <img>) is preserved verbatim instead of being stripped.
            $ok = Db::getInstance()->insert('asec_snippet', [
                'name' => pSQL($snip['name']),
                'type' => pSQL($snip['type']),
                'code' => pSQL($snip['code'], true),
                'location' => pSQL($snip['location']),
                'load_when' => pSQL($snip['load_when']),
                'active' => (int) $snip['active'],
                'position' => $pos++,
                'date_add' => $now,
                'date_upd' => $now,
            ]);
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * Definition of the two starter snippets.
     *
     * @return array
     */
    private function getSeedSnippets()
    {
        $cssPlaceholder = "/*\n"
            . " * Add your custom CSS here.\n"
            . " * This snippet is injected inline in the page <head>\n"
            . " * on every front-end page of the shop.\n"
            . " */\n";

        return [
            [
                'name' => 'Custom CSS placeholder',
                'type' => 'css',
                'code' => $cssPlaceholder,
                'location' => 'head',
                'load_when' => 'immediate',
                'active' => 1,
            ],
            [
                'name' => 'YouTube embed player in product description',
                'type' => 'js',
                'code' => $this->getYoutubeSeedJs(),
                'location' => 'body_end',
                'load_when' => 'on_ready',
                'active' => 0,
            ],
        ];
    }

    /**
     * The disabled-by-default educational JS snippet: a YouTube lite-embed
     * player. Stored as a nowdoc so nothing is interpolated.
     *
     * @return string
     */
    private function getYoutubeSeedJs()
    {
        return <<<'JS_SEED'
/*
 * Example snippet — DISABLED by default.
 *
 * What it does:
 *   Scans every <a href="*youtube.com/watch?v=ID"> inside
 *   .product__description and converts the static thumbnail into
 *   a clickable in-page player. Click loads the actual YouTube
 *   iframe with autoplay. No external assets, no third-party JS
 *   on page load (lite-embed pattern).
 *
 * How to use:
 *   1. Turn the "Active" switch on (snippet list view).
 *   2. Add a YouTube link to a product description in BO with this
 *      HTML pattern (the visual editor's "embed YouTube" usually
 *      produces it):
 *        <p><a href="https://www.youtube.com/watch?v=XXX"
 *             target="_blank" rel="noreferrer noopener">
 *          <img src="https://img.youtube.com/vi/XXX/hqdefault.jpg"
 *               alt="...">
 *        </a></p>
 *   3. Open the product on the front-end. Click the thumbnail.
 *
 * How to customize:
 *   - Container selector: change CONFIG.containerSelector below
 *     if your theme uses a different class for the description
 *     (e.g. '.product-description', '#product-description').
 *   - Autoplay on click: set CONFIG.autoplayOnClick = false.
 *   - Visual style: edit the STYLE constant (play button color,
 *     border-radius, hover effect).
 */
(function () {
    'use strict';

    // === CONFIG (edit me in the Back Office) ===
    var CONFIG = {
        containerSelector: '.product__description',
        // alternative selectors for different themes:
        // '.product-description', '#product-description', '[itemprop="description"]'
        autoplayOnClick: true,
        injectStyle: true // injects the bundled CSS on first run
    };

    var YT_REGEX = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/;

    // === STYLE (inline, injected once on first run) ===
    var STYLE = [
        '.asec-yt-player{',
        '  position:relative;display:block;width:100%;max-width:100%;',
        '  aspect-ratio:16/9;background-size:cover;background-position:center;',
        '  cursor:pointer;border-radius:4px;overflow:hidden;',
        '}',
        '.asec-yt-player::before{',
        '  content:"";position:absolute;inset:0;background:rgba(0,0,0,0.15);',
        '  transition:background 0.2s ease;',
        '}',
        '.asec-yt-player:hover::before{background:rgba(0,0,0,0.05);}',
        '.asec-yt-player::after{',
        '  content:"";position:absolute;top:50%;left:50%;',
        '  transform:translate(-50%,-50%);',
        '  width:68px;height:48px;',
        '  background:rgba(255,0,0,0.85);border-radius:14%;',
        '  pointer-events:none;',
        '}',
        '.asec-yt-play-icon{',
        '  position:absolute;top:50%;left:50%;',
        '  transform:translate(-50%,-50%);',
        '  border-style:solid;border-width:12px 0 12px 20px;',
        '  border-color:transparent transparent transparent #fff;',
        '  z-index:2;pointer-events:none;',
        '}',
        '.asec-yt-iframe{',
        '  width:100%;aspect-ratio:16/9;border:0;display:block;',
        '}',
        '@media (max-width:480px){.asec-yt-player::after{width:48px;height:34px;}}'
    ].join('\n');

    function injectStyleOnce() {
        if (!CONFIG.injectStyle || document.getElementById('asec-yt-style')) return;
        var s = document.createElement('style');
        s.id = 'asec-yt-style';
        s.textContent = STYLE;
        document.head.appendChild(s);
    }

    function extractVideoId(url) {
        if (!url) return null;
        var m = url.match(YT_REGEX);
        return m ? m[1] : null;
    }

    function convertLink(a) {
        var href = a.getAttribute('href');
        var id = extractVideoId(href);
        if (!id) return;

        // Thumbnail: use the anchor's <img> if present, else the YT API URL.
        var img = a.querySelector('img');
        var thumbUrl = img && img.getAttribute('src')
            ? img.getAttribute('src')
            : 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';

        var player = document.createElement('div');
        player.className = 'asec-yt-player';
        player.setAttribute('role', 'button');
        player.setAttribute('aria-label', 'Play video');
        player.setAttribute('tabindex', '0');
        player.setAttribute('data-asec-yt-id', id);
        player.style.backgroundImage = 'url("' + thumbUrl.replace(/"/g, '%22') + '")';

        var playIcon = document.createElement('span');
        playIcon.className = 'asec-yt-play-icon';
        player.appendChild(playIcon);

        function launch() {
            var iframe = document.createElement('iframe');
            iframe.className = 'asec-yt-iframe';
            iframe.src = 'https://www.youtube.com/embed/' + id
                + (CONFIG.autoplayOnClick ? '?autoplay=1' : '');
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('frameborder', '0');
            player.parentNode.replaceChild(iframe, player);
        }

        player.addEventListener('click', launch);
        player.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); launch(); }
        });

        // Replace the <a> with our player, keeping the wrapper for theme margins.
        a.parentNode.replaceChild(player, a);
    }

    function scan() {
        var containers = document.querySelectorAll(CONFIG.containerSelector);
        Array.prototype.forEach.call(containers, function (container) {
            var links = container.querySelectorAll('a[href*="youtube.com/watch"], a[href*="youtu.be/"]');
            Array.prototype.forEach.call(links, convertLink);
        });
    }

    injectStyleOnce();
    scan();
})();
JS_SEED;
    }

    /**
     * @return bool
     */
    private function installConfiguration()
    {
        return Configuration::updateValue(self::FORMAT_CSS_KEY, 1);
    }

    /**
     * @return bool
     */
    private function installHooks()
    {
        $ok = true;
        foreach (array_keys(self::getAvailableHooks()) as $hook) {
            $ok = $ok && $this->registerHook($hook);
        }

        return $ok;
    }

    /**
     * @return bool
     */
    private function installTab()
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = 1;
        // Hidden tab (no visible parent): managed from the module configuration page.
        $tab->id_parent = -1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Simple Edit CSS/JS';
        }

        return (bool) $tab->add();
    }

    /**
     * @return bool
     */
    private function uninstallTab()
    {
        $id = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        if (!$id) {
            return true;
        }

        try {
            $tab = new Tab($id);

            return (bool) $tab->delete();
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitAsecConfig')) {
            $format = (int) (bool) Tools::getValue(self::FORMAT_CSS_KEY);
            Configuration::updateValue(self::FORMAT_CSS_KEY, $format);
            $output .= $this->displayConfirmation($this->trans('Settings updated.', [], 'Modules.Aplinesimpleeditcssjs.Admin'));
        }

        $manageUrl = $this->context->link->getAdminLink(self::ADMIN_CONTROLLER);

        $this->context->smarty->assign([
            'asec_manage_url' => $manageUrl,
        ]);
        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        return $output . $this->renderConfigForm() . $this->renderLikeBox() . $this->renderAplineFooter();
    }

    /**
     * APLINE attribution block. Required by the module license to stay visible
     * on the configuration page with a working link to https://apline.pl.
     * Rendered server-side as a standalone component (not CSS-only) so it
     * cannot be trivially stripped.
     *
     * @return string
     */
    public function renderAplineFooter()
    {
        return '
        <style>
            .apline-credit { margin-top: 24px; font-size: 12px; opacity: 0.9; }
            .apline-credit a { font-weight: 600; }
        </style>
        <div class="apline-credit">
            ' . $this->trans('Module created by', [], 'Modules.Aplinesimpleeditcssjs.Admin') . '
            <a href="https://apline.pl" target="_blank" rel="noopener noreferrer">APLINE</a>
        </div>';
    }

    /**
     * Subtle "need custom development?" box shown on the configuration page.
     *
     * @return string
     */
    public function renderLikeBox()
    {
        return '
        <div class="panel">
            <h3>&#9749; ' . $this->trans('Like this module?', [], 'Modules.Aplinesimpleeditcssjs.Admin') . '</h3>
            <p>' . $this->trans('Need custom PrestaShop development, performance optimization or integrations?', [], 'Modules.Aplinesimpleeditcssjs.Admin') . '</p>
            <a class="btn btn-default" href="https://apline.pl" target="_blank" rel="noopener noreferrer">&#8594; APLINE.PL</a>
        </div>';
    }

    /**
     * @return string
     */
    private function renderConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Auto-format CSS on save', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                        'name' => self::FORMAT_CSS_KEY,
                        'is_bool' => true,
                        'desc' => $this->trans('When enabled, CSS snippets are automatically reformatted when saved. Disable it if you maintain your own formatting.', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                        'values' => [
                            ['id' => 'format_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'format_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitAsecConfig';
        $helper->fields_value = [
            self::FORMAT_CSS_KEY => (int) Configuration::get(self::FORMAT_CSS_KEY),
        ];

        return $helper->generateForm([$fields_form]);
    }
}

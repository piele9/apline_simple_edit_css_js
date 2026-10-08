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
 * @author    Arkadiusz Pielechowski
 * @copyright Arkadiusz Pielechowski
 * @license   MIT - see LICENSE.md
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
            'displayHeader' => 'Nagłówek strony <head> (CSS i JS w treści strony)',
            'displayBeforeBodyClosingTag' => 'Przed </body> (JS w treści strony)',
        ];
    }

    public function __construct()
    {
        $this->name = 'apline_simple_edit_css_js';
        $this->tab = 'front_office_features';
        $this->version = '1.1.1';
        $this->author = 'Arkadiusz Pielechowski';
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('APLINE Simple Edit CSS/JS dla PrestaShop 9', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        $this->description = $this->trans('Wstawiaj własne fragmenty CSS i JavaScript na stronę sklepu bez edytowania motywu. Fragmentami zarządzasz jak wierszami: kolejność przeciągasz myszą, włączasz i wyłączasz je oraz edytujesz.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        $this->confirmUninstall = $this->trans('Czy na pewno chcesz odinstalować ten moduł? Wszystkie fragmenty i historia ich wersji zostaną usunięte.', [], 'Modules.Aplinesimpleeditcssjs.Admin');

        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
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
            $this->_errors[] = $this->trans('Instalacja nie powiodła się i została wycofana. Sprawdź uprawnienia bazy danych i spróbuj ponownie.', [], 'Modules.Aplinesimpleeditcssjs.Admin');

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
     * Definition of the two starter snippets. They are generic, educational
     * examples (no shop-specific code); both are written in Polish.
     *
     * @return array
     */
    private function getSeedSnippets()
    {
        $cssPlaceholder = "/*\n"
            . " * Tutaj wpisz własny CSS.\n"
            . " * Ten fragment jest wstawiany bezpośrednio w sekcji <head>\n"
            . " * na każdej stronie sklepu.\n"
            . " */\n";

        return [
            [
                'name' => 'Miejsce na własny CSS',
                'type' => 'css',
                'code' => $cssPlaceholder,
                'location' => 'head',
                'load_when' => 'immediate',
                'active' => 1,
            ],
            [
                'name' => 'Odtwarzacz YouTube w opisie produktu',
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
 * Przykładowy fragment — domyślnie WYŁĄCZONY.
 *
 * Co robi:
 *   Przeszukuje każdy link <a href="*youtube.com/watch?v=ID"> wewnątrz
 *   .product__description i zamienia statyczną miniaturę na klikalny
 *   odtwarzacz na stronie. Kliknięcie ładuje właściwy iframe YouTube
 *   z autoodtwarzaniem. Bez zewnętrznych zasobów i skryptów stron
 *   trzecich przy ładowaniu strony (wzorzec „lite embed”).
 *
 * Jak użyć:
 *   1. Włącz przełącznik „Aktywny” tego fragmentu (lista fragmentów).
 *   2. Dodaj link do YouTube w opisie produktu w panelu, w takim układzie
 *      HTML (edytor wizualny po wstawieniu filmu zwykle go tworzy):
 *        <p><a href="https://www.youtube.com/watch?v=XXX"
 *             target="_blank" rel="noreferrer noopener">
 *          <img src="https://img.youtube.com/vi/XXX/hqdefault.jpg"
 *               alt="...">
 *        </a></p>
 *   3. Otwórz produkt w sklepie i kliknij miniaturę.
 *
 * Jak dostosować:
 *   - Selektor kontenera: zmień CONFIG.containerSelector poniżej,
 *     jeśli motyw używa innej klasy dla opisu
 *     (np. '.product-description', '#product-description').
 *   - Autoodtwarzanie po kliknięciu: ustaw CONFIG.autoplayOnClick = false.
 *   - Wygląd: edytuj stałą STYLE (kolor przycisku odtwarzania,
 *     zaokrąglenie rogów, efekt po najechaniu).
 */
(function () {
    'use strict';

    // === USTAWIENIA (edytuj w panelu) ===
    var CONFIG = {
        containerSelector: '.product__description',
        // alternatywne selektory dla innych motywów:
        // '.product-description', '#product-description', '[itemprop="description"]'
        autoplayOnClick: true,
        injectStyle: true // wstawia dołączony CSS przy pierwszym uruchomieniu
    };

    var YT_REGEX = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/;

    // === STYL (wstawiany raz, przy pierwszym uruchomieniu) ===
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

        // Miniatura: weź <img> z linku, a gdy go brak — adres z API YouTube.
        var img = a.querySelector('img');
        var thumbUrl = img && img.getAttribute('src')
            ? img.getAttribute('src')
            : 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';

        var player = document.createElement('div');
        player.className = 'asec-yt-player';
        player.setAttribute('role', 'button');
        player.setAttribute('aria-label', 'Odtwórz film');
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

        // Zastąp <a> odtwarzaczem, zostawiając otoczkę (marginesy motywu).
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
            $tab->name[$lang['id_lang']] = 'Fragmenty CSS/JS';
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

        // Admin stylesheet with the shared big-button class (.apline-btn-duzy).
        $this->context->controller->addCSS($this->_path . 'views/css/admin.css');

        if (Tools::isSubmit('submitAsecConfig')) {
            $format = (int) (bool) Tools::getValue(self::FORMAT_CSS_KEY);
            Configuration::updateValue(self::FORMAT_CSS_KEY, $format);
            $output .= $this->displayConfirmation($this->trans('Ustawienia zostały zapisane.', [], 'Modules.Aplinesimpleeditcssjs.Admin'));
        }

        $manageUrl = $this->context->link->getAdminLink(self::ADMIN_CONTROLLER);

        $this->context->smarty->assign([
            'asec_manage_url' => $manageUrl,
        ]);
        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        return $output . $this->renderConfigForm() . $this->renderLikeBox() . $this->renderAplineFooter();
    }

    /**
     * Author credit with a link to https://pielechowski.pl, shown on the
     * configuration page. The module is MIT-licensed: the credit is kept by
     * default, it is not a license requirement.
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
            ' . $this->trans('Moduł stworzony przez', [], 'Modules.Aplinesimpleeditcssjs.Admin') . '
            <a href="https://pielechowski.pl" target="_blank" rel="noopener noreferrer">PIELECHOWSKI.PL</a>
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
            <h3>&#9749; ' . $this->trans('Podoba Ci się ten moduł?', [], 'Modules.Aplinesimpleeditcssjs.Admin') . '</h3>
            <p>' . $this->trans('Potrzebujesz rozwiązań dla PrestaShop na zamówienie, optymalizacji wydajności lub integracji?', [], 'Modules.Aplinesimpleeditcssjs.Admin') . '</p>
            <a class="btn btn-default" href="https://pielechowski.pl" target="_blank" rel="noopener noreferrer">&#8594; PIELECHOWSKI.PL</a>
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
                    'title' => $this->trans('Ustawienia', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Automatycznie formatuj CSS przy zapisie', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                        'name' => self::FORMAT_CSS_KEY,
                        'is_bool' => true,
                        'desc' => $this->trans('Po włączeniu fragmenty CSS są automatycznie formatowane podczas zapisu. Wyłącz, jeśli samodzielnie dbasz o formatowanie.', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                        'values' => [
                            ['id' => 'format_on', 'value' => 1, 'label' => $this->trans('Tak', [], 'Admin.Global')],
                            ['id' => 'format_off', 'value' => 0, 'label' => $this->trans('Nie', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Zapisz', [], 'Admin.Actions'),
                    'class' => 'btn btn-primary btn-lg apline-btn-duzy pull-right',
                    'icon' => 'icon-save',
                ],
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

    /**
     * Render inline into the page <head>: all active CSS first (so styles land
     * before scripts and avoid a flash of unstyled content), then active JS
     * placed in the head.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayHeader($params)
    {
        try {
            $out = '';

            foreach (AplineSimpleEditCssJsSnippet::getActiveSnippets('css') as $snip) {
                $out .= '<style type="text/css" data-asec-id="' . (int) $snip['id_asec_snippet'] . '">' . "\n"
                    . $snip['code'] . "\n"
                    . '</style>' . "\n";
            }

            foreach (AplineSimpleEditCssJsSnippet::getActiveSnippets('js', 'head') as $snip) {
                $out .= $this->wrapJs($snip);
            }

            return $out;
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('apline_simple_edit_css_js: ' . $e->getMessage(), 3);

            return '';
        }
    }

    /**
     * Render active JS placed just before </body>.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayBeforeBodyClosingTag($params)
    {
        try {
            $out = '';
            foreach (AplineSimpleEditCssJsSnippet::getActiveSnippets('js', 'body_end') as $snip) {
                $out .= $this->wrapJs($snip);
            }

            return $out;
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('apline_simple_edit_css_js: ' . $e->getMessage(), 3);

            return '';
        }
    }

    /**
     * Wrap a JS snippet in a <script> tag. When load_when is "on_ready" the code
     * runs after DOMContentLoaded; "immediate" runs it inline as parsed.
     * The code is emitted verbatim (admin-trusted input — see the class header).
     *
     * @param array $snip
     *
     * @return string
     */
    private function wrapJs(array $snip)
    {
        $code = (string) $snip['code'];
        if (isset($snip['load_when']) && $snip['load_when'] === 'on_ready') {
            $code = "document.addEventListener('DOMContentLoaded', function () {\n" . $code . "\n});";
        }

        return '<script type="text/javascript" data-asec-id="' . (int) $snip['id_asec_snippet'] . '">' . "\n"
            . $code . "\n"
            . '</script>' . "\n";
    }
}

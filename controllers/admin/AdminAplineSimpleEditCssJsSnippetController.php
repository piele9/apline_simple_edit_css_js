<?php
/**
 * APLINE Simple Edit CSS/JS module for PrestaShop 9.
 *
 * @author    APLINE Arkadiusz Pielechowski
 * @copyright APLINE Arkadiusz Pielechowski
 * @license   Custom Attribution License v1.0 - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'apline_simple_edit_css_js/classes/AplineSimpleEditCssJsSnippet.php';

class AdminAplineSimpleEditCssJsSnippetController extends ModuleAdminController
{
    const MAX_STRING = 255;
    const ALLOWED_TYPES = ['css', 'js'];
    const ALLOWED_LOCATIONS = ['head', 'body_end'];
    const ALLOWED_LOAD_WHEN = ['immediate', 'on_ready'];

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'asec_snippet';
        $this->className = 'AplineSimpleEditCssJsSnippet';
        $this->identifier = 'id_asec_snippet';
        $this->position_identifier = 'id_asec_snippet';
        $this->lang = false;
        $this->allow_export = false;

        parent::__construct();

        $this->fields_list = [
            'id_asec_snippet' => [
                'title' => $this->trans('ID', [], 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Name', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
            ],
            'type' => [
                'title' => $this->trans('Type', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'align' => 'center',
                'callback' => 'printType',
                'search' => false,
            ],
            'location' => [
                'title' => $this->trans('Location', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'align' => 'center',
                'callback' => 'printLocation',
                'search' => false,
            ],
            'load_when' => [
                'title' => $this->trans('Load when', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'align' => 'center',
                'callback' => 'printLoadWhen',
                'search' => false,
            ],
            'code' => [
                'title' => $this->trans('Code preview', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'callback' => 'printCodePreview',
                'orderby' => false,
                'search' => false,
            ],
            'active' => [
                'title' => $this->trans('Active', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'align' => 'center',
                'active' => 'active',
                'type' => 'bool',
                'orderby' => false,
            ],
            'position' => [
                'title' => $this->trans('Position', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'align' => 'center',
                'position' => 'position',
                'search' => false,
            ],
        ];

        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Admin.Actions'),
                'confirm' => $this->trans('Delete selected items?', [], 'Admin.Notifications.Warning'),
            ],
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryUI('ui.sortable');
    }

    /**
     * Module configuration URL (so the user can get back from the snippet list).
     *
     * @return string
     */
    private function getConfigUrl()
    {
        return $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'apline_simple_edit_css_js',
            'module_name' => 'apline_simple_edit_css_js',
        ]);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['back_to_config'] = [
            'href' => $this->getConfigUrl(),
            'desc' => $this->trans('Back to configuration', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
            'icon' => 'process-icon-back',
        ];
    }

    public function renderList()
    {
        $list = parent::renderList();

        // Breadcrumb-style back link + mandatory APLINE attribution under the table.
        $back = '<div style="margin:10px 0;"><a class="btn btn-default" href="'
            . htmlspecialchars($this->getConfigUrl(), ENT_QUOTES)
            . '"><i class="icon-chevron-left"></i> '
            . $this->trans('Back to configuration', [], 'Modules.Aplinesimpleeditcssjs.Admin')
            . '</a></div>';

        $credit = method_exists($this->module, 'renderAplineFooter')
            ? $this->module->renderAplineFooter()
            : '';

        return $back . $list . $credit;
    }

    /**
     * @param string $type css|js
     *
     * @return string list cell HTML (colored badge)
     */
    public function printType($type, $row)
    {
        $type = strtolower((string) $type);
        if ($type === 'css') {
            return '<span class="badge" style="background:#2eacb5;">CSS</span>';
        }
        if ($type === 'js') {
            return '<span class="badge" style="background:#d9a441;">JS</span>';
        }

        return htmlspecialchars((string) $type, ENT_QUOTES);
    }

    /**
     * @param string $location head|body_end
     *
     * @return string list cell HTML
     */
    public function printLocation($location, $row)
    {
        if ($location === 'body_end') {
            return '<code>&lt;/body&gt;</code>';
        }

        return '<code>&lt;head&gt;</code>';
    }

    /**
     * @param string $loadWhen immediate|on_ready
     *
     * @return string list cell HTML (dash for CSS, which ignores it)
     */
    public function printLoadWhen($loadWhen, $row)
    {
        if (isset($row['type']) && $row['type'] === 'css') {
            return '<span class="text-muted">&mdash;</span>';
        }
        if ($loadWhen === 'on_ready') {
            return $this->trans('On DOMReady', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        return $this->trans('Immediate', [], 'Modules.Aplinesimpleeditcssjs.Admin');
    }

    /**
     * @param string $code
     *
     * @return string list cell HTML (first ~60 chars, escaped)
     */
    public function printCodePreview($code, $row)
    {
        $code = trim((string) $code);
        // Collapse whitespace so the preview stays on one line.
        $code = preg_replace('/\s+/', ' ', $code);
        $preview = mb_substr($code, 0, 60);
        $ellipsis = mb_strlen($code) > 60 ? '&hellip;' : '';

        if ($preview === '') {
            return '<span class="text-muted">&mdash;</span>';
        }

        return '<code style="font-size:11px;">' . htmlspecialchars($preview, ENT_QUOTES) . $ellipsis . '</code>';
    }

    public function renderForm()
    {
        $obj = $this->loadObject(true);
        $isEdit = Validate::isLoadedObject($obj);

        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('CSS / JS snippet', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                'icon' => 'icon-code',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Name', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'name' => 'name',
                    'required' => true,
                    'maxlength' => 255,
                ],
                [
                    'type' => 'radio',
                    'label' => $this->trans('Type', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'name' => 'type',
                    'required' => true,
                    'class' => 't',
                    'values' => [
                        ['id' => 'type_js', 'value' => 'js', 'label' => 'JavaScript'],
                        ['id' => 'type_css', 'value' => 'css', 'label' => 'CSS'],
                    ],
                    // HelperForm renders desc/label as raw HTML, so the angle
                    // brackets are written as entities — a literal <style> here
                    // would put the browser into RAWTEXT mode and swallow the
                    // rest of the form (Code/Location/Load when/Active/Save).
                    'desc' => $this->trans('CSS is injected as inline &lt;style&gt;; JS as inline &lt;script&gt;.', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->trans('Code', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'name' => 'code',
                    'required' => true,
                    'cols' => 80,
                    'rows' => 20,
                    'class' => 'asec-code-textarea',
                    'desc' => $this->trans('The snippet code is injected verbatim into the front-end. Test on staging first.', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                ],
                [
                    'type' => 'radio',
                    'label' => $this->trans('Location', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'name' => 'location',
                    'required' => true,
                    'class' => 't',
                    'values' => [
                        ['id' => 'location_head', 'value' => 'head', 'label' => $this->trans('Inline in &lt;head&gt;', [], 'Modules.Aplinesimpleeditcssjs.Admin')],
                        ['id' => 'location_body_end', 'value' => 'body_end', 'label' => $this->trans('Just before &lt;/body&gt; (JS only)', [], 'Modules.Aplinesimpleeditcssjs.Admin')],
                    ],
                ],
                [
                    'type' => 'radio',
                    'label' => $this->trans('Load when', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'name' => 'load_when',
                    'required' => true,
                    'class' => 't',
                    'values' => [
                        ['id' => 'load_immediate', 'value' => 'immediate', 'label' => $this->trans('Execute immediately', [], 'Modules.Aplinesimpleeditcssjs.Admin')],
                        ['id' => 'load_on_ready', 'value' => 'on_ready', 'label' => $this->trans('After DOMContentLoaded', [], 'Modules.Aplinesimpleeditcssjs.Admin')],
                    ],
                    'desc' => $this->trans('Only applies to JavaScript snippets.', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Active', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                    ],
                ],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];

        // Default values for a brand-new snippet (radios need an initial pick).
        if (!$isEdit) {
            $this->fields_value['type'] = 'js';
            $this->fields_value['location'] = 'head';
            $this->fields_value['load_when'] = 'on_ready';
            $this->fields_value['active'] = 1;
        }

        $form = parent::renderForm();

        return $form . $this->renderFormExtras($isEdit, $obj);
    }

    /**
     * Extra UI appended after the HelperForm output:
     * - the "Format CSS" button and the "Previous versions" panel are injected
     *   into the form via JS (HelperForm cannot place them itself);
     * - a small style for the code textarea (monospace);
     * - the type-toggle script (show/hide location, load_when and the Format
     *   CSS button depending on the chosen type).
     *
     * The AJAX wiring for Format CSS and "Load into editor" is added in
     * Checkpoint 05.
     *
     * @param bool $isEdit
     * @param AplineSimpleEditCssJsSnippet|false $obj
     *
     * @return string
     */
    private function renderFormExtras($isEdit, $obj)
    {
        $versionsHtml = '';
        if ($isEdit) {
            $versions = AplineSimpleEditCssJsSnippet::getVersions((int) $obj->id);
            if (!empty($versions)) {
                $rows = '';
                foreach ($versions as $v) {
                    $rows .= '<li style="margin:4px 0;">'
                        . '<code>' . htmlspecialchars((string) $v['date_add'], ENT_QUOTES) . '</code> '
                        . '<button type="button" class="btn btn-default btn-xs asec-load-version" '
                        . 'data-version-id="' . (int) $v['id_asec_snippet_version'] . '">'
                        . $this->trans('Load into editor', [], 'Modules.Aplinesimpleeditcssjs.Admin')
                        . '</button></li>';
                }
                $versionsHtml = '<div class="panel asec-versions-panel" style="display:none;">'
                    . '<h3><i class="icon-history"></i> '
                    . $this->trans('Previous versions (last 3)', [], 'Modules.Aplinesimpleeditcssjs.Admin')
                    . '</h3><ul style="list-style:none;padding-left:0;margin:0;">' . $rows . '</ul>'
                    . '<p class="text-muted" style="font-size:12px;">'
                    . $this->trans('Loading a version only fills the editor. You still have to Save to apply it.', [], 'Modules.Aplinesimpleeditcssjs.Admin')
                    . '</p></div>';
            }
        }

        $editVersionsForJs = $isEdit ? (int) $obj->id : 0;
        $formatBtnLabel = $this->trans('Format CSS', [], 'Modules.Aplinesimpleeditcssjs.Admin');

        // AJAX endpoint for this controller, token included. The action name is
        // appended by the JS (FormatCss / LoadVersion).
        $ajaxUrl = $this->context->link->getAdminLink('AdminAplineSimpleEditCssJsSnippet');
        $formatErr = $this->trans('Could not format the CSS.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        $versionErr = $this->trans('Could not load that version.', [], 'Modules.Aplinesimpleeditcssjs.Admin');

        // The script reads the chosen type and toggles related rows. It also
        // exposes hooks (asec-format-css button, version id) consumed by the
        // Checkpoint 05 AJAX layer.
        $script = "
        <style>
            textarea[name='code'], textarea.asec-code-textarea {
                font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
                font-size: 13px; white-space: pre; tab-size: 4;
            }
            .asec-format-css-wrap { margin: 6px 0 0; }
        </style>
        <script type=\"text/javascript\">
        (function () {
            var snippetId = " . $editVersionsForJs . ";
            var ajaxUrl = " . json_encode($ajaxUrl) . ";
            var formatErr = " . json_encode($formatErr) . ";
            var versionErr = " . json_encode($versionErr) . ";
            function ready(fn){ if(document.readyState!='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);} }
            function codeEl(){ return document.querySelector('textarea[name=\"code\"]'); }
            function post(params){
                var body = Object.keys(params).map(function(k){
                    return encodeURIComponent(k)+'='+encodeURIComponent(params[k]);
                }).join('&');
                return fetch(ajaxUrl, {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
                    body: body,
                    credentials:'same-origin'
                }).then(function(r){ return r.json(); });
            }
            function bindFormatCss(){
                var btn = document.getElementById('asec-format-css');
                var ta = codeEl();
                if (!btn || !ta || btn.getAttribute('data-bound')) return;
                btn.setAttribute('data-bound','1');
                btn.addEventListener('click', function(){
                    post({ajax:1, action:'FormatCss', code: ta.value}).then(function(res){
                        if (res && res.success) { ta.value = res.code; }
                        else { alert((res && res.error) ? res.error : formatErr); }
                    }).catch(function(){ alert(formatErr); });
                });
            }
            function bindLoadVersions(){
                var ta = codeEl();
                var btns = document.querySelectorAll('.asec-load-version');
                for (var i=0;i<btns.length;i++){
                    (function(b){
                        if (b.getAttribute('data-bound')) return;
                        b.setAttribute('data-bound','1');
                        b.addEventListener('click', function(){
                            post({ajax:1, action:'LoadVersion', id_asec_snippet: snippetId, id_asec_snippet_version: b.getAttribute('data-version-id')}).then(function(res){
                                if (res && res.success) { if (ta) ta.value = res.code; }
                                else { alert((res && res.error) ? res.error : versionErr); }
                            }).catch(function(){ alert(versionErr); });
                        });
                    })(btns[i]);
                }
            }
            function rowOf(name){ var el=document.querySelector('[name=\"'+name+'\"]'); return el?el.closest('.form-group'):null; }
            function currentType(){ var c=document.querySelector('input[name=\"type\"]:checked'); return c?c.value:'js'; }
            function injectFormatButton(){
                if (document.getElementById('asec-format-css')) return;
                var codeRow = rowOf('code');
                if (!codeRow) return;
                var wrap = document.createElement('div');
                wrap.className = 'asec-format-css-wrap';
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.id = 'asec-format-css';
                btn.className = 'btn btn-default btn-sm';
                btn.innerHTML = '<i class=\"icon-magic\"></i> " . addslashes($formatBtnLabel) . "';
                wrap.appendChild(btn);
                var field = codeRow.querySelector('.col-lg-9') || codeRow;
                field.appendChild(wrap);
            }
            function moveVersionsPanel(){
                var panel = document.querySelector('.asec-versions-panel');
                var codeRow = rowOf('code');
                if (panel && codeRow && codeRow.parentNode) {
                    codeRow.parentNode.insertBefore(panel, codeRow.nextSibling);
                }
            }
            function toggle(){
                var t = currentType();
                var loc = rowOf('location'), lw = rowOf('load_when');
                var fmt = document.getElementById('asec-format-css');
                if (lw) lw.style.display = (t === 'js') ? '' : 'none';
                // body_end option only valid for JS; show the whole location row always
                // but disable body_end for CSS.
                var bodyOpt = document.getElementById('location_body_end');
                if (bodyOpt) {
                    bodyOpt.disabled = (t === 'css');
                    if (t === 'css' && bodyOpt.checked) {
                        var headOpt = document.getElementById('location_head');
                        if (headOpt) headOpt.checked = true;
                    }
                }
                if (fmt) fmt.style.display = (t === 'css') ? '' : 'none';
                var panel = document.querySelector('.asec-versions-panel');
                if (panel) panel.style.display = '';
            }
            ready(function(){
                injectFormatButton();
                moveVersionsPanel();
                toggle();
                bindFormatCss();
                bindLoadVersions();
                var radios = document.querySelectorAll('input[name=\"type\"]');
                for (var i=0;i<radios.length;i++){ radios[i].addEventListener('change', toggle); }
                window.asecSnippetId = snippetId;
            });
        })();
        </script>";

        return $versionsHtml . $script;
    }

    public function postProcess()
    {
        $isAdd = Tools::isSubmit('submitAdd' . $this->table) && !Tools::getValue($this->identifier);
        $isUpdate = Tools::isSubmit('submitAdd' . $this->table) && Tools::getValue($this->identifier);

        if ($isAdd || $isUpdate) {
            if ($isUpdate) {
                $existing = new AplineSimpleEditCssJsSnippet((int) Tools::getValue($this->identifier));
                if (!Validate::isLoadedObject($existing)) {
                    $this->errors[] = $this->trans('The snippet you are trying to edit does not exist.', [], 'Modules.Aplinesimpleeditcssjs.Admin');

                    return false;
                }
            }

            if (!$this->handleSubmission()) {
                // Errors already pushed: abort before any DB write and keep the
                // form open so the user can fix the input.
                $this->display = $isUpdate ? 'edit' : 'add';

                return false;
            }
        }

        return parent::postProcess();
    }

    /**
     * Rejecting validation (never silently truncates). On failure, populate
     * $this->errors and return false (no save happens). On success, the
     * validated values are written back into $_POST for the standard
     * ObjectModel save flow.
     *
     * @return bool
     */
    private function handleSubmission()
    {
        $name = trim((string) Tools::getValue('name'));
        $type = strtolower(trim((string) Tools::getValue('type')));
        $code = (string) Tools::getValue('code');
        $location = trim((string) Tools::getValue('location'));
        $loadWhen = trim((string) Tools::getValue('load_when'));

        // 1. Name required + max length (reject, never truncate).
        if ($name === '') {
            $this->errors[] = $this->trans('The field "Name" is required.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        } elseif (mb_strlen($name) > self::MAX_STRING) {
            $this->errors[] = $this->trans('The field "Name" exceeds the maximum length of 255 characters.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        // 2. Type whitelist.
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $this->errors[] = $this->trans('Invalid snippet type. Allowed: CSS, JS.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        // 3. Code required (an empty snippet is meaningless).
        if (trim($code) === '') {
            $this->errors[] = $this->trans('The field "Code" is required — an empty snippet has no effect.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        // 4. Location whitelist, dependent on type.
        if (!in_array($location, self::ALLOWED_LOCATIONS, true)) {
            $this->errors[] = $this->trans('Invalid location.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        } elseif ($type === 'css' && $location !== 'head') {
            // CSS in body_end works but is bad practice — reject it.
            // Error messages are rendered as HTML by displayError; escape the tag.
            $this->errors[] = $this->trans('CSS snippets must be placed in &lt;head&gt;.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        // 5. Load_when whitelist (meaningful for JS; kept for CSS for field consistency).
        if (!in_array($loadWhen, self::ALLOWED_LOAD_WHEN, true)) {
            $this->errors[] = $this->trans('Invalid "Load when" value.', [], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        // 6. CSS balance check (only for CSS).
        if ($type === 'css' && trim($code) !== '' && !$this->cssBracesBalanced($code)) {
            $counts = $this->cssBraceCounts($code);
            $this->errors[] = $this->trans('Unbalanced curly braces in CSS — found %1$d opening and %2$d closing.', [$counts['open'], $counts['close']], 'Modules.Aplinesimpleeditcssjs.Admin');
        }

        if (!empty($this->errors)) {
            return false;
        }

        // Auto-format CSS on save when enabled in the module configuration.
        if ($type === 'css' && (int) Configuration::get(apline_simple_edit_css_js::FORMAT_CSS_KEY) === 1) {
            $code = self::formatCss($code);
        }

        // Feed validated values into the standard ObjectModel save flow.
        $_POST['name'] = $name;
        $_POST['type'] = $type;
        $_POST['code'] = $code;
        $_POST['location'] = $location;
        $_POST['load_when'] = $loadWhen;

        return true;
    }

    /**
     * Count opening and closing curly braces in CSS, with the contents of
     * block comments removed first so braces inside them are not counted.
     *
     * @param string $css
     *
     * @return array{open:int, close:int}
     */
    protected function cssBraceCounts($css)
    {
        // Strip block comments first so braces inside them do not count.
        $stripped = preg_replace('!/\*.*?\*/!s', '', (string) $css);

        return [
            'open' => substr_count($stripped, '{'),
            'close' => substr_count($stripped, '}'),
        ];
    }

    /**
     * @param string $css
     *
     * @return bool whether '{' and '}' are balanced (comment contents ignored)
     */
    protected function cssBracesBalanced($css)
    {
        $counts = $this->cssBraceCounts($css);

        return $counts['open'] === $counts['close'];
    }

    public function ajaxProcessUpdatePositions()
    {
        $positions = Tools::getValue($this->table);

        if (!is_array($positions)) {
            die(json_encode(['success' => false]));
        }

        // The posted array order is the new visual order; assign 1..n.
        $pos = 1;
        foreach ($positions as $value) {
            $parts = explode('_', (string) $value);
            $id = (int) end($parts);
            if (!$id) {
                continue;
            }
            Db::getInstance()->update(
                'asec_snippet',
                ['position' => $pos++],
                'id_asec_snippet = ' . $id
            );
        }

        die(json_encode(['success' => true]));
    }

    /**
     * AJAX: format a CSS string and return it. Used by the "Format CSS" button.
     * Token security is handled by the AdminController ajax framework.
     */
    public function ajaxProcessFormatCss()
    {
        $css = (string) Tools::getValue('code');

        if (!$this->cssBracesBalanced($css)) {
            $counts = $this->cssBraceCounts($css);
            die(json_encode([
                'success' => false,
                'error' => $this->trans('Unbalanced curly braces in CSS — found %1$d opening and %2$d closing.', [$counts['open'], $counts['close']], 'Modules.Aplinesimpleeditcssjs.Admin'),
            ]));
        }

        die(json_encode(['success' => true, 'code' => self::formatCss($css)]));
    }

    /**
     * AJAX: return the stored code of a previous version, for "Load into editor".
     * The version must belong to the snippet currently being edited (ownership
     * guard) — a posted version id for another snippet is rejected.
     */
    public function ajaxProcessLoadVersion()
    {
        $idVersion = (int) Tools::getValue('id_asec_snippet_version');
        $idSnippet = (int) Tools::getValue('id_asec_snippet');

        $code = AplineSimpleEditCssJsSnippet::getVersionCode($idVersion, $idSnippet);

        if ($code === null) {
            die(json_encode([
                'success' => false,
                'error' => $this->trans('That version was not found for this snippet.', [], 'Modules.Aplinesimpleeditcssjs.Admin'),
            ]));
        }

        die(json_encode(['success' => true, 'code' => $code]));
    }

    /**
     * A small dependency-free CSS pretty-printer. It is intentionally simple: a
     * character-state walk that puts one declaration per line, indents nested
     * blocks (e.g. media queries), normalizes spacing around ':' and ';', and
     * keeps block comments verbatim. It is not a full CSS parser; for exotic
     * input the admin can disable auto-format and format the CSS elsewhere.
     *
     * @param string $css
     *
     * @return string
     */
    public static function formatCss($css)
    {
        $css = (string) $css;
        $len = mb_strlen($css);
        $out = '';
        $indent = 0;
        $i = 0;
        $atLineStart = true;

        $pad = function ($level) {
            return str_repeat('    ', max(0, $level));
        };
        $trimRight = function (&$buf) {
            $buf = rtrim($buf, " \t");
        };

        while ($i < $len) {
            $ch = mb_substr($css, $i, 1);
            $next = ($i + 1 < $len) ? mb_substr($css, $i + 1, 1) : '';

            // Preserve block comments verbatim.
            if ($ch === '/' && $next === '*') {
                $end = mb_strpos($css, '*/', $i + 2);
                if ($end === false) {
                    $end = $len - 2;
                }
                $comment = mb_substr($css, $i, $end - $i + 2);
                if (!$atLineStart) {
                    $trimRight($out);
                    $out .= "\n";
                }
                $out .= $pad($indent) . $comment . "\n";
                $atLineStart = true;
                $i = $end + 2;
                continue;
            }

            if ($ch === '{') {
                $trimRight($out);
                $out = rtrim($out, "\n");
                $out .= ' {' . "\n";
                $indent++;
                $atLineStart = true;
                $i++;
                continue;
            }

            if ($ch === '}') {
                $indent--;
                $trimRight($out);
                $out = rtrim($out, "\n");
                $out .= "\n" . $pad($indent) . '}' . "\n";
                // Blank line after a top-level rule for readability.
                if ($indent === 0) {
                    $out .= "\n";
                }
                $atLineStart = true;
                $i++;
                continue;
            }

            if ($ch === ';') {
                $trimRight($out);
                $out .= ';' . "\n";
                $atLineStart = true;
                $i++;
                continue;
            }

            if ($ch === ':') {
                // Space after the colon, none before (declaration separator).
                $trimRight($out);
                $out .= ': ';
                // Skip following whitespace.
                $i++;
                while ($i < $len && in_array(mb_substr($css, $i, 1), [' ', "\t"], true)) {
                    $i++;
                }
                $atLineStart = false;
                continue;
            }

            // Collapse newlines/tabs/leading whitespace between tokens.
            if ($ch === "\n" || $ch === "\r" || $ch === "\t") {
                $i++;
                continue;
            }
            if ($ch === ' ' && $atLineStart) {
                $i++;
                continue;
            }

            if ($atLineStart) {
                $out .= $pad($indent);
                $atLineStart = false;
            }
            $out .= $ch;
            $i++;
        }

        // Collapse 3+ blank lines to a single blank line, trim edges.
        $out = preg_replace("/\n{3,}/", "\n\n", $out);

        return trim($out) . "\n";
    }
}

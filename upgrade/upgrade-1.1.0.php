<?php
/**
 * APLINE Simple Edit CSS/JS module for PrestaShop 9.
 *
 * Upgrade to 1.1.0:
 *  - the hidden back-office tab gets its Polish name (tab names are stored in
 *    the database, so the new source text alone would not change them);
 *  - the Smarty cache is cleared so the new configuration template shows up.
 *
 * Snippets, their version history and the module settings are not touched, and
 * no new configuration keys are introduced in this version.
 *
 * @author    Arkadiusz Pielechowski
 * @copyright Arkadiusz Pielechowski
 * @license   MIT - see LICENSE.md
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Module $module
 *
 * @return bool
 */
function upgrade_module_1_1_0($module)
{
    try {
        $idTab = (int) Tab::getIdFromClassName('AdminAplineSimpleEditCssJsSnippet');
        if ($idTab) {
            $tab = new Tab($idTab);
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int) $lang['id_lang']] = 'Fragmenty CSS/JS';
            }
            if (!$tab->update()) {
                PrestaShopLogger::addLog('apline_simple_edit_css_js 1.1.0: nie udało się zmienić nazwy zakładki', 2);
            }
        }

        if (method_exists('Tools', 'clearSmartyCache')) {
            Tools::clearSmartyCache();
        }
    } catch (\Throwable $e) {
        // The tab name is cosmetic: never block the module update because of it.
        PrestaShopLogger::addLog('apline_simple_edit_css_js 1.1.0: ' . $e->getMessage(), 2);
    }

    return true;
}

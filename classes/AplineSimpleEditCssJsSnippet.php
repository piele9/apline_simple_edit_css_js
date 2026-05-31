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

class AplineSimpleEditCssJsSnippet extends ObjectModel
{
    /** Keep at most this many code versions per snippet. */
    const MAX_VERSIONS = 3;

    /** @var string */
    public $name;
    /** @var string css|js */
    public $type;
    /** @var string */
    public $code;
    /** @var string head|body_end */
    public $location;
    /** @var string immediate|on_ready */
    public $load_when;
    /** @var bool */
    public $active;
    /** @var int */
    public $position;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     *
     * `code` is TYPE_HTML with isString validation: it accepts any non-array
     * value and is NOT escaped on save, so raw CSS/JS (with < > &) round-trips
     * intact. The DB column is LONGTEXT (no length cap in the definition).
     */
    public static $definition = [
        'table' => 'asec_snippet',
        'primary' => 'id_asec_snippet',
        'multilang' => false,
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 3],
            'code' => ['type' => self::TYPE_HTML, 'validate' => 'isString'],
            'location' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'load_when' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Active snippets of a given type (and optional location) ordered for the
     * front render. Guarded so a missing table never breaks the shop front.
     *
     * Reads with a raw query (not ObjectModel) so `code` comes back exactly as
     * stored, including '<', '>' and '&'.
     *
     * @param string $type css|js
     * @param string|null $location head|body_end (null = any)
     *
     * @return array
     */
    public static function getActiveSnippets($type, $location = null)
    {
        try {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'asec_snippet`
                WHERE `active` = 1 AND `type` = "' . pSQL($type) . '"';
            if ($location !== null) {
                $sql .= ' AND `location` = "' . pSQL($location) . '"';
            }
            $sql .= ' ORDER BY `position` ASC, `id_asec_snippet` ASC';

            $result = Db::getInstance()->executeS($sql);

            return is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return int next free position
     */
    public static function getNextPosition()
    {
        try {
            $max = (int) Db::getInstance()->getValue(
                'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . 'asec_snippet`'
            );

            return $max + 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * @see ObjectModel::add()
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (empty($this->position)) {
            $this->position = self::getNextPosition();
        }

        return parent::add($auto_date, $null_values);
    }

    /**
     * @see ObjectModel::update()
     *
     * Versioning lives here (not in the controller) so EVERY save path — admin
     * form today, a hypothetical API tomorrow — snapshots the previous code.
     * A new version is created only when `code` actually changed; saving an
     * unchanged snippet creates no version (no garbage snapshots).
     */
    public function update($null_values = false)
    {
        try {
            $oldCode = Db::getInstance()->getValue(
                'SELECT `code` FROM `' . _DB_PREFIX_ . 'asec_snippet`
                 WHERE `id_asec_snippet` = ' . (int) $this->id
            );

            // getValue returns false when the row is missing; (string) makes the
            // comparison safe and only snapshots when the code really differs.
            if ($oldCode !== false && (string) $oldCode !== (string) $this->code) {
                $this->snapshotVersion((int) $this->id, (string) $oldCode);
            }
        } catch (\Throwable $e) {
            // Versioning is best-effort: never block the actual save because the
            // history table is missing or unwritable.
            PrestaShopLogger::addLog('apline_simple_edit_css_js (version snapshot): ' . $e->getMessage(), 2);
        }

        return parent::update($null_values);
    }

    /**
     * @see ObjectModel::delete()
     *
     * App-level cascade as a belt-and-suspenders alongside the DB foreign key:
     * if the FK was not created (e.g. a legacy MyISAM install), versions are
     * still cleaned up here so no orphans are left behind.
     */
    public function delete()
    {
        try {
            Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'asec_snippet_version`
                 WHERE `id_asec_snippet` = ' . (int) $this->id
            );
        } catch (\Throwable $e) {
            // Ignore: the FK cascade likely handled it, or the table is gone.
        }

        return parent::delete();
    }

    /**
     * Insert a snapshot of the previous code, then trim to MAX_VERSIONS newest.
     *
     * @param int $idSnippet
     * @param string $oldCode
     */
    private function snapshotVersion($idSnippet, $oldCode)
    {
        Db::getInstance()->insert('asec_snippet_version', [
            'id_asec_snippet' => $idSnippet,
            'code' => pSQL($oldCode, true),
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        // Keep only the newest MAX_VERSIONS rows for this snippet. The derived
        // table is mandatory: MySQL forbids deleting from a table referenced in
        // a subquery without wrapping it.
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'asec_snippet_version`
             WHERE `id_asec_snippet` = ' . (int) $idSnippet . '
               AND `id_asec_snippet_version` NOT IN (
                   SELECT `id` FROM (
                       SELECT `id_asec_snippet_version` AS `id`
                       FROM `' . _DB_PREFIX_ . 'asec_snippet_version`
                       WHERE `id_asec_snippet` = ' . (int) $idSnippet . '
                       ORDER BY `date_add` DESC, `id_asec_snippet_version` DESC
                       LIMIT ' . (int) self::MAX_VERSIONS . '
                   ) AS tmp
               )'
        );
    }

    /**
     * Version list for a snippet (newest first, capped at MAX_VERSIONS), for the
     * "Previous versions" panel. Only the timestamp + id are needed there.
     * Guarded so a missing history table never breaks the edit form.
     *
     * @param int $idSnippet
     *
     * @return array each: id_asec_snippet_version, date_add
     */
    public static function getVersions($idSnippet)
    {
        try {
            $sql = 'SELECT `id_asec_snippet_version`, `date_add`
                FROM `' . _DB_PREFIX_ . 'asec_snippet_version`
                WHERE `id_asec_snippet` = ' . (int) $idSnippet . '
                ORDER BY `date_add` DESC, `id_asec_snippet_version` DESC
                LIMIT ' . (int) self::MAX_VERSIONS;

            $result = Db::getInstance()->executeS($sql);

            return is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * The stored code of one version — but ONLY when that version belongs to the
     * given snippet. This ownership guard prevents loading another snippet's
     * version by tampering with the posted version id.
     *
     * @param int $idVersion
     * @param int $idSnippet
     *
     * @return string|null the code, or null when not found / not owned
     */
    public static function getVersionCode($idVersion, $idSnippet)
    {
        try {
            $code = Db::getInstance()->getValue(
                'SELECT `code` FROM `' . _DB_PREFIX_ . 'asec_snippet_version`
                 WHERE `id_asec_snippet_version` = ' . (int) $idVersion . '
                   AND `id_asec_snippet` = ' . (int) $idSnippet
            );

            return $code === false ? null : (string) $code;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

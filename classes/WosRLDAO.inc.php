<?php

/**
 * @file plugins/generic/wosReviewerLocator/classes/WosrlDAO.php
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WosrlDAO
 *
 * @brief Operations for retrieving and modifying wosrl_submissions_settings records.
 */

import('lib.pkp.classes.db.DAO');

class WosRLDAO extends DAO {

    /**
     * Insert a new record
     *
     * @param int $submission_id
     * @param array $data
     * @return int|null
     */
    function insertObject(int $submission_id, array $data)
    {
        $fields = array_merge([
            'submission_id' => $submission_id,
            'locale' => AppLocale::getLocale(),
            'token' => null,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d')
        ], $data);
        $keys = implode(', ', array_keys($fields));
        $replacements = implode(', ', array_fill(0, count($fields), '?'));
        return $this->update('INSERT INTO wosrl_submission_tokens (' . $keys . ') VALUES (' . $replacements . ')', array_values($fields));
    }

    /**
     * Return saved token
     *
     * @param int $submission_id
     * @return mixed
     */
    function getToken(int $submission_id)
    {
        $resource = $this->retrieve('SELECT * FROM wosrl_submission_tokens WHERE submission_id = ? AND locale = ?',
            [$submission_id, AppLocale::getLocale()]);
        $result = isset($resource->fields[0]) && $resource->fields[0] != 0 ? $resource->fields : null;
        $resource->Close();
        unset($resource);
        return $result;
    }

    /**
     * Return next auto incremental ID, request usage
     *
     * @return mixed
     */
    function getNextId()
    {
        $resource = $this->retrieve('SHOW TABLE STATUS WHERE Name = ?', ['wosrl_submission_tokens']);
        $result = isset($resource->fields[0]) && $resource->fields[0] !== 0 ? $resource->fields : null;
        $resource->Close();
        unset($resource);
        return $result ? $result['Auto_increment'] + 1 : 1;
    }

    /**
     * Delete submission related records
     *
     * @param int $submission_id
     * @return bool|int|null
     */
    function deleteObject(int $submission_id)
    {
        return $this->update('DELETE FROM wosrl_submission_tokens WHERE submission_id = ?', [$submission_id]);
    }

}

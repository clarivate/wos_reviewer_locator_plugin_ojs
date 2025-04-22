<?php

/**
 * @file plugins/generic/wosReviewerLocator/classes/wosrlDAO.php
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class wosrlDAO
 *
 * @brief Operations for retrieving and modifying wosrl_submissions_settings records.
 */

namespace APP\plugins\generic\wosReviewerLocator\classes;

use Generator;
use PKP\db\DAO;
use PKP\facades\Locale;

class wosRLDAO extends DAO {

    /**
     * Insert a new record
     *
     * @param int $submission_id
     * @param array $data
     * @return int|null
     */
    function insertObject(int $submission_id, array $data): int|null
    {
        $fields = array_merge([
            'submission_id' => $submission_id,
            'locale' => Locale::getLocale(),
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
    function getToken(int $submission_id): mixed
    {
        return $this->retrieve('SELECT * FROM wosrl_submission_tokens WHERE submission_id = ? AND locale = ?',
            [$submission_id, Locale::getLocale()])->current();
    }

    /**
     * Return next auto incremental ID, request usage
     *
     * @return mixed
     */
    function getNextId(): mixed
    {
        $current = $this->retrieve('SHOW TABLE STATUS WHERE Name = ?', ['wosrl_submission_tokens'])->current();
        return $current->Auto_increment + 1;
    }

    /**
     * Delete submission related records
     *
     * @param int $submission_id
     * @return bool|int|null
     */
    function deleteObject(int $submission_id): bool|int|null
    {
        return $this->update('DELETE FROM wosrl_submission_tokens WHERE submission_id = ?', [$submission_id]);
    }

}

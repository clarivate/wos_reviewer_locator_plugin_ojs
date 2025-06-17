<?php

/**
 * @defgroup plugins_generic_wosReviewerLocator
 */

/**
 * @file plugins/generic/wosReviewerLocator/index.php
 *
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @ingroup plugins_generic_wosReviewerLocator
 * @brief Wrapper for Web of Science Reviewer Locator plugin.
 *
 */

require_once('WosReviewerLocatorPlugin.inc.php');
return new WosReviewerLocatorPlugin();

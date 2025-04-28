{**
 * plugins/generic/wosReviewerLocator/templates/list.tpl
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Web of Science - Reviewer Locator plugin - list template
 *}

<div id="wosRLToolbar">
    <table>
        <tbody>
        <tr>
            <td>
                <h3>{translate key="plugins.generic.wosrl.toolbar.title"}</h3>
            </td>
            <td id="wosRLItems">
                <span>{translate key="plugins.generic.wosrl.toolbar.items"}:</span>
                <select name="wosrl-item-count" onchange="wosRLPagination()" class="pkpFormField__input pkpFormField--select__input">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="30">30</option>
                    <option value="100">100</option>
                </select>
            </td>
            <td align="right" id="wosRLPagination">
            </td>
        </tr>
        </tbody>
    </table>
</div>
<div id="wosRLList">
    <table>
        <tbody>
        {foreach $reviewers as $reviewer_index => $reviewer}
            <tr>
                <td width="32%">
                    <span class="wosrl-title">{$reviewer->lastName}, {$reviewer->firstName}</span>
                    <span class="wosrl-subtitle">
                        {translate key="plugins.generic.wosrl.list.article_count" total=$reviewer->numWosArticles|escape}
                        | {translate key="plugins.generic.wosrl.list.review_count" total=$reviewer->numVerifiedReviews|escape}
                    </span>
                    {foreach $reviewer->recentOrganizations|array_slice:0:3 as $organization}
                        <span>{$organization->firstYear} - {$organization->lastYear} {$organization->name}, {$organization->country}</span>
                    {/foreach}
                    <div class="wosrl-card">
                        <span class="wosrl-profile"><a href="{$reviewer->profileUrl}" target="_blank">{translate key="plugins.generic.wosrl.list.profile"}</a></span>
                        {if $reviewer->orcid}
                            <span class="wosrl-orcid"><a href="https://orcid.org/{$reviewer->orcid}" target="_blank">{$reviewer->orcid}</a></span>
                        {/if}
                        {foreach $reviewer->contact->emails as $email}
                            <span><a href="mailto:{$email->email}">{$email->email}</a></span>
                        {/foreach}
                    </div>
                    {if $reviewer->conflictOfInterestArticles || $reviewer->conflictOfInterestOrganizations}
                        <div class="wosrl-actions">
                            <button type="button" class="pkp_button" onclick="return wosRLConflictToggle(this, {$reviewer_index});"
                                    data-view="{translate key="plugins.generic.wosrl.list.conflict_view"}"
                                    data-hide="{translate key="plugins.generic.wosrl.list.conflict_hide"}">
                                <b>!</b> {translate key="plugins.generic.wosrl.list.conflict_view"}
                            </button>
                        </div>
                    {/if}
                </td>
                <td>
                    <table class="wosrl-publications">
                        <tbody>
                        <tr>
                            <th>{translate key="plugins.generic.wosrl.list.relevant_publications"}</th>
                            <th>{translate key="plugins.generic.wosrl.list.citations"}</th>
                        </tr>
                        {foreach $reviewer->relevantArticles as $article}
                            <tr>
                                <td>
                                    <a href="https://www.webofscience.com/wos/woscc/full-record/{$article->ut}" target="_blank">"{$article->title}"</a>
                                    ({$article->publicationYear}) {$article->journal->name}
                                </td>
                                <td align="right">{$article->citationCount}</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                    <table class="wosrl-details">
                        <tbody>
                        <tr>
                            <th width="45%">{translate key="plugins.generic.wosrl.list.keywords"}</th>
                            <th class="wosrl-bl">{translate key="plugins.generic.wosrl.list.experience"}</th>
                            <th align="right">{translate key="plugins.generic.wosrl.list.reviews"}</th>
                        </tr>
                        <tr>
                            <td>
                                {foreach $reviewer->keywords as $keyword}
                                    {$keyword->name}{if $keyword@last}{else},{/if}
                                {/foreach}
                            </td>
                            <td class="wosrl-bl" colspan="2" style="padding: 0;">
                                <table>
                                    <tbody>
                                    {foreach $reviewer->reviewerExperience|array_slice:0:3 as $experience}
                                        <tr>
                                            <td>{$experience->journal->name}</td>
                                            <td align="right">{$experience->numVerifiedReviews}</td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            {if $reviewer->conflictOfInterestArticles || $reviewer->conflictOfInterestOrganizations}
                <tr class="wosrl-conflict" data-name="wosrl-conflict-{$reviewer_index}">
                    <td colspan="2">
                        {if $reviewer->conflictOfInterestOrganizations}
                            <p class="wosrl-section">{translate key="plugins.generic.wosrl.list.conflict_affiliation"}</p>
                            <div class="wosrl-pt05">
                                {foreach $reviewer->conflictOfInterestOrganizations as $organization}
                                    <p>{$organization->firstYear} - {$organization->lastYear} {$organization->name}, {$organization->country}</p>
                                {/foreach}
                            </div>
                        {/if}
                        {if $reviewer->conflictOfInterestArticles}
                            <p class="wosrl-section{($reviewer->conflictOfInterestOrganizations) ? ' wosrl-pt05' : ''}">
                                {translate key="plugins.generic.wosrl.list.conflict_publication"}
                            </p>
                            <div class="wosrl-pt05">
                                {foreach $reviewer->conflictOfInterestArticles as $article}
                                    <p>
                                        <a href="https://www.webofscience.com/wos/woscc/full-record/{$article->ut}" target="_blank">"{$article->title}"</a>
                                        ({$article->publicationYear}) {$article->journal->name}<br>
                                    </p>
                                    <p>
                                        {translate key="plugins.generic.wosrl.list.conflict_by"}:
                                        {foreach $article->authors as $author}
                                            {$author->lastName}, {$author->firstName}{if $author@last}{else};{/if}
                                        {/foreach}
                                    </p>
                                {/foreach}
                            </div>
                        {/if}
                    </td>
                </tr>
            {/if}
        {/foreach}
        </tbody>
    </table>
</div>
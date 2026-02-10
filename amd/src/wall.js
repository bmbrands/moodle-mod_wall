// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main wall module - handles threaded comment CRUD, voting, and state management.
 * 9gag-style: comment input on top, max 2-level threads, @mentions, up/downvotes.
 * Course page: compact bar with expand/collapse, AJAX-loaded comments.
 *
 * @module     mod_wall/wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Repository from 'mod_wall/repository';
import Templates from 'core/templates';
import Notification from 'core/notification';
import Pending from 'core/pending';
import WallState from 'mod_wall/wallstate';

/** @var {number} cmid - Course module ID */
let cmid = 0;
/** @var {number} wallid - Wall instance ID */
let wallid = 0;
/** @var {boolean} cancomment - Whether the user can comment */
let cancomment = true;
/** @var {boolean} enablevoting - Whether voting is enabled */
let enablevoting = false;
/** @var {boolean} subscribed - Whether the comment thread template is subscribed */
let subscribed = false;

/**
 * Initialize the wall module.
 *
 * @param {number} _cmid - Course module ID
 * @param {number} _wallid - Wall instance ID
 * @param {boolean} _cancomment - Whether the user can comment
 * @param {boolean} _enablevoting - Whether voting is enabled
 */
export const init = (_cmid, _wallid, _cancomment, _enablevoting) => {
    cmid = _cmid;
    wallid = _wallid;
    cancomment = _cancomment ?? true;
    enablevoting = _enablevoting ?? false;

    registerEventListeners();
    subscribeCommentThread();
};

/**
 * Register all event listeners using event delegation.
 */
const registerEventListeners = () => {
    document.addEventListener('click', (event) => {
        // Submit comment.
        const submitComment = event.target.closest('[data-action="submit-comment"]');
        if (submitComment) {
            event.preventDefault();
            handleSubmitComment(submitComment);
            return;
        }

        // Delete comment.
        const deleteComment = event.target.closest('[data-action="delete-comment"]');
        if (deleteComment) {
            event.preventDefault();
            handleDeleteComment(deleteComment);
            return;
        }

        // Reply to comment.
        const replyComment = event.target.closest('[data-action="reply-comment"]');
        if (replyComment) {
            event.preventDefault();
            handleReplyComment(replyComment);
            return;
        }

        // Vote on comment.
        const voteComment = event.target.closest('[data-action="vote-comment"]');
        if (voteComment) {
            event.preventDefault();
            handleVoteComment(voteComment);
            return;
        }

        // Toggle wall expand/collapse (course page).
        const toggleWall = event.target.closest('[data-action="toggle-wall"]');
        if (toggleWall) {
            event.preventDefault();
            handleToggleWall(toggleWall);
            return;
        }

        // Toggle replies visibility.
        const toggleReplies = event.target.closest('[data-action="toggle-replies"]');
        if (toggleReplies) {
            event.preventDefault();
            handleToggleReplies(toggleReplies);
            return;
        }

        // Vote on wall instance.
        const voteWall = event.target.closest('[data-action="vote-wall"]');
        if (voteWall) {
            event.preventDefault();
            handleVoteWall(voteWall);
            return;
        }
    });
};

/**
 * Subscribe the comment-thread template to state changes.
 * Re-renders the full thread when state updates.
 */
const subscribeCommentThread = () => {
    if (subscribed) {
        return;
    }
    subscribed = true;

    const stateKey = `comments-${wallid}`;

    const regionRenderer = async(data) => {
        const stateValue = data[stateKey];
        if (stateValue === undefined) {
            return;
        }
        const threadRegion = document.querySelector(
            `[data-region="comment-thread"][data-wallid="${wallid}"]`
        );
        if (!threadRegion) {
            return;
        }

        // Save open reply sections before re-render.
        const openReplies = new Set();
        threadRegion.querySelectorAll('[data-region="comment-replies"]').forEach(el => {
            if (el.style.display !== 'none') {
                openReplies.add(el.dataset.commentid);
            }
        });

        const context = {
            wallid: wallid,
            comments: stateValue.comments || [],
            hascomments: (stateValue.comments || []).length > 0,
            cancomment: stateValue.cancomment ?? cancomment,
            enablevoting: stateValue.enablevoting ?? enablevoting,
        };
        const {html, js} = await Templates.renderForPromise('mod_wall/comment_thread', context);
        Templates.replaceNodeContents(threadRegion, html, js);

        // Re-open previously open reply sections after re-render.
        openReplies.forEach(commentId => {
            const repliesRegion = threadRegion.querySelector(
                `[data-region="comment-replies"][data-commentid="${commentId}"]`
            );
            if (repliesRegion) {
                repliesRegion.style.display = '';
                const toggleLink = threadRegion.querySelector(
                    `[data-action="toggle-replies"][data-commentid="${commentId}"]`
                );
                if (toggleLink && toggleLink.dataset.hidetext) {
                    toggleLink.textContent = toggleLink.dataset.hidetext;
                }
            }
        });

        // Update course page summary bar counts.
        updateSummaryBar(stateValue.comments || []);
    };

    WallState.subscribe(stateKey, regionRenderer);
};

/**
 * Fetch comments from the server and store them in state.
 * This triggers the subscribed template to re-render.
 */
const fetchAndSetComments = async() => {
    const pending = new Pending('mod_wall/load-comments');
    try {
        const result = await Repository.getComments({cmid: cmid});
        const stateKey = `comments-${wallid}`;
        const enriched = enrichComments(result || [], wallid, cancomment);
        const detectedVoting = (result && result.length > 0 && result[0].enablevoting) ? true : enablevoting;
        WallState.setValue(stateKey, {
            comments: enriched,
            cancomment: cancomment,
            enablevoting: detectedVoting,
        });
    } catch (error) {
        Notification.exception(error);
    } finally {
        pending.resolve();
    }
};

/**
 * Handle expand/collapse of the wall on the course page.
 *
 * @param {HTMLElement} element - The toggle wall link.
 */
const handleToggleWall = async(element) => {
    const wallView = element.closest('[data-region="wall-view"]');
    if (!wallView) {
        return;
    }
    const expandedRegion = wallView.querySelector('[data-region="wall-expanded"]');
    if (!expandedRegion) {
        return;
    }

    const isHidden = expandedRegion.style.display === 'none';
    if (isHidden) {
        expandedRegion.style.display = '';
        // Fetch comments on first expand.
        if (!expandedRegion.dataset.loaded) {
            await fetchAndSetComments();
            expandedRegion.dataset.loaded = '1';
        }
    } else {
        expandedRegion.style.display = 'none';
    }
};

/**
 * Handle toggle replies visibility for a comment.
 *
 * @param {HTMLElement} element - The toggle replies link.
 */
const handleToggleReplies = (element) => {
    const commentId = element.dataset.commentid;
    const commentItem = element.closest('[data-region="comment-item"]');
    if (!commentItem) {
        return;
    }
    const repliesRegion = commentItem.querySelector(
        `[data-region="comment-replies"][data-commentid="${commentId}"]`
    );
    if (!repliesRegion) {
        return;
    }

    const isHidden = repliesRegion.style.display === 'none';
    repliesRegion.style.display = isHidden ? '' : 'none';

    // Update link text.
    if (isHidden && element.dataset.hidetext) {
        element.textContent = element.dataset.hidetext;
    } else if (!isHidden && element.dataset.viewtext) {
        element.textContent = element.dataset.viewtext;
    }
};

/**
 * Handle vote on the wall instance (independent score).
 *
 * @param {HTMLElement} button - The vote wall button.
 */
const handleVoteWall = async(button) => {
    const vote = Number(button.dataset.vote);

    const pending = new Pending('mod_wall/vote-wall');
    try {
        const result = await Repository.voteWall({
            cmid: cmid,
            vote: vote,
        });
        updateWallVoteBar(result);
    } catch (error) {
        Notification.exception(error);
    } finally {
        pending.resolve();
    }
};

/**
 * Update the wall vote bar UI after a vote.
 *
 * @param {Object} result - {upvotes, downvotes, score, uservote}
 */
const updateWallVoteBar = (result) => {
    const voteBar = document.querySelector('[data-region="wall-vote-bar"]');
    if (!voteBar) {
        return;
    }

    // Update score display.
    const scoreEl = voteBar.querySelector('[data-region="wall-score"]');
    if (scoreEl) {
        scoreEl.textContent = result.score;
        // Reset colour classes.
        scoreEl.classList.remove('text-primary', 'text-danger');
        if (result.uservote === 1) {
            scoreEl.classList.add('text-primary');
        } else if (result.uservote === -1) {
            scoreEl.classList.add('text-danger');
        }
    }

    // Update upvote button styling.
    const upBtn = voteBar.querySelector('[data-action="vote-wall"][data-vote="1"]');
    if (upBtn) {
        upBtn.classList.remove('text-primary', 'text-muted');
        upBtn.classList.add(result.uservote === 1 ? 'text-primary' : 'text-muted');
    }

    // Update downvote button styling.
    const downBtn = voteBar.querySelector('[data-action="vote-wall"][data-vote="-1"]');
    if (downBtn) {
        downBtn.classList.remove('text-danger', 'text-muted');
        downBtn.classList.add(result.uservote === -1 ? 'text-danger' : 'text-muted');
    }
};

/**
 * Update the summary bar on the course page with current counts.
 *
 * @param {Array} comments - Array of comment objects.
 */
const updateSummaryBar = (comments) => {
    const countEl = document.querySelector('[data-region="wall-commentcount"]');
    if (countEl) {
        countEl.textContent = countAllComments(comments);
    }
};

/**
 * Count total comments including replies.
 *
 * @param {Array} comments - Array of comment objects.
 * @return {number} Total count.
 */
const countAllComments = (comments) => {
    let count = 0;
    (comments || []).forEach(c => {
        count++;
        if (c.replies && c.replies.length) {
            count += c.replies.length;
        }
    });
    return count;
};

/**
 * Handle submit comment.
 *
 * @param {HTMLElement} button - The submit comment button.
 */
const handleSubmitComment = async(button) => {
    const parentId = button.dataset.parentid || 0;
    const commentForm = button.closest('[data-region="comment-form"]');
    const input = commentForm.querySelector('[data-region="comment-input"]');

    if (!input || !input.value.trim()) {
        return;
    }

    const pending = new Pending('mod_wall/add-comment');
    try {
        await Repository.addComment({
            cmid: cmid,
            content: input.value.trim(),
            parentid: parentId,
        });
        // Reload comments into state - triggers re-render.
        await fetchAndSetComments();
    } catch (error) {
        Notification.exception(error);
    } finally {
        pending.resolve();
    }
};

/**
 * Handle delete comment.
 *
 * @param {HTMLElement} button - The delete comment button.
 */
const handleDeleteComment = async(button) => {
    const commentId = button.dataset.commentid;

    const pending = new Pending('mod_wall/delete-comment');
    try {
        await Repository.deleteComment({cmid: cmid, commentid: commentId});
        // Reload comments into state - triggers re-render.
        await fetchAndSetComments();
    } catch (error) {
        Notification.exception(error);
    } finally {
        pending.resolve();
    }
};

/**
 * Handle reply to comment - toggle reply form via state.
 *
 * @param {HTMLElement} button - The reply button.
 */
const handleReplyComment = (button) => {
    const commentId = Number(button.dataset.commentid);
    const replyToName = button.dataset.fullname || '';
    const stateKey = `comments-${wallid}`;
    const stateValue = WallState.getValue(stateKey);

    if (!stateValue || !stateValue.comments) {
        // State not yet populated (initial PHP render). Fetch to populate, then toggle.
        fetchAndSetComments().then(() => {
            const sv = WallState.getValue(stateKey);
            if (sv && sv.comments) {
                const updated = toggleReplyForm(sv.comments, commentId, replyToName);
                WallState.setValue(stateKey, {...sv, comments: updated});
            }
        });
        return;
    }

    // Toggle showreplyform on the target comment in the state data.
    const updatedComments = toggleReplyForm(stateValue.comments, commentId, replyToName);
    WallState.setValue(stateKey, {
        ...stateValue,
        comments: updatedComments,
    });
};

/**
 * Handle vote on a comment.
 *
 * @param {HTMLElement} button - The vote button.
 */
const handleVoteComment = async(button) => {
    const commentId = button.dataset.commentid;
    const vote = Number(button.dataset.vote);

    const pending = new Pending('mod_wall/vote-comment');
    try {
        await Repository.voteComment({
            cmid: cmid,
            commentid: commentId,
            vote: vote,
        });
        // Reload comments to get updated vote counts.
        await fetchAndSetComments();
    } catch (error) {
        Notification.exception(error);
    } finally {
        pending.resolve();
    }
};

/**
 * Recursively toggle showreplyform on a comment in the tree.
 * Ensures only one reply form is open at a time.
 * For replies (level 2), the reply form targets the same comment
 * and the parentid stays correct for @mention resolution.
 *
 * @param {Array} comments - Array of comment objects.
 * @param {number} targetId - The comment ID to toggle.
 * @param {string} replyToName - The name of the user being replied to.
 * @return {Array} Updated comments array.
 */
const toggleReplyForm = (comments, targetId, replyToName) => {
    return comments.map(comment => {
        const isTarget = Number(comment.id) === targetId;
        const updatedComment = {
            ...comment,
            showreplyform: isTarget ? !comment.showreplyform : false,
            replytoname: isTarget ? replyToName : '',
        };
        if (comment.replies && comment.replies.length > 0) {
            updatedComment.replies = toggleReplyForm(comment.replies, targetId, replyToName);
            updatedComment.hasreplies = true;
        }
        return updatedComment;
    });
};

/**
 * Recursively enrich comment objects with wallid and cancomment for template rendering.
 *
 * @param {Array} comments - Array of comment objects.
 * @param {number|string} wallId - The wall instance ID.
 * @param {boolean} canComment - Whether commenting is allowed.
 * @return {Array} Enriched comments array.
 */
const enrichComments = (comments, wallId, canComment) => {
    return comments.map(comment => {
        const enriched = {
            ...comment,
            wallid: wallId,
            cancomment: canComment,
            showreplyform: false,
            userupvoted: comment.uservote === 1,
            userdownvoted: comment.uservote === -1,
        };
        if (comment.replies && comment.replies.length > 0) {
            enriched.replies = enrichComments(comment.replies, wallId, canComment);
            enriched.hasreplies = true;
            enriched.replycount = comment.replies.length;
        }
        return enriched;
    });
};

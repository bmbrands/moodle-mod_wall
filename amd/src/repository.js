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
 * Repository module for mod_wall - centralizes all AJAX web service calls.
 *
 * @module     mod_wall/repository
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Repository class for wall web service calls.
 */
class Repository {
    /**
     * Get comments for a wall.
     *
     * @param {Object} args - {cmid}
     * @returns {Promise}
     */
    getComments(args) {
        const request = {
            methodname: 'mod_wall_get_comments',
            args: args,
        };
        return Ajax.call([request])[0].fail(Notification.exception);
    }

    /**
     * Add a comment to a wall.
     *
     * @param {Object} args - {cmid, content, parentid}
     * @returns {Promise}
     */
    addComment(args) {
        const request = {
            methodname: 'mod_wall_add_comment',
            args: args,
        };
        return Ajax.call([request])[0].fail(Notification.exception);
    }

    /**
     * Delete a comment.
     *
     * @param {Object} args - {cmid, commentid}
     * @returns {Promise}
     */
    deleteComment(args) {
        const request = {
            methodname: 'mod_wall_delete_comment',
            args: args,
        };
        return Ajax.call([request])[0].fail(Notification.exception);
    }

    /**
     * Vote on a comment.
     *
     * @param {Object} args - {cmid, commentid, vote}
     * @returns {Promise}
     */
    voteComment(args) {
        const request = {
            methodname: 'mod_wall_vote_comment',
            args: args,
        };
        return Ajax.call([request])[0].fail(Notification.exception);
    }

    /**
     * Vote on a wall instance (independent score).
     *
     * @param {Object} args - {cmid, vote}
     * @returns {Promise}
     */
    voteWall(args) {
        const request = {
            methodname: 'mod_wall_vote_wall',
            args: args,
        };
        return Ajax.call([request])[0].fail(Notification.exception);
    }
}

const RepositoryInstance = new Repository();
export default RepositoryInstance;

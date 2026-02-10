<?php
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

namespace mod_wall\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service: Vote on a wall instance (upvote/downvote toggle).
 * This is an independent score for the wall element itself, not linked to comment votes.
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vote_wall extends external_api {
    /**
     * Vote on a wall instance.
     *
     * @param int $cmid The course module ID.
     * @param int $vote 1 for upvote, -1 for downvote.
     * @return array
     */
    public static function execute(int $cmid, int $vote): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'vote' => $vote,
        ]);

        $cm = get_coursemodule_from_id('wall', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/wall:view', $context);

        $wallid = $cm->instance;

        // Validate vote value.
        if (!in_array($params['vote'], [1, -1])) {
            throw new \invalid_parameter_exception('Vote must be 1 or -1');
        }

        // Check if user already voted on this wall.
        $existing = $DB->get_record('wall_instance_vote', [
            'wallid' => $wallid,
            'userid' => $USER->id,
        ]);

        if ($existing) {
            if ((int)$existing->vote === $params['vote']) {
                // Same vote again = remove vote (toggle off).
                $DB->delete_records('wall_instance_vote', ['id' => $existing->id]);
            } else {
                // Different vote = change vote.
                $existing->vote = $params['vote'];
                $existing->timemodified = time();
                $DB->update_record('wall_instance_vote', $existing);
            }
        } else {
            // New vote.
            $record = new \stdClass();
            $record->wallid = $wallid;
            $record->userid = $USER->id;
            $record->vote = $params['vote'];
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('wall_instance_vote', $record);
        }

        // Return updated vote counts.
        $upvotes = $DB->count_records_select(
            'wall_instance_vote',
            'wallid = :wallid AND vote = 1',
            ['wallid' => $wallid]
        );
        $downvotes = $DB->count_records_select(
            'wall_instance_vote',
            'wallid = :wallid AND vote = -1',
            ['wallid' => $wallid]
        );
        $uservote = $DB->get_field('wall_instance_vote', 'vote', [
            'wallid' => $wallid,
            'userid' => $USER->id,
        ]);

        return [
            'upvotes' => (int)$upvotes,
            'downvotes' => (int)$downvotes,
            'score' => (int)$upvotes - (int)$downvotes,
            'uservote' => $uservote ? (int)$uservote : 0,
        ];
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'vote' => new external_value(PARAM_INT, '1 for upvote, -1 for downvote'),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'upvotes' => new external_value(PARAM_INT, 'Total upvotes'),
            'downvotes' => new external_value(PARAM_INT, 'Total downvotes'),
            'score' => new external_value(PARAM_INT, 'Net score (upvotes - downvotes)'),
            'uservote' => new external_value(PARAM_INT, 'Current user vote: 1, -1, or 0'),
        ]);
    }
}

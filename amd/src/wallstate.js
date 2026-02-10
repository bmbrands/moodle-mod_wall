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
 * A reactive state class that stores data for the wall module.
 * Components subscribe to specific keys and get notified when values change.
 *
 * @module     mod_wall/wallstate
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A simple state class for the wall module.
 * Classes can subscribe to keys to get updates when values change.
 */
class WallState {
    /**
     * Constructor.
     */
    constructor() {
        this.data = {};
        this.subscribers = [];
    }

    /**
     * Set the full data object and notify all subscribers.
     *
     * @param {Object} data The data.
     */
    setData(data) {
        this.data = data;
        this.notifySubscribers();
    }

    /**
     * Set a single value and notify its subscriber.
     *
     * @param {string} key The key.
     * @param {*} value The value.
     */
    setValue(key, value) {
        this.data[key] = value;
        this.notifySubscriber(key);
    }

    /**
     * Get a single value.
     *
     * @param {string} key The key.
     * @return {*} The value.
     */
    getValue(key) {
        return this.data[key];
    }

    /**
     * Get the full data object.
     *
     * @return {Object} The data.
     */
    getData() {
        return this.data;
    }

    /**
     * Subscribe to state changes for a specific key.
     *
     * @param {string} key The key to subscribe to.
     * @param {Function} callback The callback receiving the full data object.
     */
    subscribe(key, callback) {
        if (typeof key !== 'string') {
            throw new Error('The key must be a string');
        }
        if (typeof callback !== 'function') {
            throw new Error('The callback must be a function');
        }

        // Prevent duplicate subscriptions.
        const exists = this.subscribers.find(
            subscriber => subscriber.key === key && subscriber.callback === callback
        );
        if (exists) {
            return;
        }
        this.subscribers.push({key, callback});
    }

    /**
     * Unsubscribe a callback from state changes.
     *
     * @param {Function} callback The callback to remove.
     */
    unsubscribe(callback) {
        this.subscribers = this.subscribers.filter(subscriber => subscriber.callback !== callback);
    }

    /**
     * Notify all subscribers whose key exists in the data.
     */
    notifySubscribers() {
        this.subscribers.forEach(subscriber => {
            if (this.data[subscriber.key] !== undefined) {
                subscriber.callback(this.data);
            }
        });
    }

    /**
     * Notify subscribers for a specific key.
     *
     * @param {string} key The key that changed.
     */
    notifySubscriber(key) {
        this.subscribers.forEach(subscriber => {
            if (subscriber.key === key) {
                subscriber.callback(this.data);
            }
        });
    }
}

const state = new WallState();
export default state;
